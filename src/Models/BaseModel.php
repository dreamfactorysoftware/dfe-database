<?php
namespace DreamFactory\Library\Fabric\Database\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;

/**
 * Base class for DFE models
 *
 * @property int   id
 * @property mixed lmod_date
 * @property mixed create_date
 *
 * @method static Builder where( $column, $operator = null, $value = null, $boolean = 'and' )
 * @method static Builder whereRaw( $clause, $params = array() )
 */
class BaseModel extends Model
{
    //******************************************************************************
    //* Constants
    //******************************************************************************

    /**
     * @type string The namespace of the auth models
     */
    const AUTH_NAMESPACE = 'DreamFactory\\Enterprise\\Database\\Models\\Auth';
    /**
     * @type string The namespace of the deployment models
     */
    const DEPLOY_NAMESPACE = 'DreamFactory\\Enterprise\\Database\\Models\\Deploy';
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
     * @type bool
     */
    protected static $unguarded = true;
    /**
     * @type string Our connection
     */
    protected $connection = 'dfe-local';
}