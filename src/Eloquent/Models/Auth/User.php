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
namespace DreamFactory\Tools\Fabric\Eloquent\Models\Auth;

use DreamFactory\Tools\Fabric\Eloquent\Models\AuthModel;

/**
 * fabric_auth.user_t
 */
class User extends AuthModel
{
    //******************************************************************************
    //* Members
    //******************************************************************************

    /**
     * @type string The table name
     */
    protected $table = 'user_t';

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function instances()
    {
        return $this->hasMany( 'DreamFactory\\Tools\\Fabric\\Eloquent\\Models\\Deploy\\Instance', 'user_id', 'id' );
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function hashes()
    {
        return $this->belongsTo( 'DreamFactory\\Tools\\Fabric\\Eloquent\\Models\\Deploy\\OwnerHash', 'owner_id', 'id' );
    }
}