<?php
namespace DreamFactory\Library\Fabric\Database\Models\Deploy;

use DreamFactory\Enterprise\Common\Traits\EntityLookup;
use DreamFactory\Enterprise\Services\Facades\Mounter;
use DreamFactory\Library\Fabric\Database\Exceptions\MountException;
use DreamFactory\Library\Fabric\Database\Models\DeployModel;
use DreamFactory\Library\Fabric\Database\Traits\AuthorizedEntity;
use DreamFactory\Library\Utility\IfSet;
use Illuminate\Database\Query\Builder;

/**
 * mount_t
 *
 * @property int    mount_type_nbr
 * @property string mount_id_text
 * @property string config_text
 *
 * @method static Builder byNameOrId( string $mountNameOrId )
 */
class Mount extends DeployModel
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
    protected $casts = [
        'config_text' => 'array',
    ];

    //******************************************************************************
    //* Methods
    //******************************************************************************

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function server()
    {
        return $this->belongsTo( __NAMESPACE__ . '\\Server', 'id', 'mount_id' );
    }

    /**
     * @param \Illuminate\Database\Query\Builder $query
     * @param string                             $mountNameOrId
     *
     * @return Builder
     */
    public function scopeByNameOrId( $query, $mountNameOrId )
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
    public function isInUse( $mountId )
    {
        /** @noinspection PhpUndefinedMethodInspection */
        return
            Server::where( 'mount_id', $mountId )->count() > 0;
    }

    /**
     * Returns this mount as a filesystem
     *
     * @param string $path
     * @param string $tag
     * @param array  $options
     * @param bool   $nameOnly If true, the name of the disk is returned only
     *
     * @return \Illuminate\Contracts\Filesystem\Filesystem|\Illuminate\Filesystem\Filesystem|string
     * @throws \DreamFactory\Library\Fabric\Database\Exceptions\MountException
     */
    public function getFilesystem( $path = null, $tag = null, $options = [], $nameOnly = false )
    {
        if ( null === ( $_disk = IfSet::get( $this->config_text, 'disk' ) ) )
        {
            throw new \RuntimeException( 'No "disk" configured for mount "' . $this->mount_id_text . '".' );
        }

        if ( is_array( $_disk ) )
        {
            $_config = $_disk;

            if ( null === ( $_disk = IfSet::get( $_config, 'name' ) ) )
            {
                throw new MountException( 'Cannot mount dynamic file system when there is no "name" setting.' );
            }

            unset( $_config['name'] );

            \Config::set( 'filesystems.disks.' . $_disk, $_config );
        }

        if ( $nameOnly )
        {
            return $_disk;
        }

        return Mounter::mount( $_disk, array_merge( $options, ['prefix' => $path, 'tag' => $tag] ) );
    }
}
