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

use DreamFactory\Library\Fabric\Database\Models\DeployModel;

/**
 * server_t
 *
 * @property int    $server_type_id
 * @property string $server_id_text
 * @property string $host_text
 * @property string $config_text
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

    //******************************************************************************
    //* Methods
    //******************************************************************************

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function serverType()
    {
        return $this->hasOne( __NAMESPACE__ . '\\ServerType' );
    }

    /**
     * Clusters in which I belong
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function clusters()
    {
        return $this->hasManyThrough( __NAMESPACE__ . '\\ClusterServer', __NAMESPACE__ . '\\Cluster', 'cluster_id', 'server_id' );
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
}