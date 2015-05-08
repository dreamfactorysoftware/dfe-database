<?php namespace DreamFactory\Library\Fabric\Database\Models\Deploy;

use DreamFactory\Enterprise\Common\Enums\AppKeyClasses;
use DreamFactory\Library\Fabric\Common\Utility\UniqueId;
use DreamFactory\Library\Fabric\Database\Models\DeployModel;
use DreamFactory\Library\Fabric\Database\Traits\AuthorizedEntity;
use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;

/**
 * 2015-04-13 GHA: This model was moved to Deploy from Auth
 *
 *
 * user_t table
 *
 * @property int    drupal_id
 * @property string api_token_text
 * @property string first_name_text
 * @property string last_name_text
 * @property string nickname_text
 * @property string email_addr_text
 * @property string password_text
 * @property string drupal_password_text
 * @property int    owner_id
 * @property int    owner_type_nbr
 * @property string company_name_text
 * @property string title_text
 * @property string city_text
 * @property string state_province_text
 * @property string country_text
 * @property string postal_code_text
 * @property string phone_text
 * @property string fax_text
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
 * @property string remember_token
 */
class User extends DeployModel implements AuthenticatableContract, CanResetPasswordContract
{
    //******************************************************************************
    //* Traits
    //******************************************************************************

    use Authenticatable, AuthorizedEntity;

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

    /**
     * @param string $keyClass The key classes to return, otherwise all
     * @param int    $ownerId  The owner of the key
     *
     * @return \Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Eloquent\Model|null|static|static[]
     */
    public function getKeys( $keyClass = AppKeyClasses::USER, $ownerId = null )
    {
        return AppKey::byClass( $keyClass, $ownerId )->get();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function hashes()
    {
        return $this->belongsTo(
            static::DEPLOY_NAMESPACE . '\\OwnerHash',
            'owner_id',
            'id'
        );
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function servers()
    {
        return $this->hasMany( __NAMESPACE__ . '\\Server' );
    }

    /**
     * Check and assign if necessary a storage ID
     */
    public function checkStorageKey()
    {
        if ( empty( $this->storage_id_text ) )
        {
            $this->storage_id_text = UniqueId::generate( __CLASS__ );
        }
    }

    /**
     * Boot method to wire in our events
     */
    public static function boot()
    {
        parent::boot();

        static::creating(
            function ( User $model )
            {
                $model->checkStorageKey();

                if ( empty( $model->nickname_text ) )
                {
                    $model->nickname_text = trim( $model->first_name_text . ' ' . $model->last_name_text, '- ' );
                }
            }
        );

        static::updating(
            function ( User $model )
            {
                $model->checkStorageKey();

                if ( empty( $model->nickname_text ) )
                {
                    $model->nickname_text = trim( $model->first_name_text . ' ' . $model->last_name_text, '- ' );
                }
            }
        );
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
        return hash( config( 'dfe.hash-algorithm', 'sha256' ), $this->storage_id_text );
    }
}