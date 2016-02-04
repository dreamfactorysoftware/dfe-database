<?php namespace DreamFactory\Enterprise\Database\Models;

use Carbon\Carbon;
use Illuminate\Database\Query\Builder;

/**
 * metrics_detail_t
 *
 * @property int      $user_id
 * @property int      $instance_id
 * @property array    $data_text
 * @property Carbon   $gather_date
 * @property Carbon   $created_at
 * @property Carbon   $modified_at
 *
 * @property User     $user
 * @property Instance $instance
 *
 * @method static Builder byGatherDate($gatherDate = null)
 */
class MetricsDetail extends EnterpriseModel
{
    //******************************************************************************
    //* Constants
    //******************************************************************************

    /** @inheritdoc */
    const CREATED_AT = 'created_at';
    /** @inheritdoc */
    const UPDATED_AT = 'updated_at';

    //******************************************************************************
    //* Members
    //******************************************************************************

    /** @inheritdoc */
    protected $table = 'metrics_detail_t';
    /** @inheritdoc */
    protected $casts = ['user_id' => 'integer', 'instance_id' => 'integer', 'data_text' => 'array',];

    //******************************************************************************
    //* Methods
    //******************************************************************************

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function instance()
    {
        return $this->hasOne(static::MODEL_NAMESPACE . 'Instance', 'id', 'instance_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function user()
    {
        return $this->hasOne(static::MODEL_NAMESPACE . 'User', 'id', 'user_id');
    }

    /**
     * @param Builder       $query
     * @param string|Carbon $gatherDate
     *
     * @return mixed
     */
    public function scopeByGatherDate($query, $gatherDate = null)
    {
        return $query->where('gather_date', $gatherDate ?: date('Y-m-d'));
    }
}
