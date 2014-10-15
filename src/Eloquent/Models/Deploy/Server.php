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

/**
 * server_t
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
        return $this->hasOne( 'ServerType', 'id', 'server_type_id' );
    }

    /**
     * Our instances relationship
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function clusters()
    {
        return $this->belongsToMany( __NAMESPACE__ . '\\ClusterServer', 'cluster_server_asgn_t', 'server_id', 'cluster_id' );
    }

    public function instances()
    {
        return $this->hasMany( __NAMESPACE__ . '\\Instance', 'id', 'server_id', 'instance_id' );
    }
}