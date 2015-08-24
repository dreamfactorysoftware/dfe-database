<?php namespace DreamFactory\Enterprise\Database\Models;

/**
 * user_role_asgn_t
 *
 * @property int user_id
 * @property int role_id
 */
class UserRole extends EnterpriseModel
{
    //******************************************************************************
    //* Members
    //******************************************************************************

    /**
     * @type string The table name
     */
    protected $table = 'user_role_asgn_t';

    //******************************************************************************
    //* Methods
    //******************************************************************************

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne|ServiceUser
     */
    public function user()
    {
        return $this->hasOne(static::MODEL_NAMESPACE . 'ServiceUser', 'id', 'user_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne|Role
     */
    public function role()
    {
        return $this->hasOne(static::MODEL_NAMESPACE . 'Role');
    }

}