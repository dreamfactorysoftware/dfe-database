<?php
namespace DreamFactory\Library\Fabric\Database\Models\Deploy;

use DreamFactory\Library\Fabric\Database\Models\DeployModel;
use Illuminate\Database\Eloquent\Builder;

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
     * @return mixed
     */
    public function servers()
    {
        return Server::whereRaw(
            'id IN ( SELECT csa.cluster_id FROM cluster_server_asgn_t csa WHERE csa.cluster_id  = :cluster_id )',
            ['cluster_id' => $this->id]
        )->get();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function user()
    {
        return $this->hasOne( __NAMESPACE__ . '\\ServiceUser' );
    }

    /**
     * @param Builder    $query
     * @param string|int $clusterNameOrId
     *
     * @return Builder
     */
    public function scopeByNameOrId( $query, $clusterNameOrId )
    {
        return $query->whereRaw(
            'cluster_id_text = :cluster_id_text OR id = :id',
            [':cluster_id_text' => $clusterNameOrId, ':id' => $clusterNameOrId]
        );
    }

}