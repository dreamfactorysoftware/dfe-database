<?php namespace DreamFactory\Enterprise\Database\Models;

use Carbon\Carbon;
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
 * @method static Builder|\Illuminate\Database\Eloquent\Builder where($column, $operator = null, $value = null, $boolean = 'and')
 * @method static Builder|\Illuminate\Database\Eloquent\Builder whereRaw($clause, $params = [])
 * @method static Builder|\Illuminate\Database\Eloquent\Builder join($table, $one, $operator = null, $two = null, $type = 'inner', $where = false)
 * @method static Builder|\Illuminate\Database\Eloquent\Builder joinWhere($table, $one, $operator, $two, $type = 'inner')
 * @method static Builder|\Illuminate\Database\Eloquent\Builder leftJoin($table, $first, $operator = null, $second = null)
 * @method static Builder|\Illuminate\Database\Eloquent\Builder leftJoinWhere($table, $one, $operator, $two)
 * @method static Builder|\Illuminate\Database\Eloquent\Builder rightJoin($table, $first, $operator = null, $second = null)
 * @method static Builder|\Illuminate\Database\Eloquent\Builder rightJoinWhere($table, $one, $operator, $two)
 * @method static Builder|\Illuminate\Database\Eloquent\Builder orWhere($column, $operator = null, $value = null)
 * @method static Builder|\Illuminate\Database\Eloquent\Builder orWhereRaw($sql, array $bindings = [])
 * @method static Builder|\Illuminate\Database\Eloquent\Builder whereBetween($column, array $values, $boolean = 'and', $not = false)
 * @method static Builder|\Illuminate\Database\Eloquent\Builder orWhereBetween($column, array $values)
 * @method static Builder|\Illuminate\Database\Eloquent\Builder whereNotBetween($column, array $values, $boolean = 'and')
 * @method static Builder|\Illuminate\Database\Eloquent\Builder orWhereNotBetween($column, array $values)
 * @method static Builder|\Illuminate\Database\Eloquent\Builder whereNested(\Closure $callback, $boolean = 'and')
 * @method static Builder|\Illuminate\Database\Eloquent\Builder addNestedWhereQuery($query, $boolean = 'and')
 * @method static Builder|\Illuminate\Database\Eloquent\Builder whereExists(\Closure $callback, $boolean = 'and', $not = false)
 * @method static Builder|\Illuminate\Database\Eloquent\Builder orWhereExists(\Closure $callback, $not = false)
 * @method static Builder|\Illuminate\Database\Eloquent\Builder whereNotExists(\Closure $callback, $boolean = 'and')
 * @method static Builder|\Illuminate\Database\Eloquent\Builder orWhereNotExists(\Closure $callback)
 * @method static Builder|\Illuminate\Database\Eloquent\Builder whereIn($column, $values, $boolean = 'and', $not = false)
 * @method static Builder|\Illuminate\Database\Eloquent\Builder orWhereIn($column, $values)
 * @method static Builder|\Illuminate\Database\Eloquent\Builder whereNotIn($column, $values, $boolean = 'and')
 * @method static Builder|\Illuminate\Database\Eloquent\Builder orWhereNotIn($column, $values)
 * @method static Builder|\Illuminate\Database\Eloquent\Builder whereNull($column, $boolean = 'and', $not = false)
 * @method static Builder|\Illuminate\Database\Eloquent\Builder orWhereNull($column)
 * @method static Builder|\Illuminate\Database\Eloquent\Builder whereNotNull($column, $boolean = 'and')
 * @method static Builder|\Illuminate\Database\Eloquent\Builder orWhereNotNull($column)
 * @method static Builder|\Illuminate\Database\Eloquent\Builder whereDate($column, $operator, $value, $boolean = 'and')
 * @method static Builder|\Illuminate\Database\Eloquent\Builder whereDay($column, $operator, $value, $boolean = 'and')
 * @method static Builder|\Illuminate\Database\Eloquent\Builder whereMonth($column, $operator, $value, $boolean = 'and')
 * @method static Builder|\Illuminate\Database\Eloquent\Builder whereYear($column, $operator, $value, $boolean = 'and')
 * @method static Builder|\Illuminate\Database\Eloquent\Builder dynamicWhere($method, $parameters)
 * @method static Builder|\Illuminate\Database\Eloquent\Builder groupBy()
 * @method static Builder|\Illuminate\Database\Eloquent\Builder having($column, $operator = null, $value = null, $boolean = 'and')
 * @method static Builder|\Illuminate\Database\Eloquent\Builder orHaving($column, $operator = null, $value = null)
 * @method static Builder|\Illuminate\Database\Eloquent\Builder havingRaw($sql, array $bindings = [], $boolean = 'and')
 * @method static Builder|\Illuminate\Database\Eloquent\Builder orHavingRaw($sql, array $bindings = [])
 * @method static Builder|\Illuminate\Database\Eloquent\Builder orderBy($column, $direction = 'asc')
 * @method static EnterpriseModel latest($column = 'created_at')
 * @method static EnterpriseModel oldest($column = 'created_at')
 * @method static Builder|\Illuminate\Database\Eloquent\Builder orderByRaw($sql, $bindings = [])
 * @method static EnterpriseModel offset($value)
 * @method static Builder|\Illuminate\Database\Eloquent\Builder skip($value)
 * @method static Builder|\Illuminate\Database\Eloquent\Builder limit($value)
 * @method static Builder|\Illuminate\Database\Eloquent\Builder take($value)
 * @method static Builder|\Illuminate\Database\Eloquent\Builder forPage($page, $perPage = 15)
 * @method static Builder|\Illuminate\Database\Eloquent\Builder union($query, $all = false)
 * @method static Builder|\Illuminate\Database\Eloquent\Builder unionAll($query)
 * @method static Builder|\Illuminate\Database\Eloquent\Builder lock($value = true)
 * @method static Builder|\Illuminate\Database\Eloquent\Builder lockForUpdate()
 * @method static Builder|\Illuminate\Database\Eloquent\Builder sharedLock()
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
 * @method static Builder|\Illuminate\Database\Eloquent\Builder raw($value)
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
        static::saving(function (EnterpriseModel $row) {
            static::enforceBusinessLogic($row);
        });
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