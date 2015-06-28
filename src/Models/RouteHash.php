<?php namespace DreamFactory\Enterprise\Database\Models;

use Illuminate\Database\Query\Builder;

/**
 * route_hash_t
 *
 * @property int    $type_nbr
 * @property int    $mount_id
 * @property string $hash_text
 * @property string $actual_path_text
 * @property string expireDate
 *
 * @method static \Illuminate\Database\Eloquent\Builder byHash(string $hash)
 */
class RouteHash extends EnterpriseModel
{
    //******************************************************************************
    //* Members
    //******************************************************************************

    /**
     * @type string The table name
     */
    protected $table = 'route_hash_t';

    //******************************************************************************
    //* Methods
    //******************************************************************************

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function mount()
    {
        return $this->hasOne(__NAMESPACE__ . '\\Mount');
    }

    /**
     * @param Builder $query
     * @param string  $hash
     *
     * @return Builder
     */
    public function scopeByHash($query, $hash)
    {
        return $query->where('hash_text', $hash);
    }
}