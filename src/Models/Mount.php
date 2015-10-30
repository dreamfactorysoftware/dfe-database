<?php namespace DreamFactory\Enterprise\Database\Models;

use DreamFactory\Enterprise\Common\Traits\EntityLookup;
use DreamFactory\Enterprise\Database\Enums\OwnerTypes;
use DreamFactory\Enterprise\Database\Traits\AuthorizedEntity;
use DreamFactory\Enterprise\Storage\Facades\Mounter;
use Illuminate\Database\Query\Builder;

/**
 * mount_t
 *
 * @property int    mount_type_nbr
 * @property string mount_id_text
 * @property string config_text
 *
 * @method static Builder|\Illuminate\Database\Eloquent\Builder byNameOrId($mountNameOrId)
 */
class Mount extends EnterpriseModel
{
    //******************************************************************************
    //* Traits
    //******************************************************************************

    use EntityLookup, AuthorizedEntity;

    //******************************************************************************
    //* Members
    //******************************************************************************

    /** @inheritdoc */
    protected $table = 'mount_t';
    /** @inheritdoc */
    protected $casts = ['config_text' => 'array',];

    //******************************************************************************
    //* Methods
    //******************************************************************************

    /**
     * @return int The owner of this entity
     */
    public function getMorphClass()
    {
        return OwnerTypes::SERVER;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany|RouteHash[]
     */
    public function routeHashes()
    {
        return $this->belongsToMany(static::MODEL_NAMESPACE . 'RouteHash', 'id', 'mount_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany|Server[]
     */
    public function servers()
    {
        return $this->belongsToMany(static::MODEL_NAMESPACE . 'Server', 'id', 'mount_id');
    }

    /**
     * @param \Illuminate\Database\Query\Builder $query
     * @param string                             $mountNameOrId
     *
     * @return Builder
     */
    public function scopeByNameOrId($query, $mountNameOrId)
    {
        return $query->whereRaw('mount_id_text = :mount_id_text OR id = :id',
            [':mount_id_text' => $mountNameOrId, ':id' => $mountNameOrId]);
    }

    /**
     * Determines if a mount is currently assigned to a server
     *
     * @param int $mountId
     *
     * @return bool
     */
    public function isInUse($mountId)
    {
        /** @noinspection PhpUndefinedMethodInspection */
        return Server::where('mount_id', $mountId)->count() > 0;
    }

    /**
     * Returns this mount as a filesystem
     *
     * @param string $path
     * @param string $tag
     * @param array  $options
     *
     * @return \Illuminate\Contracts\Filesystem\Filesystem|string
     * @throws \DreamFactory\Enterprise\Database\Exceptions\MountException
     */
    public function getFilesystem($path = null, $tag = null, $options = [])
    {
        $_diskName = null;
        $_mountConfig = $this->config_text;

        if (null === ($_diskName = array_get($_mountConfig, 'disk'))) {
            throw new \RuntimeException('No "disk" configured for mount "' . $this->mount_id_text . '".');
        }

        return Mounter::mount($_diskName, array_merge($options, ['prefix' => $path, 'tag' => $tag]));
    }
}
