<?php namespace DreamFactory\Enterprise\Database\Models;

use DB;
use DreamFactory\Enterprise\Common\Commands\ConsoleCommand;
use DreamFactory\Enterprise\Database\Enums\AppKeyClasses;
use DreamFactory\Enterprise\Common\Enums\EnterpriseDefaults;
use DreamFactory\Enterprise\Common\Packets\ErrorPacket;
use DreamFactory\Enterprise\Common\Packets\SuccessPacket;
use DreamFactory\Enterprise\Common\Utility\UniqueId;
use DreamFactory\Enterprise\Database\Contracts\OwnedEntity;
use DreamFactory\Enterprise\Database\Enums\OwnerTypes;
use DreamFactory\Enterprise\Database\Traits\CanHashEmailAddress;
use DreamFactory\Enterprise\Database\Traits\CheckNickname;
use DreamFactory\Enterprise\Database\Traits\KeyMaster;
use DreamFactory\Enterprise\Storage\Facades\InstanceStorage;
use DreamFactory\Library\Utility\Disk;
use DreamFactory\Library\Utility\IfSet;
use Exception;
use Hash;
use Illuminate\Auth\Authenticatable;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use \Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use InvalidArgumentException;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;
use Log;
use RuntimeException;

/**
 * [20150812-gha] Corrected the property list to reflect the current data model
 * [20150413-gha] This model was moved to Deploy from Auth
 *
 * user_t table
 *
 * @property string email_addr_text
 * @property string password_text
 * @property string remember_token
 * @property string first_name_text
 * @property string last_name_text
 * @property string nickname_text
 * @property string api_token_text
 * @property string storage_id_text
 * @property string external_id_text
 * @property string external_password_text
 * @property int    owner_id
 * @property int    owner_type_nbr
 * @property string company_name_text
 * @property string title_text
 * @property string city_text
 * @property string state_province_text
 * @property string country_text
 * @property string postal_code_text
 * @property string phone_text
 * @property int    opt_in_ind
 * @property int    agree_ind
 * @property string last_login_date
 * @property string last_login_ip_text
 * @property int    admin_ind
 * @property int    activate_ind
 * @property int    active_ind
 *
 * @method static \Illuminate\Database\Eloquent\Builder byEmail($email)
 */
class User extends EnterpriseModel implements AuthorizableContract, AuthenticatableContract, CanResetPasswordContract, OwnedEntity
{
    //******************************************************************************
    //* Traits
    //******************************************************************************

    use Authorizable, Authenticatable, CanResetPassword, KeyMaster, CheckNickname, CanHashEmailAddress;

    //******************************************************************************
    //* Members
    //******************************************************************************

    /** @inheritdoc */
    protected $table = 'user_t';
    /**
     * @inheritdoc
     * [20150812-gha] Corrected incorrect casts.
     */
    protected $casts = [
        'id'             => 'integer',
        'owner_id'       => 'integer',
        'owner_type_nbr' => 'integer',
        'active_ind'     => 'boolean',
        'activate_ind'   => 'boolean',
        'admin_ind'      => 'boolean',
        'opt_in_ind'     => 'boolean',
        'agree_ind'      => 'boolean',
    ];
    /** @inheritdoc */
    protected $hidden = ['password_text', 'external_password_text', 'remember_token',];

    //******************************************************************************
    //* Methods
    //******************************************************************************

    /**
     * Boot method to wire in our events
     */
    public static function boot()
    {
        parent::boot();

        static::creating(function(User $model) {
            $model->checkStorageKey();

            //  Ensure user is active upon creation
            $model->active_ind = true;
        });

        static::created(function(User $model) {
            AppKey::createKeyForEntity($model, OwnerTypes::USER);
        });
    }

    /** @inheritdoc */
    protected static function enforceBusinessLogic($row)
    {
        parent::enforceBusinessLogic($row);

        $row->checkStorageKey();
    }

    /** @inheritdoc */
    public function owner()
    {
        return $this->belongsTo(static::MODEL_NAMESPACE . 'User', 'id', 'owner_id');
    }

