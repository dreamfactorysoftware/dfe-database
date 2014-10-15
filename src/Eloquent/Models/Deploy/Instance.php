<?php
/**
 * This file is part of the DreamFactory Fabric(tm) Tools Library
 *
 * Copyright 2014 DreamFactory Software, Inc. All Rights Reserved.
 *
 * Proprietary code, DO NOT DISTRIBUTE!
 *
 * @email   <support@dreamfactory.com>
 * @license proprietary
 */
namespace DreamFactory\Tools\Fabric\Eloquent\Models\Deploy;

use DreamFactory\Tools\Fabric\Eloquent\Models\DeployModel;
use DreamFactory\Tools\Fabric\Enums\InstanceStates;
use DreamFactory\Tools\Fabric\Exceptions\InstanceNotActivatedException;
use DreamFactory\Tools\Fabric\Exceptions\InstanceUnlockedException;
use Illuminate\Database\Query\Builder;

/**
 * instance_t
 *
 * @property int platform_state_nbr
 * @property int state_nbr
 * @property int ready_state_nbr
 *
 * @method static Builder instanceName( string $instanceName )
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
        return $this->hasMany( 'Server', 'server_id' );
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

        if ( InstanceStates::ACTIVATED != $_instance->platform_state_nbr )
        {
            throw new InstanceNotActivatedException( $id );
        }

        return $_instance->update( array('platform_state_nbr' => 0) );
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

        if ( InstanceStates::LOCKED != $_instance->platform_state_nbr )
        {
            throw new InstanceUnlockedException( $id );
        }

        return $_instance->update( array('platform_state_nbr' => 1) );
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
    public function scopeOnDatabase( $query, $dbServerId )
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
        return $this->belongsTo( 'DreamFactory\\Tools\\Fabric\\Eloquent\\Models\\Auth\\User' );
    }
}