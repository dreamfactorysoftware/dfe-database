<?php
namespace DreamFactory\Library\Fabric\Database\Models\Deploy;

use DreamFactory\Library\Fabric\Database\Models\DeployModel;
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
        return
            $this->belongsTo( __NAMESPACE__ . '\\Server' );
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
        return
            Server::where( 'mount_id', $mountId )->count() > 0;
    }

    /**
     * Get the server's base storage mount as a filesystem
     *
     * @return \Illuminate\Contracts\Filesystem\Filesystem
     */
    public function getFilesystem()
    {
        $_disk = IfSet::get( $this->config_text, 'disk' );

        if ( !is_string( $_disk ) )
        {
            \Config::set( 'filesystems.disks.' . $this->mount_id_text, $_disk );
            $_disk = $this->mount_id_text;
        }

        return \Storage::disk( $_disk );
    }
}