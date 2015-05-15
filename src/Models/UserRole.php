<?php
namespace DreamFactory\Enterprise\Database\Models;

use DreamFactory\Enterprise\Database\ModelsModel;

/**
 * user_role_asgn_t
 *
 * @property int    user_id
 * @property int    role_id
 */
class UserRole extends DeployModel
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
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function user()
    {
        return $this->hasOne( __NAMESPACE__ . '\\ServiceUser', 'user_id' );
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function role()
    {
        return $this->hasOne( __NAMESPACE__ . '\\Role' );
    }

}