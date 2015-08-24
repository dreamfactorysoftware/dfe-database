<?php namespace DreamFactory\Enterprise\Database\Traits;

/**
 * Stashes data into an array column, stored as JSON
 */
trait DataStash
{
    //******************************************************************************
    //* Methods
    //******************************************************************************

    /**
     * @type string|null The column to use as the stash
     */
    protected $dataStashColumn = null;

    //******************************************************************************
    //* Methods
    //******************************************************************************

    /**
     * @param string|null $key
     * @param mixed       $default
     * @param string|null $column The column name if not $this->dataStashColumn
     *
     * @return mixed
     */
    protected function stashGetValue($key, $default = null, $column = null)
    {
        return array_get($this->stashAsArray($column), $key, $default);
    }

    /**
     * @param string      $key    The key to stash
     * @param mixed       $value  The value for $key
     * @param string|null $column The column name if not $this->dataStashColumn
     *
     * @return $this
     */
    protected function stashSetValue($key, $value = null, $column = null)
    {
        $_data = $this->stashAsArray($column);
        $_data[$key] = $value;

        return $this->stashSetColumn($_data);
    }

    /**
     * Sets a value at $key. If $key exists, it will be turned into an array
     * (if not already) and $value will be pushed onto the array.
     *
     * @param string      $key    The key to stash
     * @param mixed       $value  The value for $key
     * @param string|null $column The column name if not $this->dataStashColumn
     *
     * @return $this
     */
    protected function stashAddValue($key, $value = null, $column = null)
    {
        $_data = $this->stashAsArray($column);

        if (empty($_data[$key])) {
            $_data[$key] = [];
        }

        if (!is_array($_data[$key])) {
            $_data[$key] = [$_data[$key]];
        }

        $_data[$key][] = $value;

        return $this->stashSetColumn($_data);
    }

    /**
     * @param string|null $column The column name if not $this->dataStashColumn
     *
     * @return array
     */
    protected function stashAsArray($column = null)
    {
        $_data = $this->{$this->stashGetColumnName($column)};

        return empty($_data) ? [] : $_data;
    }

    /**
     * @param array       $array  The data to store in the column
     * @param string|null $column The column name if not $this->dataStashColumn
     *
     * @return $this
     */
    protected function stashSetColumn(array $array, $column = null)
    {
        $this->{$this->stashGetColumnName($column)} = $array;

        return $this;
    }

    /**
     * @param string|null $column The column name if not $this->dataStashColumn
     *
     * @return null|string
     */
    protected function stashGetColumnName($column = null)
    {
        return $column ?: $this->dataStashColumn;
    }
}
