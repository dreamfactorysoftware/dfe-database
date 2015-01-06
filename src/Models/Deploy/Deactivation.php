<?php
namespace DreamFactory\Library\Fabric\Database\Models\Deploy;

use DreamFactory\Library\Fabric\Database\Models\DeployModel;
use Illuminate\Database\Query\Builder;

/**
 * deactivation_t
 *
 * @property int    user_id
 * @property int    instance_id
 * @property string activate_by_date
 * @property int    extend_count_nbr
 * @property int    user_notified_nbr
 * @property int    action_reason_nbr
 *
 * @method static Builder instanceId( int $instanceId )
 */
class Deactivation extends DeployModel
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
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo( static::AUTH_NAMESPACE . '\\User', 'user_id' );
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function instance()
    {
        return $this->belongsTo( __NAMESPACE__ . '\\Instance', 'instance_id' );
    }

    /**
     * @param Builder $query
     * @param int     $userId
     *
     * @return Builder
     */
    public function scopeUserId( $query, $userId )
    {
        if ( !empty( $userId ) )
        {
            return $query->where( 'user_id', '=', $userId );
        }

        return $query;
    }

    /**
     * @param Builder $query
     * @param int     $instanceId
     *
     * @return Builder
     */
    public function scopeInstanceId( $query, $instanceId )
    {
        if ( !empty( $instanceId ) )
        {
            return $query->where( 'instance_id', '=', $instanceId );
        }

        return $query;
    }
}