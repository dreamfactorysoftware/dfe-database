<?php
namespace DreamFactory\Library\Fabric\Database\Models\Deploy;

use DreamFactory\Library\Fabric\Common\Enums\DeactivationReasons;
use DreamFactory\Library\Fabric\Common\Enums\OperationalStates;
use DreamFactory\Library\Fabric\Common\Exceptions\InstanceNotActivatedException;
use DreamFactory\Library\Fabric\Common\Exceptions\InstanceUnlockedException;
use DreamFactory\Library\Fabric\Database\Models\DeployModel;
use Illuminate\Database\Query\Builder;

/**
 * instance_t
 *
 * @property int platform_state_nbr
 * @property int state_nbr
 * @property int ready_state_nbr
 *
 * @method static Builder instanceName( string $instanceName )
 * @method static Builder withDbName( string $dbName )
 * @method static Builder onDbServer( int $dbServerId )
 */
class Instance extends DeployModel
{
    //******************************************************************************
    //* Members
    //******************************************************************************

    /**
     * @type string The table name
     */
    protected $table = 'instance_t';

    //******************************************************************************
    //* Methods
    //******************************************************************************

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function servers()
    {
        return $this->hasMany( __NAMESPACE__ . '\\Server', 'server_id' );
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function dbServer()
    {
        return $this->hasOne( __NAMESPACE__ . '\\Server', 'id', 'db_server_id' );
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function appServer()
    {
        return $this->hasOne( __NAMESPACE__ . '\\Server', 'id', 'app_server_id' );
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function webServer()
    {
        return $this->hasOne( __NAMESPACE__ . '\\Server', 'id', 'web_server_id' );
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo( static::AUTH_NAMESPACE . '\\User' );
    }

    /**
     * @param int $id
     *
     * @throws InstanceNotActivatedException
     * @return bool|int
     */
    public static function lock( $id )
    {
        /** @type Instance $_instance */
        $_instance = static::findOrFail( $id );

        if ( OperationalStates::ACTIVATED != $_instance->platform_state_nbr )
        {
            throw new InstanceNotActivatedException( 'Instance "' . $id . '" not activated.' );
        }

        return $_instance->update( array('platform_state_nbr' => OperationalStates::LOCKED) );
    }

    /**
     * @param int $id
     *
     * @return bool|int
     * @throws InstanceUnlockedException
     */
    public static function unlock( $id )
    {
        /** @type Instance $_instance */
        $_instance = static::findOrFail( $id );

        if ( OperationalStates::LOCKED != $_instance->platform_state_nbr )
        {
            throw new InstanceUnlockedException( 'Instance "' . $id . '" not locked.' );
        }

        return $_instance->update( array('platform_state_nbr' => OperationalStates::ACTIVATED) );
    }

    /**
     * @param array $schemaInfo
     * @param int   $actionReason
     *
     * @return bool
     */
    public static function deactivate( array $schemaInfo, $actionReason = DeactivationReasons::NON_USE )
    {
        if ( null === ( $_row = Deactivation::instanceId( $schemaInfo['instance']->id )->first() ) )
        {
            //  Not found
            $_row = new Deactivation();
            $_row->user_id = $schemaInfo['instance']->user_id;
            $_row->instance_id = $schemaInfo['instance']->id;
        }

        if ( false === $actionReason )
        {
            //  Set activation date to 7 days from now.
            $_row->activate_by_date = date( 'Y-m-d H-i-s', time() + ( 7 * 86400 ) );
        }
        else
        {
            $_row->action_reason_nbr = $actionReason;
        }

        return $_row->save();
    }

    /**
     * @param Builder $query
     * @param         $userId
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
     * @param int     $dbServerId
     *
     * @return Builder
     */
    public function scopeOnDbServer( $query, $dbServerId )
    {
        if ( !empty( $dbServerId ) )
        {
            return $query->where( 'db_server_id', '=', $dbServerId );
        }

        return $query;
    }

    /**
     * @param Builder $query
     * @param int     $stateId
     *
     * @return Builder
     */
    public function scopeWithPlatformState( $query, $stateId )
    {
        if ( null !== $stateId )
        {
            return $query->where( 'platform_state_nbr', '=', $stateId );
        }

        return $query;
    }

    /**
     * @param Builder $query
     * @param string  $dbName The database name to query for
     *
     * @return Builder
     */
    public function scopeWithDbName( $query, $dbName )
    {
        if ( null !== $dbName )
        {
            return $query->where( 'db_name_text', '=', $dbName );
        }

        return $query;
    }

    /**
     * @param Builder $query
     * @param string  $instanceName
     *
     * @return Builder
     */
    public function scopeInstanceName( $query, $instanceName )
    {
        return $query->where( 'instance_name_text', '=', $instanceName );
    }

    /**
     * @param Builder $query
     * @param string  $instanceName
     *
     * @return Builder
     */
    public function scopeLikeInstanceName( $query, $instanceName )
    {
        return $query->where( 'instance_name_text', 'like', '%' . $instanceName . '%' );
    }

}