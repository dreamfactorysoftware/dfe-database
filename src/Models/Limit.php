<?php namespace DreamFactory\Enterprise\Database\Models;

use Illuminate\Database\Query\Builder;

/**
 * limit_t
 *
 * @property integer $cluster_id
 * @property integer $instance_id
 * @property integer $limit_type_nbr
 * @property string  $limit_key_text
 * @property integer $limit_nbr
 * @property integer $period_nbr
 * @property string  $label_text
 * @property boolean $active_ind
 *
 * @method static Builder byClusterInstance(string $instanceId, string $clusterId)
 */
class Limit extends EnterpriseModel
{
    //******************************************************************************
    //* Members
    //******************************************************************************

    /** @inheritdoc */
    protected $table = 'limit_t';
    /** @inheritdoc */
    protected $casts = [
        'cluster_id'     => 'integer',
        'instance_id'    => 'integer',
        'limit_type_nbr' => 'integer',
        'limit_nbr'      => 'integer',
        'period_nbr'     => 'integer',
        'active_ind'     => 'boolean',
    ];

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
        return $query->whereRaw('(cluster_id = :cluster_id OR cluster_id IS NULL) AND (instance_id = :instance_id OR instance_id IS NULL) AND active_ind = 1',
            [':cluster_id' => $clusterId, ':instance_id' => $instanceId]);
    }
}
