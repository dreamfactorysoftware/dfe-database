<?php
namespace DreamFactory\Enterprise\Database\Models;

use DreamFactory\Enterprise\Database\ModelsModel;

class OwnerHash extends DeployModel
{
    //******************************************************************************
    //* Members
    //******************************************************************************

    /**
     * @type string The table name
     */
    protected $table = 'owner_hash_t';

    //******************************************************************************
    //* Methods
    //******************************************************************************

    /**
     * Our owners
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function owners()
    {
        return $this->hasMany( static::DEPLOY_NAMESPACE . '\\User', 'id', 'owner_id' );
    }

}