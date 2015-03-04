<?php
namespace DreamFactory\Library\Fabric\Database\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Base class for DFE models
 *
 * @property int   id
 * @property mixed lmod_date
 * @property mixed create_date
 *
 * @method static Builder where( $column, $operator = null, $value = null, $boolean = 'and' )
 * @method static Builder whereRaw( $clause, $params = [] )
 * @method static Builder join( $table, $one, $operator = null, $two = null, $type = 'inner', $where = false )
 * @method static Builder joinWhere( $table, $one, $operator, $two, $type = 'inner' )
 * @method static Builder leftJoin( $table, $first, $operator = null, $second = null )
 * @method static Builder leftJoinWhere( $table, $one, $operator, $two )
 * @method static Builder rightJoin( $table, $first, $operator = null, $second = null )
 * @method static Builder rightJoinWhere( $table, $one, $operator, $two )
 * @method static Builder orWhere( $column, $operator = null, $value = null )
 * @method static Builder orWhereRaw( $sql, array $bindings = [] )
 * @method static Builder whereBetween( $column, array $values, $boolean = 'and', $not = false )
 * @method static Builder orWhereBetween( $column, array $values )
 * @method static Builder whereNotBetween( $column, array $values, $boolean = 'and' )
 * @method static Builder orWhereNotBetween( $column, array $values )
 * @method static Builder whereNested( \Closure $callback, $boolean = 'and' )
 * @method static Builder addNestedWhereQuery( $query, $boolean = 'and' )
 * @method static Builder whereExists( \Closure $callback, $boolean = 'and', $not = false )
 * @method static Builder orWhereExists( \Closure $callback, $not = false )
 * @method static Builder whereNotExists( \Closure $callback, $boolean = 'and' )
 * @method static Builder orWhereNotExists( \Closure $callback )
 * @method static Builder whereIn( $column, $values, $boolean = 'and', $not = false )
 * @method static Builder orWhereIn( $column, $values )
 * @method static Builder whereNotIn( $column, $values, $boolean = 'and' )
 * @method static Builder orWhereNotIn( $column, $values )
 * @method static Builder whereNull( $column, $boolean = 'and', $not = false )
 * @method static Builder orWhereNull( $column )
 * @method static Builder whereNotNull( $column, $boolean = 'and' )
 * @method static Builder orWhereNotNull( $column )
 * @method static Builder whereDate( $column, $operator, $value, $boolean = 'and' )
 * @method static Builder whereDay( $column, $operator, $value, $boolean = 'and' )
 * @method static Builder whereMonth( $column, $operator, $value, $boolean = 'and' )
 * @method static Builder whereYear( $column, $operator, $value, $boolean = 'and' )
 * @method static Builder dynamicWhere( $method, $parameters )
 * @method static Builder groupBy()
 * @method static Builder having( $column, $operator = null, $value = null, $boolean = 'and' )
 * @method static Builder orHaving( $column, $operator = null, $value = null )
 * @method static Builder havingRaw( $sql, array $bindings = [], $boolean = 'and' )
 * @method static Builder orHavingRaw( $sql, array $bindings = [] )
 * @method static Builder orderBy( $column, $direction = 'asc' )
 * @method static Model   latest( $column = 'created_at' )
 * @method static Model   oldest( $column = 'created_at' )
 * @method static Builder orderByRaw( $sql, $bindings = [] )
 * @method static Model   offset( $value )
 * @method static Builder skip( $value )
 * @method static Builder limit( $value )
 * @method static Builder take( $value )
 * @method static Builder forPage( $page, $perPage = 15 )
 * @method static Builder union( $query, $all = false )
 * @method static Builder unionAll( $query )
 * @method static Builder lock( $value = true )
 * @method static Builder lockForUpdate()
 * @method static Builder sharedLock()
 * @method static string  toSql()
 * @method static Collection find( $id, $columns = ['*'] )
 * @method static Collection findOrFail( $id, $columns = ['*'] )
 * @method static Model   pluck( $column )
 * @method static Model   first( $columns = ['*'] )
 * @method static Model   firstOrFail( $columns = ['*'] )
 * @method static Collection get( $columns = ['*'] )
 * @method static Collection getFresh( $columns = ['*'] )
 * @method static Collection runSelect()
 * @method static Collection paginate( $perPage = 15, $columns = ['*'] )
 * @method static Collection simplePaginate( $perPage = 15, $columns = ['*'] )
 * @method static int getCountForPagination()
 * @method static mixed backupFieldsForCount()
 * @method static mixed restoreFieldsForCount()
 * @method static mixed chunk( $count, callable $callback )
 * @method static mixed lists( $column, $key = null )
 * @method static mixed getListSelect( $column, $key )
 * @method static string implode( $column, $glue = null )
 * @method static mixed exists()
 * @method static int count( $columns = '*' )
 * @method static mixed min( $column )
 * @method static mixed max( $column )
 * @method static mixed sum( $column )
 * @method static mixed avg( $column )
 * @method static mixed aggregate( $function, $columns = ['*'] )
 * @method static mixed insert( array $values )
 * @method static int insertGetId( array $values, $sequence = null )
 * @method static mixed truncate()
 * @method static Builder raw( $value )
 */
class BaseModel extends Model
{
    //*************************************************************************
    //* Constants
    //*************************************************************************

    /**
     * @var string
     */
    const FABRIC_STORAGE_KEY = '%%STORAGE_KEY%%';
    /**
     * @var string
     */
    const FABRIC_BASE_STORAGE_PATH = '/data/storage/%%STORAGE_KEY%%';
    /**
     * @var string
     */
    const FABRIC_INSTANCE_PRIVATE_PATH = '/data/storage/%%STORAGE_KEY%%/.private';
    /**
     * @var string
     */
    const FABRIC_INSTANCE_SNAPSHOT_PATH = '/data/storage/%%STORAGE_KEY%%/.private/snapshots';
    /**
     * @type string The namespace of the auth models
     */
    const AUTH_NAMESPACE = 'DreamFactory\\Library\\Fabric\\Database\\Models\\Auth';
    /**
     * @type string The namespace of the deployment models
     */
    const DEPLOY_NAMESPACE = 'DreamFactory\\Library\\Fabric\\Database\\Models\\Deploy';
    /**
     * @type string Override timestamp column
     */
    const UPDATED_AT = 'lmod_date';
    /**
     * @type string Override timestamp column
     */
    const CREATED_AT = 'create_date';
    /**
     * @var string
     */
    const HOSTED_SNAPSHOT_PATH = '/snapshots';

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
