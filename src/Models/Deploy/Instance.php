<?php
namespace DreamFactory\Library\Fabric\Database\Models\Deploy;

use DreamFactory\Enterprise\Console\Enums\ConsoleDefaults;
use DreamFactory\Enterprise\Services\Facades\InstanceStorage;
use DreamFactory\Enterprise\Services\Utility\InstanceMetadata;
use DreamFactory\Library\Fabric\Common\Enums\DeactivationReasons;
use DreamFactory\Library\Fabric\Common\Enums\OperationalStates;
use DreamFactory\Library\Fabric\Common\Exceptions\InstanceNotActivatedException;
use DreamFactory\Library\Fabric\Common\Exceptions\InstanceUnlockedException;
use DreamFactory\Library\Fabric\Common\Utility\UniqueId;
use DreamFactory\Library\Fabric\Database\Enums\GuestLocations;
use DreamFactory\Library\Fabric\Database\Models\Auth\User;
use DreamFactory\Library\Fabric\Database\Models\DeployModel;
use DreamFactory\Library\Utility\IfSet;
use Illuminate\Database\Query\Builder;
use Illuminate\Filesystem\FilesystemAdapter;

/**
 * instance_t
 *
 * @property integer            $user_id
 * @property integer            $cluster_id
 * @property integer            $guest_location_nbr
 * @property string             $instance_id_text
 * @property array              $instance_data_text
 * @property int                $app_server_id
 * @property int                $db_server_id
 * @property int                $web_server_id
 * @property string             $db_host_text
 * @property int                $db_port_nbr
 * @property string             $db_name_text
 * @property string             $db_user_text
 * @property string             $db_password_text
 * @property string             $storage_id_text
 * @property string             $request_id_text
 * @property string             $request_date
 * @property integer            $deprovision_ind
 * @property integer            $provision_ind
 * @property integer            $trial_instance_ind
 * @property integer            $state_nbr
 * @property integer            $platform_state_nbr
 * @property integer            $ready_state_nbr
 * @property integer            $environment_id
 * @property integer            $activate_ind
 * @property string             $start_date
 * @property string             $end_date
 * @property string             $terminate_date
 *
 * Relations:
 *
 * @property User               $user
 * @property Server             $appServer
 * @property Server             $dbServer
 * @property Server             $webServer
 *
 * @method static Builder instanceName( string $instanceName )
 * @method static Builder byNameOrId( string $instanceNameOrId )
 * @method static Builder withDbName( string $dbName )
 * @method static Builder onDbServer( int $dbServerId )
 */
class Instance extends DeployModel
{
    //******************************************************************************
    //* Constants
    //******************************************************************************

    /**
     * @type string
     */
    const CHARACTER_PATTERN = '/[^a-zA-Z0-9]/';
    /**
     * @type string
     */
    const HOST_NAME_PATTERN = "/^([a-zA-Z0-9])+$/";

    //******************************************************************************
    //* Members
    //******************************************************************************

    /**
     * @type string The table name
     */
    protected $table = 'instance_t';
    /**
     * @type string
     */
    protected $_privatePathName = ConsoleDefaults::PRIVATE_PATH_NAME;
    /** @inheritdoc */
    protected $casts = [
        'instance_data_text' => 'array',
        'cluster_id'         => 'integer',
        'db_server_id'       => 'integer',
        'web_server_id'      => 'integer',
        'app_server_id'      => 'integer',
        'user_id'            => 'integer',
        'state_nbr'          => 'integer',
        'platform_state_nbr' => 'integer',
        'ready_state_nbr'    => 'integer',
    ];

    //******************************************************************************
    //* Methods
    //******************************************************************************

    /**
     * @param array $attributes
     */
    public function __construct( array $attributes = array() )
    {
        parent::__construct( $attributes );

        $this->_privatePathName = config( 'dfe.provisioning.private-path-base', ConsoleDefaults::PRIVATE_PATH_NAME );
    }

