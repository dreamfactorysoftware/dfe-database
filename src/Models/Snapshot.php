<?php namespace DreamFactory\Enterprise\Database\Models;

use DreamFactory\Enterprise\Common\Traits\Archivist;
use DreamFactory\Enterprise\Common\Traits\EntityLookup;
use DreamFactory\Library\Utility\Disk;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;
use League\Flysystem\ZipArchive\ZipArchiveAdapter;

/**
 * snapshot_t
 *
 * @property int    user_id
 * @property int    instance_id
 * @property int    route_hash_id
 * @property string snapshot_id_text
 * @property int    public_ind
 * @property string public_url_text
 * @property string expire_date
 *
 * @property User   user
 *
 * @method static Builder|EloquentBuilder byUserId($userId)
 * @method static Builder|EloquentBuilder fromHash($hash)
 * @method static Builder|EloquentBuilder bySnapshotId($snapshotId)
 */
class Snapshot extends EnterpriseModel
{
    //******************************************************************************
    //* Traits
    //******************************************************************************

    use Archivist, EntityLookup;

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
     * @return \Illuminate\Database\Eloquent\Relations\HasOne|User
     */
    public function user()
    {
        return $this->hasOne(static::MODEL_NAMESPACE . 'User', 'id', 'user_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne|Instance
     */
    public function instance()
    {
        return $this->hasOne(static::MODEL_NAMESPACE . 'Instance', 'id', 'instance_id');
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
     * @param Builder $query
     * @param int     $userId
     *
     * @return Builder
     */
    public function scopeByUserId($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * @param Builder    $query
     * @param string|int $snapshotId
     *
     * @return Builder
     */
    public function scopeBySnapshotId($query, $snapshotId)
    {
        return $query->whereRaw('id = :id OR snapshot_id_text = :snapshot_id_text',
            ['id' => $snapshotId, 'snapshot_id_text' => $snapshotId]);
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
            if (null !== ($_routeHash = RouteHash::byHash($hash)->with(['snapshot'])->firstOrFail())) {
                //  Look up the snapshot and get an instance of the file system
                $_snapshot = $_routeHash->snapshot ?: static::fromHash($hash)->with(['instance'])->firstOrFail();

                try {
                    $_instance = static::_locateInstance($_snapshot->instance_id);
                } catch (ModelNotFoundException $_ex) {
                    throw new ModelNotFoundException('Instance not found for snapshot "' .
                        $_snapshot->snapshot_id_text .
                        '"');
                }

                if (null === ($_fs = $_instance->getSnapshotMount())) {
                    throw new ModelNotFoundException('Snapshot storage area is not available.');
                }

                //  Get some work space to download the snapshot
                $_workPath = static::getWorkPath('snapshot-download', true);
                $_fsWork = new Filesystem(new Local($_workPath));
                $_tempFile = $_routeHash->actual_path_text;

                //  Delete any file with the same name...
                file_exists($_workPath . DIRECTORY_SEPARATOR . $_tempFile) &&
                @unlink($_workPath . DIRECTORY_SEPARATOR . $_tempFile);

                //  Download the snapshot to local temp
                static::writeStream($_fsWork, $_fs->readStream($_tempFile), $_tempFile);

                //  Download the local file to client
                /** @noinspection PhpUndefinedMethodInspection */

                return response()->download($_workPath . DIRECTORY_SEPARATOR . $_tempFile, $_tempFile);
            }

            throw new ModelNotFoundException();
        } catch (\Exception $_ex) {
            Log::error('route hash "' . $hash . '" not found: ' . $_ex->getMessage());

            abort(Response::HTTP_NOT_FOUND);
        }

        //  Death to all ye who enter here...
        return false;
    }

    /**
     * Returns the mount to the snapshot itself if it exists here otherwise returns mount to user's snapshot path
     *
     * @return \League\Flysystem\Filesystem|null
     */
    public function getMount()
    {
        if ($this->user) {
            if (!is_dir($_path = $this->user->getSnapshotPath())) {
                return $this->user->getSnapshotMount();
            }

            if (file_exists($_file = Disk::segment([$_path, $this->snapshot_id_text . '.snapshot.zip'], true))) {
                return new Filesystem(new ZipArchiveAdapter($_file));
            }
        }

        return null;
    }
}
