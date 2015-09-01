<?php namespace DreamFactory\Enterprise\Database\Models;

use Illuminate\Database\Query\Builder;

/**
 * limit_t
 *
 * @property integer cluster_id
 * @property integer instance_id
 * @property string  limit_key_text
 * @property integer value_nbr
 * @property integer period_nbr
 *
 * @method static Builder byClusterInstance(string $instanceId, string $clusterId)
 */
class Limit extends EnterpriseModel
{
    //******************************************************************************
    //* Members
    //******************************************************************************

    /**
     * @type string The table name
     */
    protected $table = 'limit_t';

    //******************************************************************************
    //* Methods
    //******************************************************************************

    /**
     * @param Builder $query
     * @param integer $clusterId
     * @param integer $instanceId
     *
     * @return Builder
     */
    public function scopeByClusterInstance($query, $clusterId, $instanceId)
    {
        return $query->whereRaw('(cluster_id = :cluster_id OR cluster_id IS NULL) AND (instance_id = :instance_id OR instance_id IS NULL)',
            [':cluster_id' => $clusterId, ':instance_id' => $instanceId]);
    }
}