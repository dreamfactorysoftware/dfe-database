<?php namespace DreamFactory\Enterprise\Database\Models;

use Illuminate\Database\Eloquent\Builder;

/**
 * job_result_t
 *
 * @property string $result_id_text
 * @property array  $result_text
 *
 * @method static Builder byResultId(string $resultId)
 */
class JobResult extends EnterpriseModel
{
    //******************************************************************************
    //* Members
    //******************************************************************************

    /** @inheritdoc */
    protected $table = 'job_result_t';
    /** @inheritdoc */
    protected $casts = ['result_text' => 'array',];

    //******************************************************************************
    //* Methods
    //******************************************************************************

    /**
     * @param Builder $query
     * @param string  $resultId
     *
     * @return Builder
     */
    public function scopeByResultId($query, $resultId)
    {
        return $query->where('result_id_text', $resultId);
    }
}
