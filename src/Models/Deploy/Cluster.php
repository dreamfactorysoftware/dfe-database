<?php
namespace DreamFactory\Library\Fabric\Database\Models\Deploy;

use DreamFactory\Library\Fabric\Database\Models\DeployModel;

class Cluster extends DeployModel
{
    //******************************************************************************
    //* Members
    //******************************************************************************

    /**
     * @type string The table name
     */
    protected $table = 'cluster_t';

    //******************************************************************************
    //* Methods
    //******************************************************************************

    /**
     * Our instances relationship
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     */
    public function servers()
    {
        return $this->belongsToMany( __NAMESPACE__ . '\\Server', 'cluster_server_asgn_t' );
    }

    public function user()
    {
        return $this->hasOne( __NAMESPACE__ . '\\ServiceUser', 'user_id' );
    }

}