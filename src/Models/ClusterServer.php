<?php
namespace DreamFactory\Enterprise\Database\Models;

/**
 * cluster_server_asgn_t
 *
 * @property int cluster_id
 * @property int server_id
 */
class ClusterServer extends BaseEnterpriseModel
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
    public function server()
    {
        return $this->hasOne( __NAMESPACE__ . '\\Server', 'id', 'server_id' );
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function cluster()
    {
        return $this->hasOne( __NAMESPACE__ . '\\Cluster', 'id', 'cluster_id' );
    }

}