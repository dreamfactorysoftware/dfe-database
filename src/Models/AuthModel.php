<?php
namespace DreamFactory\Library\Fabric\Database\Models;

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