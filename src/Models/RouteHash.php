<?php namespace DreamFactory\Enterprise\Database\Models;

use Illuminate\Database\Query\Builder;

/**
 * route_hash_t
 *
 * @property int    $type_nbr
 * @property int    $mount_id
 * @property int    $snapshot_id
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

    /** @inheritdoc */
    protected $table = 'route_hash_t';
    /** @inheritdoc */
    protected $casts = [
        'type_nbr'    => 'integer',
        'snapshot_id' => 'integer',
        'mount_id'    => 'integer',
    ];

    //******************************************************************************
    //* Methods
    //******************************************************************************

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo|Mount
     */
    public function mount()
    {
        return $this->belongsTo(static::MODEL_NAMESPACE . 'Mount');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo|Snapshot
     */
    public function snapshot()
    {
        return $this->belongsTo(static::MODEL_NAMESPACE . 'Snapshot');
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