<?php
namespace DreamFactory\Enterprise\Database\Models;

/**
 * cluster_server_asgn_t
 *
 * @property int cluster_id
 * @property int server_id
 */
class ClusterServer extends EnterpriseModel
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
        return $this->hasOne(static::MODEL_NAMESPACE . 'Server', 'id', 'server_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function cluster()
    {
        return $this->hasOne(static::MODEL_NAMESPACE . 'Cluster', 'id', 'cluster_id');
    }

    /** @inheritdoc */
    public function getKey()
    {
        return parent::getKey();
    }

}