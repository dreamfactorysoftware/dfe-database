<?php namespace DreamFactory\Enterprise\Database\Models;

use Illuminate\Database\Query\Builder;

/**
 * limit_t
 *
 * @property integer cluster_id
 * @property integer instance_id
 * @property string  limit_key_text
 * @property integer limit_value
 * @property integer period_value
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

    /**
     * @param Builder $query
     * @param integer $clusterId
     * @param integer $instanceId
     *
     * @return Builder
     */
    public function scopeByClusterInstance($query, $clusterId, $instanceId)
    {
        return $query->where('cluster_id', '=' . $clusterId)->where('instance_id', '=', $instanceId);
    }
}