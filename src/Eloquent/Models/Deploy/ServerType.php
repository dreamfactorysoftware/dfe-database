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
 * @property mixed lmod_date
 * @property mixed create_date
 */
class ServerType extends DeployModel
{
    //******************************************************************************
    //* Members
    //******************************************************************************

    /**
     * @type string The table name
     */
    protected $table = 'server_type_t';

    //******************************************************************************
    //* Methods
    //******************************************************************************
    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function serverType()
    {
        return $this->belongsTo( __NAMESPACE__ . '\\Server', 'server_type_id' );
    }
}