    /** @inheritdoc */
    /** @noinspection PhpMissingParentCallCommonInspection */
    public function getMorphClass()
    {
        return $this->owner_type_nbr ?: OwnerTypes::USER;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany|OwnerHash[]
     */
    public function hashes()
    {
        return $this->hasMany(static::MODEL_NAMESPACE . 'OwnerHash', 'id', 'owner_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function snapshots()
    {
        return $this->hasMany(static::MODEL_NAMESPACE . 'Snapshot');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany|Instance[]
     */
    public function instances()
    {
        return $this->hasMany(static::MODEL_NAMESPACE . 'Instance');
    }

    /**
     * Check and assign if necessary a storage ID
     */
    public function checkStorageKey()
    {
        if (empty($this->storage_id_text)) {
            $this->storage_id_text = UniqueId::generate(__CLASS__);
        }
    }

    /**
     * @param Builder $query
     * @param string  $email
     *
     * @return Builder
     */
    public function scopeByEmail($query, $email)
    {
        return $query->where('email_addr_text', $email);
    }

    /**
     * @param Builder $query
     * @param string  $emailOrId
     *
     * @return Builder
     */
    public function scopeByIdOrEmail($query, $emailOrId)
    {
        return is_numeric($emailOrId) ? $query->where('id', $emailOrId) : $query->where('email_addr_text', $emailOrId);
    }

    /**
     * Get the password for the user.
     *
     * @return string
     */
    public function getAuthPassword()
    {
        return $this->password_text;
    }

    /**
     * Get the e-mail address where password reset links are sent.
     *
     * @return string
     */
    public function getEmailForPasswordReset()
    {
        return $this->email_addr_text;
    }

    /**
     * @return string The hashed storage key for this user
     */
    public function getHash()
    {
        return hash(config('dfe.signature-method', EnterpriseDefaults::DEFAULT_SIGNATURE_METHOD),
            $this->storage_id_text);
    }

    /**
     * @param string|array|null $append
     *
     * @return string
     */
    public function getSnapshotPath($append = null)
    {
        return Disk::path([
            $this->getOwnerPrivatePath(config('snapshot.storage-path', EnterpriseDefaults::SNAPSHOT_PATH_NAME), false),
            $append,
        ],
            true);
    }

    /**
     * @return \League\Flysystem\Filesystem
     */
    public function getSnapshotMount()
    {
        return new Filesystem(new Local($this->getSnapshotPath()));
    }

    /**
     * @param string|array|null $append
     * @param bool|true         $create
     * @param int               $mode
     * @param bool|true         $recursive
     *
     * @return string
     */
    public function getOwnerPrivatePath($append = null, $create = true, $mode = 0777, $recursive = true)
    {
        InstanceStorage::buildStorageMap($this->storage_id_text);

        return Disk::path([
            config('provisioning.storage-root', EnterpriseDefaults::STORAGE_ROOT),
            InstanceStorage::getStorageRootPath(),
            InstanceStorage::getPrivatePathName(),
            $append,
        ],
            $create,
            $mode,
            $recursive);
    }

    /**
     * Easy registration from Request
     *
     * @param \Illuminate\Http\Request $request
     * @param bool                     $validate If false, no validation is done.
     *
     * @return array|User
     */
    public static function register(Request $request, $validate = true)
    {
        $_message = null;

        if (false === ($_user = static::doRegister($request->input(), $validate, $_message))) {
            return $validate ? ErrorPacket::create(null, Response::HTTP_INTERNAL_SERVER_ERROR, $_message) : null;
        }

        return $validate ? SuccessPacket::create($_user, Response::HTTP_CREATED) : $_user;
    }

    /**
     * Easy registration from Request
     *
     * @param ConsoleCommand|array $command
     * @param bool                 $validate If false, no validation is done.
     *
     * @return static
     */
    public static function artisanRegister($command, $validate = true)
    {
        $_data = $command instanceof ConsoleCommand ? array_merge($command->argument(), $command->option()) : (is_array($command) ? $command : []);

        if (false === ($_user = static::doRegister($_data, $validate, $_message))) {
            throw new RuntimeException($_message);
        }

        return $_user;
    }

    /**
     * Standardized user creation method
     *
     * @param array       $data
     * @param bool        $validate     If false, no validation is done.
     * @param string|null $errorMessage Any error message returned
     *
     * @return static
     * @throws Exception
     */
    protected static function doRegister(array $data, $validate = true, &$errorMessage = null)
    {
        $_email = array_get($data, 'email', array_get($data, 'email_addr_text'));
        $_first = array_get($data, 'first-name', array_get($data, 'first_name_text', array_get($data, 'firstname')));
        $_last = array_get($data, 'last-name', array_get($data, 'last_name_text', array_get($data, 'lastname')));
        $_password = array_get($data, 'password', array_get($data, 'password_text'));
        $_nickname = array_get($data, 'nickname', array_get($data, 'nickname_text'));
        $_company = array_get($data, 'company', array_get($data, 'company_name_text'));
        $_phone = array_get($data, 'phone', array_get($data, 'phone_text'));
        $_active = IfSet::getBool($data, 'active', IfSet::getBool($data, 'active_ind', true));

        if ($validate) {
            if (empty($_email) || empty($_password) || empty($_first) || empty($_last)) {
                Log::error('[user.register] incomplete request', $data);

                throw new InvalidArgumentException('Missing required fields');
            }

            if (false === filter_var($_email, FILTER_VALIDATE_EMAIL)) {
                Log::error('[user.register] invalid email address', $data);

                throw new InvalidArgumentException('Email address invalid');
            }
        }

        //  See if we know this cat...
        /** @type User $_user */
        if (null !== ($_user = User::byEmail($_email)->first())) {
            //  Existing user found!
            Log::notice('[user.register] existing user registration attempt', ['request' => $data, 'existing' => $_user->toArray()]);

            throw new InvalidArgumentException('Email address already registered.');
        }

        $_attributes = [
            'first_name_text'   => $_first,
            'last_name_text'    => $_last,
            'email_addr_text'   => $_email,
            'nickname_text'     => $_nickname,
            'password_text'     => Hash::make($_password),
            'phone_text'        => $_phone,
            'company_name_text' => $_company,
            'active_ind'        => $_active,
        ];

        //  Create a user account
        try {
            $_user = DB::transaction(function() use ($_attributes) {
                $_user = User::create($_attributes);

                if (null === ($_appKey = AppKey::mine($_user->id, OwnerTypes::USER))) {
                    $_appKey = AppKey::create([
                        'key_class_text' => AppKeyClasses::USER,
                        'owner_id'       => $_user->id,
                        'owner_type_nbr' => OwnerTypes::USER,
                        'server_secret'  => config('dfe.security.console-api-key'),
                    ]);
                }

                //  Update the user with the key info and activate
                $_user->api_token_text = $_appKey->client_id;
                $_user->active_ind = 1;
                $_user->save();

                return $_user;
            });

            Log::info('[user.register] new registration', $_user->toArray());

            return $_user;
        } catch (Exception $_ex) {
            if (false !== ($_pos = stripos($errorMessage = $_ex->getMessage(), ' (sql: '))) {
                $errorMessage = substr($errorMessage, 0, $_pos);
            }

            Log::error('[user.register] database error creating user: ' . $errorMessage, $data);

            throw $_ex;
        }
    }
}
