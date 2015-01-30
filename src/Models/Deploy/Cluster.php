<?php
namespace DreamFactory\Library\Fabric\Database\Models\Deploy;

use DreamFactory\Library\Fabric\Database\Models\DeployModel;

/**
 * cluster_t
 *
 * @property int    user_id
 * @property string cluster_id_text
 * @property string subdomain_text
 *
 * @package DreamFactory\Library\Fabric\Database\Models\Deploy
 */
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

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function user()
    {
        return $this->hasOne( __NAMESPACE__ . '\\ServiceUser', 'user_id' );
    }

}