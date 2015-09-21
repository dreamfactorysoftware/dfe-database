<?php namespace DreamFactory\Enterprise\Database\Models;

use DreamFactory\Enterprise\Common\Enums\EnterpriseDefaults;
use DreamFactory\Enterprise\Common\Facades\InstanceStorage;
use DreamFactory\Enterprise\Common\Utility\UniqueId;
use DreamFactory\Enterprise\Database\Contracts\OwnedEntity;
use DreamFactory\Enterprise\Database\Enums\OwnerTypes;
use DreamFactory\Enterprise\Database\Traits\CheckNickname;
use DreamFactory\Enterprise\Database\Traits\KeyMaster;
use DreamFactory\Library\Utility\Disk;
use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Database\Query\Builder;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;

/**
 * [20150812-gha] Corrected the property list to reflect the current data model
 * [20150413-gha] This model was moved to Deploy from Auth
 *
 * user_t table
 *
 * @property string $email_addr_text
 * @property string $password_text
 * @property string $remember_token
 * @property string $first_name_text
 * @property string $last_name_text
 * @property string $nickname_text
 * @property string $api_token_text
 * @property string $storage_id_text
 * @property string $external_id_text
 * @property string $external_password_text
 * @property int    $owner_id
 * @property int    $owner_type_nbr
 * @property string $company_name_text
 * @property string $title_text
 * @property string $city_text
 * @property string $state_province_text
 * @property string $country_text
 * @property string $postal_code_text
 * @property string $phone_text
 * @property int    $opt_in_ind
 * @property int    $agree_ind
 * @property string $last_login_date
 * @property string $last_login_ip_text
 * @property int    $admin_ind
 * @property int    $activate_ind
 * @property int    $active_ind
 *
 * @method static \Illuminate\Database\Eloquent\Builder byEmail(string $email)
 */
class User extends EnterpriseModel implements AuthenticatableContract, CanResetPasswordContract, OwnedEntity
{
    //******************************************************************************
    //* Traits
    //******************************************************************************

    use Authenticatable, KeyMaster, CheckNickname;

    //******************************************************************************
    //* Members
    //******************************************************************************

    /**
     * @type string The table name
     */
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

    //******************************************************************************
    //* Methods
    //******************************************************************************

    /**
     * Boot method to wire in our events
     */
    public static function boot()
    {
        parent::boot();

        static::created(function (User $model){
            AppKey::createKeyForEntity($model);
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
}