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

class OwnerHash extends DeployModel
{
    //******************************************************************************
    //* Members
    //******************************************************************************

    /**
     * @type string The table name
     */
    protected $table = 'owner_hash_t';

    /**
     * Our owners
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function owners()
    {
        return $this->hasMany( 'DreamFactory\\Tools\\Fabric\\Eloquent\\Models\\Auth\\User', 'id', 'owner_id' );
    }

}