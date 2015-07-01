<?php namespace DreamFactory\Enterprise\Database\Models;

use DreamFactory\Enterprise\Common\Traits\EntityLookup;
use DreamFactory\Enterprise\Database\Enums\OwnerTypes;
use DreamFactory\Enterprise\Database\Traits\AuthorizedEntity;
use DreamFactory\Enterprise\Storage\Facades\Mounter;
use DreamFactory\Library\Utility\IfSet;
use Illuminate\Database\Query\Builder;

/**
 * mount_t
 *
 * @property int    mount_type_nbr
 * @property string mount_id_text
 * @property string config_text
 *
 * @method static Builder|\Illuminate\Database\Eloquent\Builder byNameOrId(string $mountNameOrId)
 */
class Mount extends AssociativeEntityOwner
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
     * @param array $attributes
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        //  Servers are the owner of mounts for association
        $this->setOwnerType(OwnerTypes::SERVER);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function routeHashes()
    {
        return $this->hasMany(__NAMESPACE__ . '\\RouteHash');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function server()
    {
        return $this->belongsTo(__NAMESPACE__ . '\\Server');
    }

    /**
     * @param \Illuminate\Database\Query\Builder $query
     * @param string                             $mountNameOrId
     *
     * @return Builder
     */
    public function scopeByNameOrId($query, $mountNameOrId)
    {
        return $query->whereRaw(
            'mount_id_text = :mount_id_text OR id = :id',
            [':mount_id_text' => $mountNameOrId, ':id' => $mountNameOrId]
        );
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
     * @param bool   $nameOnly If true, the name of the disk is returned only
     *
     * @return \Illuminate\Contracts\Filesystem\Filesystem|string
     * @throws \DreamFactory\Enterprise\Database\Exceptions\MountException
     */
    public function getFilesystem($path = null, $tag = null, $options = [], $nameOnly = false)
    {
        $_diskName = null;
        $_mountConfig = $this->config_text;

        if (null === ($_diskName = IfSet::get($_mountConfig, 'disk'))) {
            throw new \RuntimeException('No "disk" configured for mount "' . $this->mount_id_text . '".');
        }

        return Mounter::mount($_diskName, array_merge($options, ['prefix' => $path, 'tag' => $tag]));

        //@todo Dynamically configured disk is not yet supported because of a config provider issue

//        $_config = $_info;
//
//        if (!$_diskName && null === ($_diskName = IfSet::get($_config, 'name'))) {
//            $_diskName = 'mount-temp-' . microtime(true);
//        }
//
//        !isset($_config['driver']) && $_config['driver'] = 'local';
//        !isset($_config['path']) && isset($_config['root']) && $_config['path'] = $_config['root'];
//        unset($_config['root'], $_config['name']);
//
//        if ($nameOnly) {
//            return $_diskName;
//        }
//
//        return Mounter::mount($_diskName, array_merge($_config, $options, ['prefix' => $path, 'tag' => $tag]));
    }
}
