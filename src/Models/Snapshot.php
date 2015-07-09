<?php namespace DreamFactory\Enterprise\Database\Models;

use DreamFactory\Enterprise\Common\Traits\Archivist;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Response;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;

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
    //* Traits
    //******************************************************************************

    use Archivist;

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
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo|User
     */
    public function user()
    {
        return $this->belongsTo(static::MODEL_NAMESPACE . 'User', 'id', 'user_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo|Instance
     */
    public function instance()
    {
        return $this->belongsTo(static::MODEL_NAMESPACE . 'Instance', 'id', 'instance_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne|RouteHash
     */
    public function routeHash()
    {
        return $this->hasOne(static::MODEL_NAMESPACE . 'RouteHash', 'id', 'route_hash_id');
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
                //  Look up the snapshot and get an instance of the file system
                $_snapshot = $_routeHash->snapshot ?: static::fromHash($hash)->firstOrFail();
                $_fs = $_snapshot->instance->getSnapshotMount();

                //  Get some work space to download the snapshot
                $_workPath = static::getWorkPath('snapshot-download', true);
                $_fsWork = new Filesystem(new Local($_workPath));
                $_tempFile = $_routeHash->actual_path_text;

                //  Delete any file with the same name...
                file_exists($_workPath . DIRECTORY_SEPARATOR . $_tempFile) && @unlink($_workPath . DIRECTORY_SEPARATOR . $_tempFile);

                //  Download the snapshot to local temp
                static::writeStream($_fsWork, $_fs->readStream($_tempFile), $_tempFile);

                //  Download the local file to client
                /** @noinspection PhpUndefinedMethodInspection */

                return response()->download($_workPath . DIRECTORY_SEPARATOR . $_tempFile, $_tempFile);
            }

            throw new ModelNotFoundException();
        } catch (\Exception $_ex) {
            \Log::error('error retrieving download location: ' . $_ex->getMessage());

            abort(Response::HTTP_NOT_FOUND);
        }

        //  Death to all ye who enter here...
        return false;
    }

}