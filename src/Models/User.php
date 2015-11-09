<?php namespace DreamFactory\Enterprise\Database\Models;

use DreamFactory\Enterprise\Common\Enums\AppKeyClasses;
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
use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;

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
class User extends EnterpriseModel implements AuthenticatableContract, CanResetPasswordContract, OwnedEntity
{
    //******************************************************************************
    //* Traits
    //******************************************************************************

    use Authenticatable, KeyMaster, CheckNickname, CanHashEmailAddress;

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

        static::creating(function (User $model){
            $model->checkStorageKey();

            //  Ensure user is active upon creation
            $model->active_ind = true;
        });

        static::created(function (User $model){
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
     * Get the unique identifier for the user.
     *
     * @return mixed
     */
    public function getAuthIdentifier()
    {
        return $this->id;
    }

    /**
     * Get the token value for the "remember me" session.
     *
     * @return string
     */
    public function getRememberToken()
    {
        return $this->remember_token;
    }

    /**
     * Set the token value for the "remember me" session.
     *
     * @param  string $value
     *
     * @return void
     */
    public function setRememberToken($value)
    {
        $this->remember_token = $value;
    }

    /**
     * Get the column name for the "remember me" token.
     *
     * @return string
     */
    public function getRememberTokenName()
    {
        return 'remember_token';
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
     * Standardized user creation method
     *
     * @param \Illuminate\Http\Request $request
     * @param bool                     $validate If false, no validation is done.
     *
     * @return \DreamFactory\Enterprise\Common\Packets\ErrorPacket|\DreamFactory\Enterprise\Common\Packets\SuccessPacket
     */
    public static function register(Request $request, $validate = true)
    {
        $_email = $request->input('email', $request->input('email_addr_text'));
        $_first = $request->input('firstname', $request->input('first_name_text'));
        $_last = $request->input('lastname', $request->input('last_name_text'));
        $_password = $request->input('password', $request->input('password_text'));

        $_nickname = $request->input('nickname', $request->input('nickname_text', $_first));
        $_company = $request->input('company', $request->input('company_name_text'));
        $_phone = $request->input('phone', $request->input('phone_text'));

        if ($validate) {
            if (empty($_email) || empty($_password) || empty($_first) || empty($_last)) {
                /** @noinspection PhpUndefinedMethodInspection */
                Log::error('missing required fields from partner post', ['payload' => $request->input()]);

                throw new \InvalidArgumentException('Missing required fields');
            }

            if (false === filter_var($_email, FILTER_VALIDATE_EMAIL)) {
                /** @noinspection PhpUndefinedMethodInspection */
                Log::error('invalid email address "' . $_email . '"', ['payload' => $request->input()]);

                throw new \InvalidArgumentException('Email address invalid');
            }
        }

        //  See if we know this cat...
        if (null !== ($_user = User::byEmail($_email)->first())) {
            //  Existing user found, don't add to database...
            $_values = $_user->toArray();
            unset($_values['password_text'], $_values['external_password_text']);

            /** @noinspection PhpUndefinedMethodInspection */
            Log::info('existing user attempting registration through api', ['user' => $_values]);

            return $_user;
        }

        //  Create a user account
        try {
            /** @type User $_user */
            /** @noinspection PhpUndefinedMethodInspection */
            $_user =
                DB::transaction(function () use ($request, $_first, $_last, $_email, $_password, $_nickname, $_phone, $_company){
                    /** @noinspection PhpUndefinedMethodInspection */
                    $_user = User::create([
                        'first_name_text'   => $_first,
                        'last_name_text'    => $_last,
                        'email_addr_text'   => $_email,
                        'nickname_text'     => $_nickname,
                        'password_text'     => Hash::make($_password),
                        'phone_text'        => $_phone,
                        'company_name_text' => $_company,
                    ]);

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

            $_values = $_user->toArray();
            unset($_values['password_text'], $_values['external_password_text']);

            /** @noinspection PhpUndefinedMethodInspection */
            Log::info('new user registered', ['user' => $_values]);

            return $validate ? SuccessPacket::create($_user, Response::HTTP_CREATED) : $_user;
        } catch (\Exception $_ex) {
            if (false !== ($_pos = stripos($_message = $_ex->getMessage(), ' (sql: '))) {
                $_message = substr($_message, 0, $_pos);
            }

            /** @noinspection PhpUndefinedMethodInspection */
            Log::error('database error creating user from ops-resource post: ' . $_message);

            return $validate ? ErrorPacket::create(null, Response::HTTP_INTERNAL_SERVER_ERROR, $_message) : null;
        }
    }
}
