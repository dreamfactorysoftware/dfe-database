<?php namespace DreamFactory\Enterprise\Database\Models;

use Illuminate\Database\Query\Builder;

/**
 * instance_server_asgn_t
 *
 * @property int    instance_id
 * @property int    server_id
 * @property string create_date
 * @property string lmod_date
 *
 * @method static Builder instanceName(string $instanceName)
 * @method static Builder withDbName(string $dbName)
 * @method static Builder onDbServer(int $dbServerId)
 */
class InstanceServer extends EnterpriseModel
{
    //******************************************************************************
    //* Members
    //******************************************************************************

    /**
     * @type string The table name
     */
    protected $table = 'instance_server_asgn_t';

    //******************************************************************************
    //* Methods
    //******************************************************************************

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne|Instance
     */
    public function instance()
    {
        return $this->hasOne(static::MODEL_NAMESPACE . 'Instance', 'id', 'instance_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne|Server
     */
    public function server()
    {
        return $this->hasOne(static::MODEL_NAMESPACE . 'Server', 'id', 'server_id');
    }

}