<?php
namespace DreamFactory\Library\Fabric\Database\Models\Deploy;

use DreamFactory\Library\Fabric\Database\Models\DeployModel;

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
        return $this->hasMany( static::AUTH_NAMESPACE . '\\User', 'id', 'owner_id' );
    }

}