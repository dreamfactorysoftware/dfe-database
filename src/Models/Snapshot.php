<?php namespace DreamFactory\Enterprise\Database\Models;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Response;
use League\Flysystem\Filesystem;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

/**
 * snapshot_t
 *
 * @property int    $user_id
 * @property int    $instance_id
 * @property int    $route_hash_id
 * @property string $snapshot_id_text
 * @property int    $public_ind
 * @property string $public_url_text
 * @property string $expire_date
 *
 * @method static \Illuminate\Database\Eloquent\Builder fromHash(string $hash)
 */
class Snapshot extends EnterpriseModel
{
    //******************************************************************************
    //* Members
    //******************************************************************************

    /**
     * @type string The table name
     */
    protected $table = 'snapshot_t';
    /** @inheritdoc */
    protected $casts = [
        'public_ind'    => 'bool',
        'user_id'       => 'integer',
        'instance_id'   => 'integer',
        'route_hash_id' => 'integer',
    ];

    //******************************************************************************
    //* Methods
    //******************************************************************************

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(__NAMESPACE__ . '\\User');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function instance()
    {
        return $this->belongsTo(__NAMESPACE__ . '\\Instance');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function routeHash()
    {
        return $this->belongsTo(__NAMESPACE__ . '\\RouteHash');
    }

    /**
     * @param Builder $query
     * @param string  $hash
     *
     * @return Builder
     */
    public function scopeFromHash($query, $hash)
    {
        /** @type RouteHash $_routeHash */
        if (null !== ($_routeHash = RouteHash::byHash($hash)->first())) {
            return $query->where('route_hash_id', $_routeHash->id);
        }

        return $query;
    }

    /**
     * Hijacks the request and resumes as a file download of a snapshot
     *
     * @param string $hash
     *
     * @return bool|false|string
     * @throws ModelNotFoundException
     */
    public static function downloadFromHash($hash)
    {
        /**
         * @type Filesystem $_fs
         * @type RouteHash  $_routeHash
         */

        try {
            if (null !== ($_routeHash = RouteHash::byHash($hash)->firstOrFail())) {
                $_snapshot = $_routeHash->snapshot ?: static::fromHash($hash)->firstOrFail();

                $_fs = $_snapshot->instance->getSnapshotMount();

                response()->headers->set(
                    'Content-Disposition',
                    response()->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                        $_snapshot->snapshot_id_text . '.zip')
                );

                return $_fs->read($_routeHash->actual_path_text);
            }

            throw new ModelNotFoundException();
        } catch (\Exception $_ex) {
            abort(Response::HTTP_NOT_FOUND);
        }

        //  Death to all ye who enter here...
        return false;
    }

}