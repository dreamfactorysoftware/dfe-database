<?php namespace DreamFactory\Enterprise\Database\Models;

use DreamFactory\Enterprise\Common\Enums\EnterpriseDefaults;
use DreamFactory\Enterprise\Common\Utility\UniqueId;
use DreamFactory\Enterprise\Database\Enums\OwnerTypes;
use DreamFactory\Enterprise\Database\Traits\AuthorizedEntity;
use DreamFactory\Enterprise\Database\Traits\CheckNickname;
use DreamFactory\Enterprise\Database\Traits\KeyHolder;
use DreamFactory\Enterprise\Database\Traits\OwnedEntity;
use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Database\Query\Builder;

/**
 * 2015-04-13 GHA: This model was moved to Deploy from Auth
 *
 *
 * user_t table
 *
 * @property string api_token_text
 * @property string first_name_text
 * @property string last_name_text
 * @property string nickname_text
 * @property string email_addr_text
 * @property string password_text
 * @property int    external_id
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
 * @property string valid_email_hash_text
 * @property int    valid_email_hash_expire_time
 * @property string valid_email_date
 * @property string recover_hash_text
 * @property int    recover_hash_expire_time
 * @property string last_login_date
 * @property string last_login_ip_text
 * @property int    admin_ind
 * @property string storage_id_text
 * @property int    activate_ind
 * @property int    active_ind
 * @property string remember_token
 *
 * @method static Builder byEmail(string $email)
 */
class User extends EnterpriseModel implements AuthenticatableContract, CanResetPasswordContract
{
    //******************************************************************************
    //* Traits
    //******************************************************************************

    use Authenticatable, AuthorizedEntity, KeyHolder, CheckNickname, OwnedEntity;

    //******************************************************************************
    //* Members
    //******************************************************************************

    /**
     * @type string The table name
     */
    protected $table = 'user_t';
    /** @inheritdoc */
    protected $casts = [
        'cluster_id'    => 'integer',
        'app_server_id' => 'integer',
        'db_server_id'  => 'integer',
        'web_server_id' => 'integer',
        'owner_id'      => 'integer',
    ];

    //******************************************************************************
    //* Methods
    //******************************************************************************

    /** @inheritdoc */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->owner_type_nbr = OwnerTypes::USER;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany|OwnerHash[]
     */
    public function hashes()
    {
        return $this->hasMany(__NAMESPACE__ . '\\OwnerHash', 'id', 'owner_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function snapshots()
    {
        return $this->hasMany(__NAMESPACE__ . '\\Snapshot');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany|Instance[]
     */
    public function instances()
    {
        return $this->hasMany(__NAMESPACE__ . '\\Instances');
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
     * Boot method to wire in our events
     */
    public static function boot()
    {
        parent::boot();

        static::creating(
            function (User $model) {
                $model->checkStorageKey();
            }
        );

        static::updating(
            function (User $model) {
                $model->checkStorageKey();
            }
        );
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
        return hash(
            config('dfe.signature-method', EnterpriseDefaults::DEFAULT_SIGNATURE_METHOD),
            $this->storage_id_text
        );
    }

}