    /**
     * Boot method to wire in our events
     */
    public static function boot()
    {
        parent::boot();

        static::creating(
            function ( $instance /** @var Instance $instance */ )
            {
                $instance->instance_name_text = $instance->sanitizeName( $instance->instance_name_text );
                $instance->checkStorageKey();
                $instance->getStorageMap();

                if ( empty( $instance->instance_data_text ) )
                {
                    $instance->instance_data_text = [];
                }
            }
        );

        static::updating(
            function ( $instance /** @var Instance $instance */ )
            {
                $instance->checkStorageKey();

                if ( empty( $instance->instance_data_text ) )
                {
                    $instance->instance_data_text = [];
                }
            }
        );
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function servers()
    {
        return $this->hasManyThrough( __NAMESPACE__ . '\\InstanceServer', __NAMESPACE__ . '\\Server', 'instance_id', 'server_id' );
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function webServer()
    {
        return $this->hasOne( __NAMESPACE__ . '\\Server', 'id', 'web_server_id' );
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function dbServer()
    {
        return $this->hasOne( __NAMESPACE__ . '\\Server', 'id', 'db_server_id' );
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function appServer()
    {
        return $this->hasOne( __NAMESPACE__ . '\\Server', 'id', 'app_server_id' );
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo( static::AUTH_NAMESPACE . '\\User' );
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function guest()
    {
        return $this->hasOne( __NAMESPACE__ . '\\InstanceGuest', 'id', 'instance_id' );
    }

    /**
     * Update the operational state of this instance
     *
     * @param int $state
     *
     * @return bool|int
     */
    public function updateState( $state )
    {
        $this->state_nbr = $state;

        return $this->update( ['state_nbr' => $state] );
    }

    /**
     * @param int $id
     *
     * @throws InstanceNotActivatedException
     * @return bool|int
     */
    public static function lock( $id )
    {
        /** @type Instance $_instance */
        $_instance = static::findOrFail( $id );

        if ( OperationalStates::ACTIVATED != $_instance->platform_state_nbr )
        {
            throw new InstanceNotActivatedException( 'Instance "' . $id . '" not activated.' );
        }

        return $_instance->update( ['platform_state_nbr' => OperationalStates::LOCKED] );
    }

    /**
     * @param int $id
     *
     * @return bool|int
     * @throws InstanceUnlockedException
     */
    public static function unlock( $id )
    {
        /** @type Instance $_instance */
        $_instance = static::findOrFail( $id );

        if ( OperationalStates::LOCKED != $_instance->platform_state_nbr )
        {
            throw new InstanceUnlockedException( 'Instance "' . $id . '" not locked.' );
        }

        return $_instance->update( ['platform_state_nbr' => OperationalStates::ACTIVATED] );
    }

    /**
     * @param array $schemaInfo
     * @param int   $actionReason
     *
     * @return bool
     */
    public static function deactivate( array $schemaInfo, $actionReason = DeactivationReasons::NON_USE )
    {
        if ( null === ( $_row = Deactivation::instanceId( $schemaInfo['instance']->id )->first() ) )
        {
            //  Not found
            $_row = new Deactivation();
            $_row->user_id = $schemaInfo['instance']->user_id;
            $_row->instance_id = $schemaInfo['instance']->id;
        }

        if ( false === $actionReason )
        {
            //  Set activation date to 7 days from now.
            $_row->activate_by_date = date( 'Y-m-d H-i-s', time() + ( 7 * 86400 ) );
        }
        else
        {
            $_row->action_reason_nbr = $actionReason;
        }

        return $_row->save();
    }

    /**
     * @param Builder $query
     * @param         $userId
     *
     * @return Builder
     */
    public function scopeUserId( $query, $userId )
    {
        if ( !empty( $userId ) )
        {
            return $query->where( 'user_id', '=', $userId );
        }

        return $query;
    }

    /**
     * @param Builder $query
     * @param int     $dbServerId
     *
     * @return Builder
     */
    public function scopeOnDbServer( $query, $dbServerId )
    {
        if ( !empty( $dbServerId ) )
        {
            return $query->where( 'db_server_id', '=', $dbServerId );
        }

        return $query;
    }

    /**
     * @param Builder $query
     * @param int     $stateId
     *
     * @return Builder
     */
    public function scopeWithPlatformState( $query, $stateId )
    {
        if ( null !== $stateId )
        {
            return $query->where( 'platform_state_nbr', '=', $stateId );
        }

        return $query;
    }

    /**
     * @param Builder $query
     * @param string  $dbName The database name to query for
     *
     * @return Builder
     */
    public function scopeWithDbName( $query, $dbName )
    {
        if ( null !== $dbName )
        {
            return $query->where( 'db_name_text', '=', $dbName );
        }

        return $query;
    }

    /**
     * @param Builder $query
     * @param string  $instanceName
     *
     * @return Builder
     */
    public function scopeInstanceName( $query, $instanceName )
    {
        return $query->where( 'instance_name_text', '=', $instanceName );
    }

    /**
     * @param Builder    $query
     * @param int|string $instanceNameOrId
     *
     * @return Builder
     */
    public function scopeByNameOrId( $query, $instanceNameOrId )
    {
        return $query->whereRaw(
            'instance_name_text = :instance_name_text OR instance_id_text = :instance_id_text or id = :id',
            [':instance_name_text' => $instanceNameOrId, ':instance_id_text' => $instanceNameOrId, ':id' => $instanceNameOrId]
        );
    }

    /**
     * @param Builder $query
     * @param string  $instanceName
     *
     * @return Builder
     */
    public function scopeLikeInstanceName( $query, $instanceName )
    {
        return $query->where( 'instance_name_text', 'like', '%' . $instanceName . '%' );
    }

    /**
     * @return string
     */
    public function getStoragePath()
    {
        return InstanceStorage::getStoragePath( $this );
    }

    /**
     * @return string
     */
    public function getSnapshotPath()
    {
        return InstanceStorage::getSnapshotPath( $this );
    }

    /**
     * We want the private path of the instance to point to the user's area. Instances have no "private path" per se.
     *
     * @return mixed
     */
    public function getPrivatePath()
    {
        return InstanceStorage::getPrivatePath( $this );
    }

    /**
     * Return the instance owner's private path
     *
     * @return mixed
     */
    public function getOwnerPrivatePath()
    {
        return InstanceStorage::getOwnerPrivatePath( $this );
    }

    /**
     * Ensures that a storage key has been assigned
     */
    public function checkStorageKey()
    {
        if ( empty( $this->storage_id_text ) )
        {
            $this->storage_id_text = UniqueId::generate( __CLASS__ );
            $this->getStorageMap();
        }
    }

    /**
     * @param int|string|Server $serverId
     *
     * @return bool
     */
    public function removeFromServer( $serverId )
    {
        $_server = ( $serverId instanceof Server ) ? $serverId : $this->_getServer( $serverId );

        //  Do we belong to a server?
        if ( $this->belongsToServer( $_server->id ) )
        {
            return
                1 == InstanceServer::whereRaw(
                    'server_id = :server_id AND instance_id = :instance_id',
                    array(':server_id' => $_server->id, ':instance_id' => $this->id)
                )->delete();
        }

        //  Not currently assigned...
        return false;
    }

    /**
     * @param int|string $serverId
     *
     * @return bool
     */
    public function addToServer( $serverId )
    {
        //  This will fail if $serverId is bogus
        $this->removeFromServer( $_server = $this->_getServer( $serverId ) );

        return 1 == InstanceServer::insert( ['server_id' => $_server->id, 'instance_id' => $this->id] );
    }

    /**
     * @param int|string $serverId
     *
     * @return bool True if this instance
     */
    public function belongsToServer( $serverId )
    {
        $_server = $this->_getServer( $serverId );

        /** @noinspection PhpUndefinedMethodInspection */

        return 0 != InstanceServer::whereRaw(
            'server_id = :server_id AND instance_id = :instance_id',
            [
                ':server_id'   => $_server->id,
                ':instance_id' => $this->id
            ]
        )->count();
    }

    /**
     * @param int|string $serverId
     *
     * @return Server
     */
    protected function _getServer( $serverId )
    {
        if ( null === ( $_server = Server::byNameOrId( $serverId )->first() ) )
        {
            throw new \InvalidArgumentException( 'The server id "' . $serverId . '" is invalid.' );
        }

        return $_server;
    }

    /**
     * @param string $name
     *
     * @return bool|string Returns the sanitized name or FALSE if not available
     */
    public static function isNameAvailable( $name )
    {
        if ( false === ( $_sanitized = static::sanitizeName( $name ) ) )
        {
            return false;
        }

        return ( 0 == static::byNameOrId( $_sanitized )->count() ? $_sanitized : false );
    }

    /**
     * Ensures the instance name meets quality standards
     *
     * @param string $name
     *
     * @return string
     */
    public static function sanitizeName( $name )
    {
        static $_unavailableNames = null;

        //	This replaces any disallowed characters with dashes
        $_clean = str_replace(
            [' ', '_'],
            '-',
            trim( str_replace( '--', '-', preg_replace( static::CHARACTER_PATTERN, '-', $name ) ), ' -_' )
        );

        if ( null === $_unavailableNames && function_exists( 'config' ) )
        {
            $_unavailableNames = config( 'dfe.forbidden-names', array() );

            if ( !is_array( $_unavailableNames ) || empty( $_unavailableNames ) )
            {
                $_unavailableNames = [];
            }
        }

        if ( in_array( $_clean, $_unavailableNames ) )
        {
            \Log::error( 'Attempt to register forbidden instance name: ' . $name . ' => ' . $_clean );

            return false;
        }

        //	Check host name
        if ( preg_match( static::HOST_NAME_PATTERN, $_clean ) )
        {
            \Log::notice( 'Non-standard instance name "' . $_clean . '" being provisioned' );
        }

        return $_clean;
    }

    /**
     * Retrieves an instances' metadata which is stored in the instance_data_text column
     *
     * @param bool   $sync If true, the current information will be updated into the instance row
     * @param string $key  If specified, return only this metadata item, otherwise all
     *
     * @return array
     */
    public function getMetadata( $sync = true, $key = null )
    {
        if ( !$this->user )
        {
            throw new \RuntimeException( 'The user for instance "' . $this->instance_id_text . '" was not found.' );
        }

        $_data = $this->instance_data_text;

        if ( empty( $_data ) || !is_array( $_data ) )
        {
            $_data = [];
        }

        if ( !array_key_exists( 'metadata', $_data ) )
        {
            $_data['metadata'] = [];
        }

        $_data['metadata'] = array_merge(
            $_data['metadata'],
            [
                'instance-id'         => $this->instance_id_text,
                'cluster-id'          => $this->cluster_id,
                'db-server-id'        => $this->db_server_id,
                'app-server-id'       => $this->app_server_id,
                'web-server-id'       => $this->web_server_id,
                'owner-id'            => $this->user_id,
                'owner-email-address' => $this->user->email_addr_text,
                'storage-key'         => $this->storage_id_text,
                'owner-storage-key'   => $this->user->storage_id_text,
                'storage-map'         => $this->getStorageMap(),
            ]
        );

        !$key && $sync && $this->update( ['instance_data_text' => $_data] );

        return $key ? IfSet::get( $_data['metadata'], $key ) : $_data['metadata'];
    }

    /**
     * Returns the ROOT storage path for all instances with optional appendage
     *
     * @param string $append
     *
     * @return mixed|string
     */
    public function getRootStoragePath( $append = null )
    {
        static $_cache = [];

        $_ck = hash( 'sha256', 'rsp.' . $this->id . ( $append ? DIRECTORY_SEPARATOR . $append : $append ) );

        if ( null === ( $_path = IfSet::get( $_cache, $_ck ) ) )
        {
            switch ( $this->guest_location_nbr )
            {
                case GuestLocations::LOCAL:
                    $_path = storage_path( $append );
                    break;

                default:
                    $_map = $this->getStorageMap();
                    $_path =
                        implode( DIRECTORY_SEPARATOR, [$_map['zone'], $_map['partition'], $_map['root-hash']] ) .
                        ( $append ? DIRECTORY_SEPARATOR . ltrim( $append, ' ' . DIRECTORY_SEPARATOR ) : null );
                    break;
            }

            $_cache[$_ck] = $_path;
        }

        return $_path;
    }

    /**
     * @return array
     */
    public function getStorageMap()
    {
        if ( !isset( $this->instance_data_text ) || null === ( $_map = IfSet::get( $this->instance_data_text, 'storage-map' ) ) )
        {
            if ( empty( $this->instance_data_text ) )
            {
                $this->instance_data_text = [];
            }

            //  Non-hosted has no structure, just storage
            if ( GuestLocations::LOCAL == $this->guest_location_nbr || 'localhost' == $this->db_host_text )
            {
                $_map = [
                    'zone'      => null,
                    'partition' => null,
                    'root-hash' => null,
                ];
            }
            else
            {
                $_rootHash = $this->user->getHash();
                $_partition = substr( $_rootHash, 0, 2 );

                $_zone = null;

                switch ( config( 'dfe.provisioning.storage-zone-type' ) )
                {
                    case 'dynamic':
                        switch ( $this->guest_location_nbr )
                        {
                            case GuestLocations::AMAZON_EC2:
                            case GuestLocations::DFE_CLUSTER:
                                if ( file_exists( '/usr/bin/ec2metadata' ) )
                                {
                                    $_zone = str_replace( 'availability-zone: ', null, `/usr/bin/ec2metadata | grep zone` );
                                }
                                break;
                        }
                        break;

                    case 'static':
                        $_zone = config( 'dfe.provisioning.static-zone-name' );
                        break;
                }

                if ( empty( $_zone ) || empty( $_partition ) )
                {
                    throw new \RuntimeException( 'Zone and/or partition unknown. Cannot provision storage.' );
                }

                $_map = [
                    'zone'      => $_zone,
                    'partition' => $_partition,
                    'root-hash' => $_rootHash,
                ];
            }

            $this->instance_data_text = array_merge( $this->instance_data_text, ['storage-map' => $_map] );
        }

        return $_map;
    }

    /**
     * Returns the relative root directory of this instance's storage
     *
     * @param string $path
     * @param string $tag
     *
     * @return FilesystemAdapter
     */
    public function getRootStorageMount( $path = null, $tag = null )
    {
        return InstanceStorage::getRootStorageMount( $this, $path, $tag );
    }

    /**
     * Returns the relative root directory of this instance's storage
     *
     * @param string $append
     * @param string $tag
     *
     * @return FilesystemAdapter
     */
    public function getSnapshotMount( $append = null, $tag = null )
    {
        return InstanceStorage::getSnapshotMount( $this, $tag );
    }

    /**
     * @param string $tag
     *
     * @return FilesystemAdapter
     */
    public function getStorageMount( $tag = null )
    {
        return InstanceStorage::getStorageMount( $this, $tag );
    }

    /**
     * @param string $tag
     *
     * @return \Illuminate\Filesystem\FilesystemAdapter
     */
    public function getPrivateStorageMount( $tag = null )
    {
        return InstanceStorage::getPrivateStorageMount( $this, $tag );
    }

    /**
     * @param string $tag
     *
     * @return FilesystemAdapter
     */
    public function getOwnerPrivateStorageMount( $tag = null )
    {
        return InstanceStorage::getOwnerPrivateStorageMount( $this, $tag );
    }

    /**
     * Returns an instance of InstanceMetadata
     *
     * @return array
     */
    public function getInstanceMetadata()
    {
        return InstanceMetadata::createFromInstance( $this );
    }

}
