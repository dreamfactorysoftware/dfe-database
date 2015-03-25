<?php
/**
 * This file is part of the DreamFactory Fabric(tm) Tools Library
 *
 * Copyright 2014 DreamFactory Software, Inc. All Rights Reserved.
 *
 * Proprietary code, DO NOT DISTRIBUTE!
 *
 * @email   <support@dreamfactory.com>
 * @license proprietary
 */
namespace DreamFactory\Library\Fabric\Database\Models\Deploy;

use DreamFactory\Library\Fabric\Database\Models\DeployModel;
use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;

/**
 * service_user_t
 *
 * @property string first_name_text
 * @property string last_name_text
 * @property string display_name_text
 * @property string email_addr_text
 * @property string password_text
 * @property int    owner_id
 * @property int    owner_type_nbr
 * @property mixed  last_login_date
 * @property string last_login_ip_text
 * @property string remember_token
 */
class ServiceUser extends DeployModel implements AuthenticatableContract, CanResetPasswordContract
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
    protected $table = 'service_user_t';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['first_name_text', 'last_name_text', 'email_addr_text', 'password_text'];
    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = ['password_text', 'remember_token'];

    //******************************************************************************
    //* Methods
    //******************************************************************************

    /**
     * Boot method to wire in our events
     */
    public static function boot()
    {
        parent::boot();

        static::creating(
            function ( ServiceUser $model )
            {
                if ( empty( $model->display_name_text ) )
                {
                    $model->display_name_text = trim( $model->first_name_text . ' ' . $model->last_name_text, '- ' );
                }
            }
        );

        static::updating(
            function ( ServiceUser $model )
            {
                if ( empty( $model->display_name_text ) )
                {
                    $model->display_name_text = trim( $model->first_name_text . ' ' . $model->last_name_text, '- ' );
                }
            }
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
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     */
    public function roles()
    {
        return $this->hasManyThrough( __NAMESPACE__ . '\\Role', __NAMESPACE__ . '\\UserRole', 'user_id', 'role_id' );
    }

    /**
     * @param int $roleId
     *
     * @return bool True if user has role $roleId
     */
    public function hasRole( $roleId )
    {
        return 0 != UserRole::whereRaw( 'user_id = :user_id AND role_id = :role_id', [':user_id' => $this->id, ':role_id' => $roleId] )->count();
    }

    /**
     * @param int|string|Role $roleId
     *
     * @return bool True if server removed from role
     */
    public function removeRole( $roleId )
    {
        $_role = ( $roleId instanceof Role ) ? $roleId : $this->_getRole( $roleId );

        if ( $this->hasRole( $_role->id ) )
        {
            return
                1 == UserRole::whereRaw(
                    'role_id = :role_id AND user_id = :user_id',
                    [':role_id' => $_role->id, ':user_id' => $this->id]
                )->delete();
        }

        return false;
    }

    /**
     * @param int|string $roleId
     *
     * @return bool
     */
    public function addRole( $roleId )
    {
        //  This will fail if $roleId is bogus
        $this->removeRole( $_role = $this->_getRole( $roleId ) );

        return 1 == UserRole::insert( ['role_id' => $_role->id, 'user_id' => $this->id] );
    }

    /**
     * @param int|string $roleId
     *
     * @return Role
     */
    protected function _getRole( $roleId )
    {
        if ( null === ( $_role = Role::find( $roleId ) ) )
        {
            throw new \InvalidArgumentException( 'The role id "' . $roleId . '" is invalid.' );
        }

        return $_role;
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