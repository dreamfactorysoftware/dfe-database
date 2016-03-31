<?php namespace DreamFactory\Enterprise\Database\Models;

use Carbon\Carbon;
use Illuminate\Database\Query\Builder;

/**
 * deactivation_t
 *
 * Properties
 *
 * @property int    user_id
 * @property int    instance_id
 * @property string activate_by_date
 * @property int    extend_count_nbr
 * @property int    user_notified_nbr
 * @property int    action_reason_nbr
 *
 * Scopes
 *
 * @method static EnterpriseModel|Deactivation|\Illuminate\Database\Eloquent\Builder|Builder instanceId(int $instanceId)
 * @method static EnterpriseModel|Deactivation|\Illuminate\Database\Eloquent\Builder|Builder userId(int $userId)
 * @method static EnterpriseModel|Deactivation|\Illuminate\Database\Eloquent\Builder|Builder expired($byDate, int $extends = null)
 */
class Deactivation extends EnterpriseModel
{
    //******************************************************************************
    //* Members
    //******************************************************************************

    /**
     * @type string The table name
     */
    protected $table = 'deactivation_t';

    //******************************************************************************
    //* Methods
    //******************************************************************************

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo|User
     */
    public function user()
    {
        return $this->belongsTo(static::MODEL_NAMESPACE . 'User');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo|Instance
     */
    public function instance()
    {
        return $this->belongsTo(static::MODEL_NAMESPACE . 'Instance');
    }

    /**
     * @param Builder $query
     * @param int     $userId
     *
     * @return Builder
     */
    public function scopeUserId($query, $userId)
    {
        if (!empty($userId)) {
            return $query->where('user_id', '=', $userId);
        }

        return $query;
    }

    /**
     * @param Builder $query
     * @param int     $instanceId
     *
     * @return Builder
     */
    public function scopeInstanceId($query, $instanceId)
    {
        if (!empty($instanceId)) {
            return $query->where('instance_id', '=', $instanceId);
        }

        return $query;
    }

    /**
     * @param Builder            $query
     * @param null|string|Carbon $byDate  Date to activate by. Defaults to today.
     * @param null               $extends The number of allowed extensions, if any.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public static function scopeExpired($query, $byDate = null, $extends = null)
    {
        $query = $query->where('activate_by_date', '<', $byDate ?: Carbon::now());

        if (!empty($extends)) {
            $query = $query->where('extend_count_nbr', '>', $extends);
        }

        return $query;
    }
}
