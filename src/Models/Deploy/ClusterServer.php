<?php
namespace DreamFactory\Library\Fabric\Database\Models\Deploy;

use DreamFactory\Library\Fabric\Database\Models\DeployModel;

/**
 * cluster_server_asgn_t
 *
 * @property int cluster_id
 * @property int server_id
 */
class ClusterServer extends DeployModel
{
    //******************************************************************************
    //* Members
    //******************************************************************************

    /**
     * @type string The table name
     */
    protected $table = 'cluster_server_asgn_t';

    //******************************************************************************
    //* Methods
    //******************************************************************************

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function servers()
    {
        return $this->hasOne( __NAMESPACE__ . '\\Server' );
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function clusters()
    {
        return $this->hasOne( __NAMESPACE__ . '\\Cluster' );
    }
}