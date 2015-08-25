<?php namespace DreamFactory\Enterprise\Database\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;

/**
 * Base class for all DFE models
 *
 * @property int    $id
 * @property Carbon $lmod_date
 * @property Carbon $create_date
 *
 * @method static Builder|EloquentBuilder where($column, $operator = null, $value = null, $boolean = 'and')
 * @method static Builder|EloquentBuilder whereRaw($clause, $params = [])
 * @method static Builder|EloquentBuilder join($table, $one, $operator = null, $two = null, $type = 'inner', $where = false)
 * @method static Builder|EloquentBuilder joinWhere($table, $one, $operator, $two, $type = 'inner')
 * @method static Builder|EloquentBuilder leftJoin($table, $first, $operator = null, $second = null)
 * @method static Builder|EloquentBuilder leftJoinWhere($table, $one, $operator, $two)
 * @method static Builder|EloquentBuilder rightJoin($table, $first, $operator = null, $second = null)
 * @method static Builder|EloquentBuilder rightJoinWhere($table, $one, $operator, $two)
 * @method static Builder|EloquentBuilder orWhere($column, $operator = null, $value = null)
 * @method static Builder|EloquentBuilder orWhereRaw($sql, array $bindings = [])
 * @method static Builder|EloquentBuilder whereBetween($column, array $values, $boolean = 'and', $not = false)
 * @method static Builder|EloquentBuilder orWhereBetween($column, array $values)
 * @method static Builder|EloquentBuilder whereNotBetween($column, array $values, $boolean = 'and')
 * @method static Builder|EloquentBuilder orWhereNotBetween($column, array $values)
 * @method static Builder|EloquentBuilder whereNested(\Closure $callback, $boolean = 'and')
 * @method static Builder|EloquentBuilder addNestedWhereQuery($query, $boolean = 'and')
 * @method static Builder|EloquentBuilder whereExists(\Closure $callback, $boolean = 'and', $not = false)
 * @method static Builder|EloquentBuilder orWhereExists(\Closure $callback, $not = false)
 * @method static Builder|EloquentBuilder whereNotExists(\Closure $callback, $boolean = 'and')
 * @method static Builder|EloquentBuilder orWhereNotExists(\Closure $callback)
 * @method static Builder|EloquentBuilder whereIn($column, $values, $boolean = 'and', $not = false)
 * @method static Builder|EloquentBuilder orWhereIn($column, $values)
 * @method static Builder|EloquentBuilder whereNotIn($column, $values, $boolean = 'and')
 * @method static Builder|EloquentBuilder orWhereNotIn($column, $values)
 * @method static Builder|EloquentBuilder whereNull($column, $boolean = 'and', $not = false)
 * @method static Builder|EloquentBuilder orWhereNull($column)
 * @method static Builder|EloquentBuilder whereNotNull($column, $boolean = 'and')
 * @method static Builder|EloquentBuilder orWhereNotNull($column)
 * @method static Builder|EloquentBuilder whereDate($column, $operator, $value, $boolean = 'and')
 * @method static Builder|EloquentBuilder whereDay($column, $operator, $value, $boolean = 'and')
 * @method static Builder|EloquentBuilder whereMonth($column, $operator, $value, $boolean = 'and')
 * @method static Builder|EloquentBuilder whereYear($column, $operator, $value, $boolean = 'and')
 * @method static Builder|EloquentBuilder dynamicWhere($method, $parameters)
 * @method static Builder|EloquentBuilder groupBy()
 * @method static Builder|EloquentBuilder having($column, $operator = null, $value = null, $boolean = 'and')
 * @method static Builder|EloquentBuilder orHaving($column, $operator = null, $value = null)
 * @method static Builder|EloquentBuilder havingRaw($sql, array $bindings = [], $boolean = 'and')
 * @method static Builder|EloquentBuilder orHavingRaw($sql, array $bindings = [])
 * @method static Builder|EloquentBuilder orderBy($column, $direction = 'asc')
 * @method static EnterpriseModel latest($column = 'created_at')
 * @method static EnterpriseModel oldest($column = 'created_at')
 * @method static Builder|EloquentBuilder orderByRaw($sql, $bindings = [])
 * @method static EnterpriseModel offset($value)
 * @method static Builder|EloquentBuilder skip($value)
 * @method static Builder|EloquentBuilder limit($value)
 * @method static Builder|EloquentBuilder take($value)
 * @method static Builder|EloquentBuilder forPage($page, $perPage = 15)
 * @method static Builder|EloquentBuilder union($query, $all = false)
 * @method static Builder|EloquentBuilder unionAll($query)
 * @method static Builder|EloquentBuilder lock($value = true)
 * @method static Builder|EloquentBuilder lockForUpdate()
 * @method static Builder|EloquentBuilder sharedLock()
 * @method static string  toSql()
 * @method static EnterpriseModel find($id, $columns = ['*'])
 * @method static EnterpriseModel findOrFail($id, $columns = ['*'])
 * @method static EnterpriseModel pluck($column)
 * @method static EnterpriseModel first($columns = ['*'])
 * @method static EnterpriseModel firstOrFail($columns = ['*'])
 * @method static Collection get($columns = ['*'])
 * @method static Collection getFresh($columns = ['*'])
 * @method static Collection runSelect()
 * @method static Collection paginate($perPage = 15, $columns = ['*'])
 * @method static Collection simplePaginate($perPage = 15, $columns = ['*'])
 * @method static int getCountForPagination()
 * @method static mixed backupFieldsForCount()
 * @method static mixed restoreFieldsForCount()
 * @method static mixed chunk($count, callable $callback)
 * @method static mixed lists($column, $key = null)
 * @method static mixed getListSelect($column, $key)
 * @method static string implode($column, $glue = null)
 * @method static mixed exists()
 * @method static int count($columns = '*')
 * @method static mixed min($column)
 * @method static mixed max($column)
 * @method static mixed sum($column)
 * @method static mixed avg($column)
 * @method static mixed aggregate($function, $columns = ['*'])
 * @method static mixed insert(array $values)
 * @method static int insertGetId(array $values, $sequence = null)
 * @method static mixed truncate()
 * @method static Builder|EloquentBuilder raw($value)
 */
class EnterpriseModel extends Model
{
    //*************************************************************************
    //* Constants
    //*************************************************************************

    /**
     * @type string Override timestamp column
     */
    const UPDATED_AT = 'lmod_date';
    /**
     * @type string Override timestamp column
     */
    const CREATED_AT = 'create_date';
    /**
     * @type string The namespace of the deployment models
     */
    const DEPLOY_NAMESPACE = __NAMESPACE__;
    /**
     * @type string The hard-coded namespace of the models with trailing slash
     */
    const MODEL_NAMESPACE = 'DreamFactory\\Enterprise\\Database\\Models\\';
    /**
     * @type string
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

    //******************************************************************************
    //* Methods
    //******************************************************************************
    /**
     * The "booting" method of the model.
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        //  Called before inserting and updating
        static::saving(function (EnterpriseModel $row){
            static::enforceBusinessLogic($row);
        }
        );
    }

    /**
     * @param \DreamFactory\Enterprise\Database\Models\EnterpriseModel|mixed $row
     */
    protected static function enforceBusinessLogic($row)
    {
        //  Make sure owner type is set properly
        if (isset($row->owner_id, $row->owner_type_nbr)) {
            if (null !== $row->owner_id && null === $row->owner_type_nbr) {
                $row->owner_type_nbr = $row->getMorphClass();
            }
        }
    }
}