<?php namespace DreamFactory\Enterprise\Database\Models;

use Illuminate\Database\Query\Builder;

/**
 * config_t
 *
 * @property string $name_text
 * @property array  $value_text
 *
 * @method static mixed get($name, $default = null)
 * @method static bool put($name, $value)
 * @method static bool|int|null forget($name)
 * @method static Builder|EnterpriseModel byKey($name)
 */
class Config extends EnterpriseModel
{
    //******************************************************************************
    //* Members
    //******************************************************************************

    /**
     * @type string The table name
     */
    protected $table = 'config_t';

    //******************************************************************************
    //* Methods
    //******************************************************************************

    /**
     * @param Builder $query
     * @param string  $name
     *
     * @return static|Builder
     */
    public function scopeByKey($query, $name)
    {
        return $query->where('name_text', $name);
    }

    /**
     * @param string     $name
     * @param mixed|null $default
     *
     * @return mixed
     */
    public static function getValue($name, $default = null)
    {
        if (null === ($_row = static::byKey($name)->first())) {
            return $default;
        }

        return null === $_row->value_text ? null : json_decode($_row->value_text);
    }

    /**
     * @param string $name
     * @param mixed  $value
     *
     * @return bool
     */
    public static function putValue($name, $value)
    {
        if (null === ($_row = static::byKey($name)->first())) {
            $_row = static::create(['name_text' => $name]);
        }

        $_row->value_text = json_encode($value);

        return $_row->save();
    }

    /**
     * @param string $name
     *
     * @return bool|int|null
     */
    public static function forgetValue($name)
    {
        return static::byKey($name)->delete();
    }
}
