<?php
namespace DreamFactory\Library\Fabric\Database\Models\Deploy;

use DreamFactory\Library\Fabric\Common\Enums\DeactivationReasons;
use DreamFactory\Library\Fabric\Common\Enums\OperationalStates;
use DreamFactory\Library\Fabric\Common\Exceptions\InstanceNotActivatedException;
use DreamFactory\Library\Fabric\Common\Exceptions\InstanceUnlockedException;
use DreamFactory\Library\Fabric\Common\Utility\UniqueId;
use DreamFactory\Library\Fabric\Database\Models\Auth\User;
use DreamFactory\Library\Fabric\Database\Models\DeployModel;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Log;

/**
 * instance_t
 *
 * @property integer            $user_id
 * @property integer            $cluster_id
 * @property integer            $vendor_id
 * @property integer            $vendor_image_id
 * @property integer            $vendor_credentials_id
 * @property integer            $guest_location_nbr
 * @property string             $instance_id_text
 * @property int                $app_server_id
 * @property int                $db_server_id
 * @property int                $web_server_id
 * @property string             $db_host_text
 * @property int                $db_port_nbr
 * @property string             $db_name_text
 * @property string             $db_user_text
 * @property string             $db_password_text
 * @property string             $storage_id_text
 * @property integer            $flavor_nbr
 * @property string             $base_image_text
 * @property string             $instance_name_text
 * @property string             $region_text
 * @property string             $availability_zone_text
 * @property string             $security_group_text
 * @property string             $ssh_key_text
 * @property integer            $root_device_type_nbr
 * @property string             $public_host_text
 * @property string             $public_ip_text
 * @property string             $private_host_text
 * @property string             $private_ip_text
 * @property string             $request_id_text
 * @property string             $request_date
 * @property integer            $deprovision_ind
 * @property integer            $provision_ind
 * @property integer            $trial_instance_ind
 * @property integer            $state_nbr
 * @property integer            $platform_state_nbr
 * @property integer            $ready_state_nbr
 * @property integer            $vendor_state_nbr
 * @property string             $vendor_state_text
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

    //******************************************************************************
    //* Methods
    //******************************************************************************

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
            }
        );

        static::updating(
            function ( $instance /** @var Instance $instance */ )
            {
                $instance->checkStorageKey();
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
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo( static::AUTH_NAMESPACE . '\\User' );
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
     * @param Builder $query
     * @param string  $instanceNameOrId
     *
     * @return Builder
     */
    public function scopeByNameOrId( $query, $instanceNameOrId )
    {
        return $query->whereRaw(
            'instance_name_text = :instance_name_text OR instance_id_text = :instance_id_text',
            [':instance_name_text' => $instanceNameOrId, ':instance_id_text' => $instanceNameOrId]
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
        return str_ireplace( static::FABRIC_STORAGE_KEY, $this->storage_id_text, static::FABRIC_BASE_STORAGE_PATH );
    }

    /**
     * @return string
     */
    public function getSnapshotPath()
    {
        return $this->getStoragePath() . DIRECTORY_SEPARATOR . '.private' . DIRECTORY_SEPARATOR . 'snapshots';
    }

    /**
     * We want the private path of the instance to point to the user's area. Instances have no "private path" per se.
     *
     * @return mixed
     */
    public function getPrivatePath()
    {
        return $this->getStoragePath() . DIRECTORY_SEPARATOR . '.private';
    }

    /**
     * Ensures that a storage key has been assigned
     */
    public function checkStorageKey()
    {
        if ( empty( $this->storage_id_text ) )
        {
            $this->storage_id_text = UniqueId::generate( __CLASS__ );
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
            Log::error( 'Attempt to register forbidden instance name: ' . $name . ' => ' . $_clean );

            return false;
        }

        //	Check host name
        if ( preg_match( static::HOST_NAME_PATTERN, $_clean ) )
        {
            Log::notice( 'Non-standard instance name "' . $_clean . '" being provisioned' );
        }

        return $_clean;
    }

    /**
      * Retrieves an instances' metadata
      *
      * @return array
      */
     public function getMetadata()
     {
         if ( !$this->user )
         {
             throw new \RuntimeException( 'The user for instance "' . $this->instance_id_text . '" was not found.' );
         }
 
         $_response = [
             'instance-id'         => $this->id,
             'cluster-id'          => $this->cluster_id,
             'db-server-id'        => $this->db_server_id,
             'app-server-id'       => $this->app_server_id,
             'web-server-id'       => $this->web_server_id,
             'owner-id'            => $this->user_id,
             'owner-email-address' => $this->user->email_addr_text,
         ];
 
         return $_response;
     }
}
