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
namespace DreamFactory\Library\Fabric\Database\Models\Deploy;

use DreamFactory\Enterprise\Services\Enums\ServerTypes;
use DreamFactory\Library\Fabric\Database\Models\DeployModel;
use Illuminate\Database\Query\Builder;

/**
 * server_t
 *
 * @property int    $server_type_id
 * @property string $server_id_text
 * @property string $host_text
 * @property array  $config_text
 *
 * @property Mount  $mount
 *
 * @method static Builder byNameOrId( string $nameOrId )
 */
class Server extends DeployModel
{
    //******************************************************************************
    //* Members
    //******************************************************************************

    /**
     * @type string The table name
     */
    protected $table = 'server_t';
    /** @inheritdoc */
    protected $casts = [
        'id' => 'integer',
        'server_type_id' => 'integer',
        'mount_id' => 'integer',
        'config_text' => 'array',
    ];

    //******************************************************************************
    //* Methods
    //******************************************************************************

    protected static function boot()
    {
        parent::boot();

        static::creating(
            function ( Server $server )
            {

            }
        );
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function serverType()
    {
        return $this->hasOne( __NAMESPACE__ . '\\ServerType' );
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne|Mount
     */
    public function mount()
    {
        return $this->hasOne( __NAMESPACE__ . '\\Mount', 'id', 'mount_id' );
    }

    /**
     * Cluster in which I belong
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function cluster()
    {
        return Cluster::whereRaw(
            'id IN (SELECT csa.cluster_id FROM cluster_server_asgn_t csa WHERE csa.server_id = :server_id)',
            [':server_id' => $this->id]
        )->first();
    }

    /**
     * Instances on this server
     *
     * @return array|static[]
     */
    public function instances()
    {
        return Instance::whereRaw(
            'id IN (SELECT isa.instance_id FROM instance_server_asgn_t isa WHERE isa.instance_id = instance_t.id AND isa.server_id = :server_id)',
            [':server_id', '=', $this->id]
        )->get();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function clusterServer()
    {
        return $this->belongsTo( 'clusterServer', __NAMESPACE__ . '\\ClusterServer', 'cluster_id' );
    }

    /**
     * @param int|string|Cluster $clusterId
     *
     * @return bool True if server removed from cluster
     */
    public function removeFromCluster( $clusterId )
    {
        $_cluster = ( $clusterId instanceof Cluster ) ? $clusterId : $this->_getCluster( $clusterId );

        if ( $this->belongsToCluster( $_cluster->id ) )
        {
            return
                1 == ClusterServer::where( 'cluster_id', '=', $_cluster->id )
                    ->where( 'server_id', '=', $this->id )
                    ->delete();
        }

        return false;
    }

    /**
     * @param int|string $clusterId
     *
     * @return bool
     */
    public function addToCluster( $clusterId )
    {
        //  This will fail if $clusterId is bogus
        $this->removeFromCluster( $_cluster = $this->_getCluster( $clusterId ) );

        return 1 == ClusterServer::insert( ['cluster_id' => $_cluster->id, 'server_id' => $this->id] );
    }

    /**
     * @param int|string $clusterId
     *
     * @return Cluster
     */
    protected function _getCluster( $clusterId )
    {
        if ( null === ( $_cluster = Cluster::byNameOrId( $clusterId )->first() ) )
        {
            throw new \InvalidArgumentException( 'The cluster id "' . $clusterId . '" is invalid.' );
        }

        return $_cluster;
    }

    /**
     * @param int|string $clusterId
     *
     * @return bool True if this instance
     */
    public function belongsToCluster( $clusterId )
    {
        $_cluster = $this->_getCluster( $clusterId );

        return 0 != ClusterServer::whereRaw(
            'cluster_id = :cluster_id AND server_id = :server_id',
            [
                ':cluster_id' => $_cluster->id,
                ':server_id'  => $this->id
            ]
        )->count();
    }

    /**
     * @param \Illuminate\Database\Query\Builder $query
     * @param string|int                         $nameOrId
     *
     * @return Builder
     */
    public function scopeByNameOrId( $query, $nameOrId )
    {
        return $query->whereRaw(
            'server_id_text = :server_id_text OR id = :id',
            [':server_id_text' => $nameOrId, ':id' => $nameOrId]
        );
    }

    /**
     * App servers only scope
     *
     * @param Builder $query
     *
     * @return Builder
     */
    public function scopeAppServers( $query )
    {
        return $this->scopeByType( $query, ServerTypes::APP );
    }

    /**
     * Db servers only scope
     *
     * @param Builder $query
     *
     * @return Builder
     */
    public function scopeDbServers( $query )
    {
        return $this->scopeByType( $query, ServerTypes::DB );
    }

    /**
     * Web servers only scope
     *
     * @param Builder $query
     *
     * @return Builder
     */
    public function scopeWebServers( $query )
    {
        return $this->scopeByType( $query, ServerTypes::WEB );
    }

    /**
     * Limit results by server type
     *
     * @param Builder $query
     * @param int     $typeId
     *
     * @return Builder
     */
    public function scopeByType( $query, $typeId )
    {
        return $query->where( 'server_type_id', '=', (int)$typeId );
    }

}