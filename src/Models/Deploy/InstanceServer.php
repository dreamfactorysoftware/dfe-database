<?php
namespace DreamFactory\Library\Fabric\Database\Models\Deploy;

use DreamFactory\Library\Fabric\Database\Models\DeployModel;
use Illuminate\Database\Query\Builder;

/**
 * instance_server_asgn_t
 *
 * @property int    instance_id
 * @property int    server_id
 * @property string create_date
 * @property string lmod_date
 *
 * @method static Builder instanceName( string $instanceName )
 * @method static Builder withDbName( string $dbName )
 * @method static Builder onDbServer( int $dbServerId )
 */
class InstanceServer extends DeployModel
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
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function instance()
    {
        return $this->hasOne( __NAMESPACE__ . '\\Instance', 'id', 'instance_id' );
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function server()
    {
        return $this->hasOne( __NAMESPACE__ . '\\Server', 'id', 'server_id' );
    }

}