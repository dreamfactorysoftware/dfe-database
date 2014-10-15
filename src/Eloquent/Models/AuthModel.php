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
namespace DreamFactory\Tools\Fabric\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;

/**
 * @property int   id
 * @property mixed lmod_date
 * @property mixed create_date
 * @method static Builder where( $column, $operator = null, $value = null, $boolean = 'and' )
 */
class AuthModel extends Model
{
    //******************************************************************************
    //* Constants
    //******************************************************************************

    /**
     * @type string Override timestamp column
     */
    const UPDATED_AT = 'lmod_date';
    /**
     * @type string Override timestamp column
     */
    const CREATED_AT = 'create_date';

    //******************************************************************************
    //* Members
    //******************************************************************************

    /**
     * @type string Our connection
     */
    protected $connection = 'fabric-auth';
    /**
     * @type bool
     */
    protected static $unguarded = true;

}