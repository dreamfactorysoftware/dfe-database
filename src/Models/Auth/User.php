<?php
namespace DreamFactory\Library\Fabric\Database\Models\Auth;

use DreamFactory\Library\Fabric\Common\Utility\UniqueId;
use DreamFactory\Library\Fabric\Database\Models\AuthModel;
use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;

/**
 * user_t table
 *
 * @property int    drupal_id
 * @property string api_token_text
 * @property string first_name_text
 * @property string last_name_text
 * @property string display_name_text
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
 */
class User extends AuthModel implements AuthenticatableContract, CanResetPasswordContract
{
    //******************************************************************************
    //* Traits
    //******************************************************************************

    use Authenticatable;

    //******************************************************************************
    //* Members
    //******************************************************************************

    /**
     * @type string The table name
     */
    protected $table = 'user_t';

    //******************************************************************************
    //* Methods
    //******************************************************************************

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function instances()
    {
        return $this->hasMany(
            static::DEPLOY_NAMESPACE . '\\Instance',
            'user_id',
            'id'
        );
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
        return $this->hasMany( __NAMESPACE__ . '\\Server', 'user_id' );
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

                if ( empty( $model->display_name_text ) )
                {
                    $model->display_name_text = trim( $model->first_name_text . ' ' . $model->last_name_text, '- ' );
                }
            }
        );

        static::updating(
            function ( User $model )
            {
                $model->checkStorageKey();

                if ( empty( $model->display_name_text ) )
                {
                    $model->display_name_text = trim( $model->first_name_text . ' ' . $model->last_name_text, '- ' );
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
}