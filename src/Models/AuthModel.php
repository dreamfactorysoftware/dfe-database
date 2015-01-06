<?php
namespace DreamFactory\Library\Fabric\Database\Models;

use Illuminate\Database\Query\Builder;

/**
 * @property int   id
 * @property mixed lmod_date
 * @property mixed create_date
 * @method static Builder where( $column, $operator = null, $value = null, $boolean = 'and' )
 */
class AuthModel extends BaseModel
{
    //******************************************************************************
    //* Members
    //******************************************************************************

    /**
     * @type string Our connection
     */
    protected $connection = 'fabric-auth';

}