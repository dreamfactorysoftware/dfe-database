<?php namespace DreamFactory\Enterprise\Database\Models;

use DreamFactory\Enterprise\Common\Enums\EnterpriseDefaults;
use DreamFactory\Enterprise\Common\Enums\EnterprisePaths;
use DreamFactory\Enterprise\Common\Enums\OperationalStates;
use DreamFactory\Enterprise\Common\Facades\InstanceStorage;
use DreamFactory\Enterprise\Common\Support\Metadata;
use DreamFactory\Enterprise\Common\Traits\EntityLookup;
use DreamFactory\Enterprise\Common\Traits\StaticComponentLookup;
use DreamFactory\Enterprise\Common\Utility\UniqueId;
use DreamFactory\Enterprise\Database\Enums\DeactivationReasons;
use DreamFactory\Enterprise\Database\Enums\GuestLocations;
use DreamFactory\Enterprise\Database\Enums\OwnerTypes;
use DreamFactory\Enterprise\Database\Exceptions\InstanceNotActivatedException;
use DreamFactory\Enterprise\Database\Exceptions\InstanceUnlockedException;
use DreamFactory\Enterprise\Database\Traits\AuthorizedEntity;
use DreamFactory\Enterprise\Database\Traits\DataStash;
use Illuminate\Database\Query\Builder;
use League\Flysystem\Filesystem;

/**
 * instance_t
 *
 * @property integer $user_id
 * @property integer $cluster_id
 * @property integer $guest_location_nbr
 * @property string  $instance_id_text
 * @property array   $instance_data_text
 * @property int     $app_server_id
 * @property int     $db_server_id
 * @property int     $web_server_id
 * @property string  $db_host_text
 * @property int     $db_port_nbr
 * @property string  $db_name_text
 * @property string  $db_user_text
 * @property string  $db_password_text
 * @property string  $storage_id_text
 * @property string  $request_id_text
 * @property string  $request_date
 * @property integer $deprovision_ind
 * @property integer $provision_ind
 * @property integer $trial_instance_ind
 * @property integer $state_nbr
 * @property integer $platform_state_nbr
 * @property integer $ready_state_nbr
 * @property integer $environment_id
 * @property integer $activate_ind
 * @property string  $start_date
 * @property string  $end_date
 * @property string  $terminate_date
 *
 * Relations:
 *
 * @property User    $user
 * @property Server  $appServer
 * @property Server  $dbServer
 * @property Server  $webServer
 *
 * @method static Builder instanceName(string $instanceName)
 * @method static Builder|\Illuminate\Database\Eloquent\Builder byNameOrId(string $instanceNameOrId)
 * @method static Builder userId(int $userId)
 * @method static Builder withDbName(string $dbName)
 * @method static Builder onDbServer(int $dbServerId)
 */
class Instance extends AssociativeEntityOwner
{
    //******************************************************************************
    //* Traits
    //******************************************************************************

    use EntityLookup, AuthorizedEntity, StaticComponentLookup, DataStash;

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
        'provision_ind'      => 'boolean',
        'deprovision_ind'    => 'boolean',
        'trial_instance_ind' => 'boolean',
    ];
    /**
     * @type array The template for metadata stored in
     */
    protected static $_metadataTemplate = ['storage-map' => [], 'paths' => [], 'db' => [], 'env' => [],];
    /**
     * @type string
     */
    protected $_privatePathName = '.private';

    //******************************************************************************
    //* Methods
    //******************************************************************************

    /**
     * @param array $attributes
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->_privatePathName = config('dfe.provisioning.private-path-name', '.private');

        //  Servers are the owner of instances for association
        $this->setOwnerType(OwnerTypes::SERVER);
    }

    /**
     * Boot method to wire in our events
     */
    public static function boot()
    {
        parent::boot();

        static::creating(
            function ($instance/** @var Instance $instance */) {
                $instance->instance_name_text = $instance->sanitizeName($instance->instance_name_text);

                $instance->checkStorageKey();
                $instance->refreshMetadata();
            }
        );

        static::updating(
            function ($instance/** @var Instance $instance */) {
                $instance->checkStorageKey();
                $instance->refreshMetadata();
            }
        );

        static::deleted(function ($instance/** @var Instance $instance */) {
            AppKey::where('owner_id', $instance->id)->where('owner_type_nbr', OwnerTypes::INSTANCE)->delete();
        });
    }

    /**
     * @return Cluster
     */
    public function cluster()
    {
        return $this->belongsTo(__NAMESPACE__ . '\\Cluster');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     */
    public function servers()
    {
        return $this->hasManyThrough(__NAMESPACE__ . '\\InstanceServer',
            __NAMESPACE__ . '\\Server',
            'instance_id',
            'server_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function webServer()
    {
        return $this->hasOne(__NAMESPACE__ . '\\Server', 'id', 'web_server_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function dbServer()
    {
        return $this->hasOne(__NAMESPACE__ . '\\Server', 'id', 'db_server_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function appServer()
    {
        return $this->hasOne(__NAMESPACE__ . '\\Server', 'id', 'app_server_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(__NAMESPACE__ . '\\User');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function guest()
    {
        return $this->hasOne(__NAMESPACE__ . '\\InstanceGuest');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function snapshots()
    {
        return $this->hasMany(__NAMESPACE__ . '\\Snapshot');
    }

    /**
     * Update the operational state of this instance
     *
     * @param int $state
     *
     * @return bool|int
     */
    public function updateState($state)
    {
        return $this->update(['state_nbr' => $state]);
    }

    /**
     * @param int $id
     *
     * @throws InstanceNotActivatedException
     * @return bool|int
     */
    public static function lock($id)
    {
        /** @type Instance $_instance */
        $_instance = static::findOrFail($id);

        if (OperationalStates::ACTIVATED != $_instance->platform_state_nbr) {
            throw new InstanceNotActivatedException('Instance "' . $id . '" not activated.');
        }

        return $_instance->update(['platform_state_nbr' => OperationalStates::LOCKED]);
    }

    /**
     * @param int $id
     *
     * @return bool|int
     * @throws InstanceUnlockedException
     */
    public static function unlock($id)
    {
        /** @type Instance $_instance */
        $_instance = static::findOrFail($id);

        if (OperationalStates::LOCKED != $_instance->platform_state_nbr) {
            throw new InstanceUnlockedException('Instance "' . $id . '" not locked.');
        }

        return $_instance->update(['platform_state_nbr' => OperationalStates::ACTIVATED]);
    }

    /**
     * @param array $schemaInfo
     * @param int   $actionReason
     *
     * @return bool
     */
    public static function deactivate(array $schemaInfo, $actionReason = DeactivationReasons::NON_USE)
    {
        if (null === ($_row = Deactivation::instanceId($schemaInfo['instance']->id)->first())) {
            //  Not found
            $_row = new Deactivation();
            $_row->user_id = $schemaInfo['instance']->user_id;
            $_row->instance_id = $schemaInfo['instance']->id;
        }

        if (false === $actionReason) {
            //  Set activation date to 7 days from now.
            $_row->activate_by_date = date('Y-m-d H-i-s', time() + (7 * 86400));
        } else {
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
    public function scopeUserId($query, $userId)
    {
        if (!empty($userId)) {
            return $query->where('user_id', '=', $userId);
        }

        return $query;
    }

    /**
     * @param Builder $query
     * @param int     $dbServerId
     *
     * @return Builder
     */
    public function scopeOnDbServer($query, $dbServerId)
    {
        if (!empty($dbServerId)) {
            return $query->where('db_server_id', '=', $dbServerId);
        }

        return $query;
    }

    /**
     * @param Builder $query
     * @param int     $stateId
     *
     * @return Builder
     */
    public function scopeWithPlatformState($query, $stateId)
    {
        if (null !== $stateId) {
            return $query->where('platform_state_nbr', '=', $stateId);
        }

        return $query;
    }

    /**
     * @param Builder $query
     * @param string  $dbName The database name to query for
     *
     * @return Builder
     */
    public function scopeWithDbName($query, $dbName)
    {
        if (null !== $dbName) {
            return $query->where('db_name_text', '=', $dbName);
        }

        return $query;
    }

    /**
     * @param Builder $query
     * @param string  $instanceName
     *
     * @return Builder
     */
    public function scopeInstanceName($query, $instanceName)
    {
        return $query->where('instance_name_text', '=', $instanceName);
    }

    /**
     * @param Builder    $query
     * @param int|string $instanceNameOrId
     *
     * @return Builder
     */
    public function scopeByNameOrId($query, $instanceNameOrId)
    {
        return $query->whereRaw(
            'instance_name_text = :instance_name_text OR instance_id_text = :instance_id_text or id = :id',
            [
                ':instance_name_text' => $instanceNameOrId,
                ':instance_id_text'   => $instanceNameOrId,
                ':id'                 => $instanceNameOrId,
            ]
        );
    }

    /**
     * @param Builder $query
     * @param string  $instanceName
     *
     * @return Builder
     */
    public function scopeLikeInstanceName($query, $instanceName)
    {
        return $query->where('instance_name_text', 'like', '%' . $instanceName . '%');
    }

    /**
     * @return string
     */
    public function getStoragePath()
    {
        return InstanceStorage::getStoragePath($this);
    }

    /**
     * @return string
     */
    public function getSnapshotPath()
    {
        return InstanceStorage::getSnapshotPath($this);
    }

    /**
     * We want the private path of the instance to point to the user's area. Instances have no "private path" per se.
     *
     * @return mixed
     */
    public function getPrivatePath()
    {
        return InstanceStorage::getPrivatePath($this);
    }

    /**
     * Return the instance owner's private path
     *
     * @return mixed
     */
    public function getOwnerPrivatePath()
    {
        return InstanceStorage::getOwnerPrivatePath($this);
    }

    /**
     * Ensures that a storage key has been assigned
     */
    public function checkStorageKey()
    {
        if (empty($this->storage_id_text)) {
            $this->storage_id_text = UniqueId::generate(__CLASS__);
            $this->getStorageMap();
        }
    }

    /**
     * @param int|string|Server $serverId
     *
     * @return bool
     */
    public function removeFromServer($serverId)
    {
        $_server = ($serverId instanceof Server) ? $serverId : $this->_getServer($serverId);

        //  Do we belong to a server?
        if ($this->belongsToServer($_server->id)) {
            return
                1 == InstanceServer::whereRaw(
                    'server_id = :server_id AND instance_id = :instance_id',
                    [':server_id' => $_server->id, ':instance_id' => $this->id]
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
    public function addToServer($serverId)
    {
        //  This will fail if $serverId is bogus
        $this->removeFromServer($_server = $this->_getServer($serverId));

        return 1 == InstanceServer::insert(['server_id' => $_server->id, 'instance_id' => $this->id]);
    }

    /**
     * @param int|string $serverId
     *
     * @return bool True if this instance
     */
    public function belongsToServer($serverId)
    {
        $_server = $this->_getServer($serverId);

        /** @noinspection PhpUndefinedMethodInspection */

        return 0 != InstanceServer::whereRaw(
            'server_id = :server_id AND instance_id = :instance_id',
            [
                ':server_id'   => $_server->id,
                ':instance_id' => $this->id,
            ]
        )->count();
    }

    /**
     * @param int|string $serverId
     *
     * @return Server
     */
    protected function _getServer($serverId)
    {
        if (null === ($_server = Server::byNameOrId($serverId)->first())) {
            throw new \InvalidArgumentException('The server id "' . $serverId . '" is invalid.');
        }

        return $_server;
    }

    /**
     * @param string $name
     *
     * @return bool|string Returns the sanitized name or FALSE if not available
     */
    public static function isNameAvailable($name)
    {
        if (false === ($_sanitized = static::sanitizeName($name))) {
            return false;
        }

        return (0 == static::byNameOrId($_sanitized)->count() ? $_sanitized : false);
    }

    /**
     * Ensures the instance name meets quality standards
     *
     * @param string $name
     * @param bool   $isAdmin
     *
     * @return string
     */
    public static function sanitizeName($name, $isAdmin = false)
    {
        static $_sanitized = [];
        static $_unavailableNames = null;

        if (isset($_sanitized[$name])) {
            //\Log::debug( '>>> sanitize skipped' );

            return $_sanitized[$name];
        }

        //	This replaces any disallowed characters with dashes
        $_clean = str_replace(
            [' ', '_'],
            '-',
            trim(str_replace('--', '-', preg_replace(static::CHARACTER_PATTERN, '-', $name)), ' -_')
        );

        //  Ensure non-admin user instances are prefixed
        $_prefix =
            function_exists('config')
                ? config('dfe.instance-prefix')
                : 'dfe-';

        if ($_prefix != substr($_clean, 0, strlen($_prefix))) {
            $_clean = trim(str_replace('--', '-', $_prefix . $_clean), ' -_');
        }

        if (null === $_unavailableNames && function_exists('config')) {
            $_unavailableNames = config('dfe.forbidden-names', []);

            if (!is_array($_unavailableNames) || empty($_unavailableNames)) {
                $_unavailableNames = [];
            }
        }

        if (in_array($_clean, $_unavailableNames)) {
            \Log::error('Attempt to register forbidden instance name: ' . $name . ' => ' . $_clean);

            return false;
        }

        //	Check host name
        if (preg_match(static::HOST_NAME_PATTERN, $_clean)) {
            \Log::notice('Non-standard instance name "' . $_clean . '" being provisioned');
        }

        //  Cache it...
        $_sanitized[$name] = $_clean;

        //\Log::debug( '>>> sanitized "' . $name . '" to "' . $_clean . '"' );

        return $_clean;
    }

    /**
     * Retrieves an instances' metadata which is stored in the instance_data_text column, filling in any missing values.
     *
     * @param bool   $sync If true, the current information will be updated into the instance row
     * @param string $key  If specified, return only this metadata item, otherwise all
     *
     * @return array
     */
    public function getMetadata($sync = true, $key = null)
    {
        $_data = $this->instance_data_text;

        if (empty($_data)) {
            $_data = static::makeMetadata($this)->toArray();
            ($sync && !$key) && $this->update(['instance_data_text' => $_data]);
        }

        return $key ? array_get($_data, $key) : $_data;
    }

    /**
     * Sets an instances metadata
     *
     * @param Metadata|array $md The metadata to set
     *
     * @return Instance
     */
    public function setMetadata($md)
    {
        if ($md instanceof Metadata) {
            $this->instance_data_text = $md->toArray();
        } elseif (is_array($md)) {
            $this->instance_data_text = $md;
        }

        return $this;
    }

    /**
     * Merges fresh metadata with the existing
     *
     * @param Metadata|array $md The metadata to merge
     *
     * @return Instance
     */
    public function mergeMetadata($md)
    {
        $_data = $this->instance_data_text;
        empty($_data) && ($_data = []);

        if ($md instanceof Metadata) {
            $_md = $md->toArray();
        } elseif (is_array($md)) {
            $_md = $md;
        } else {
            throw new \InvalidArgumentException();
        }

        $this->instance_data_text = array_merge($_data, $_md);

        return $this;
    }

    /**
     * Returns the ROOT storage path for all instances with optional appendage
     *
     * @param string $append
     *
     * @return mixed|string
     */
    public function getRootStoragePath($append = null)
    {
        static $_cache = [];

        $_ck = hash(EnterpriseDefaults::DEFAULT_SIGNATURE_METHOD,
            'rsp.' . $this->id . ($append ? DIRECTORY_SEPARATOR . $append : $append));

        if (null === ($_path = array_get($_cache, $_ck))) {
            switch ($this->guest_location_nbr) {
                case GuestLocations::LOCAL:
                    $_path = storage_path($append);
                    break;

                default:
                    $_map = $this->getStorageMap();
                    $_path =
                        implode(DIRECTORY_SEPARATOR, [$_map['zone'], $_map['partition'], $_map['root-hash']]) .
                        ($append ? DIRECTORY_SEPARATOR . ltrim($append, ' ' . DIRECTORY_SEPARATOR) : null);
                    break;
            }

            $_cache[$_ck] = $_path;
        }

        return $_path;
    }

    /**
     * @param bool $update If true (default), the metadata will be updated with this new map
     *
     * @return array
     */
    public function getStorageMap($update = true)
    {
        $_data = $this->instance_data_text;
        empty($_data) && ($_data = []);
        $_map = array_get($_data, 'storage-map', []);

        if (empty($_map)) {
            //  Non-hosted has no structure, just storage
            if (GuestLocations::LOCAL == $this->guest_location_nbr) {
                $_map = [
                    'zone'      => null,
                    'partition' => null,
                    'root-hash' => null,
                ];
            } else {
                if ($this->user) {
                    $_userKey = $this->user->storage_id_text;
                } else {
                    $_userKey = \DB::select(
                        'SELECT storage_id_text FROM user_t WHERE id = :id',
                        [':id' => $this->user_id]
                    );

                    if ($_userKey) {
                        $_userKey = $_userKey[0]->storage_id_text;
                    }
                }

                if (empty($_userKey)) {
                    throw new \RuntimeException('Cannot locate owner record of instance.');
                }

                $_rootHash = hash(config('dfe.signature-method', EnterpriseDefaults::SIGNATURE_METHOD), $_userKey);
                $_partition = substr($_rootHash, 0, 2);

                $_zone = null;

                switch (config('dfe.provisioning.storage-zone-type')) {
                    case 'dynamic':
                        switch ($this->guest_location_nbr) {
                            case GuestLocations::AMAZON_EC2:
                            case GuestLocations::DFE_CLUSTER:
                                if (file_exists('/usr/bin/ec2metadata')) {
                                    $_zone = str_replace('availability-zone: ',
                                        null,
                                        `/usr/bin/ec2metadata | grep zone`);
                                }
                                break;
                        }
                        break;

                    case 'static':
                        $_zone = config('dfe.provisioning.static-zone-name');
                        break;
                }

                if (empty($_zone) || empty($_partition)) {
                    throw new \RuntimeException('Zone and/or partition unknown. Cannot provision storage.');
                }

                $_map = [
                    'zone'      => $_zone,
                    'partition' => $_partition,
                    'root-hash' => $_rootHash,
                ];
            }

            //  save the map into the metadata
            $_data['storage-map'] = $_map;
            $update && ($this->instance_data_text = $_data);
        }

        return $_map;
    }

    /**
     * Returns the relative root directory of this instance's storage
     *
     * @param string $tag
     *
     * @return Filesystem
     */
    public function getRootStorageMount($tag = null)
    {
        return InstanceStorage::getRootStorageMount($this, $tag);
    }

    /**
     * Returns the relative root directory of this instance's storage
     *
     * @param string $tag
     *
     * @return Filesystem
     */
    public function getSnapshotMount($tag = null)
    {
        return InstanceStorage::getSnapshotMount($this, $tag);
    }

    /**
     * @param string $tag
     *
     * @return Filesystem
     */
    public function getStorageMount($tag = null)
    {
        return InstanceStorage::getStorageMount($this, $tag);
    }

    /**
     * @param string $tag
     *
     * @return Filesystem
     */
    public function getPrivateStorageMount($tag = null)
    {
        return InstanceStorage::getPrivateStorageMount($this, $tag);
    }

    /**
     * @param string $tag
     *
     * @return Filesystem
     */
    public function getOwnerPrivateStorageMount($tag = null)
    {
        return InstanceStorage::getOwnerPrivateStorageMount($this, $tag);
    }

    /**
     * @param bool $save
     *
     * @return array|bool If $save is TRUE, instance row is saved and result returned. Otherwise, the freshened
     *                    metadata is returned.
     */
    public function refreshMetadata($save = false)
    {
        $this->instance_data_text = static::makeMetadata($this);

        return $save ? $this->save() : $this->instance_data_text;
    }

    /**
     * @param Instance $instance
     * @param bool     $object If true, the Metadata object is returned instead of the array
     *
     * @return array|\DreamFactory\Enterprise\Common\Support\Metadata
     */
    public static function makeMetadata(Instance $instance, $object = false)
    {
        $_key = AppKey::mine($instance->user_id, OwnerTypes::USER);
        $_cluster = static::_lookupCluster($instance->cluster_id);

        $_md = new Metadata([], $instance->instance_name_text . '.json', $instance->getOwnerPrivateStorageMount());
        $_md->set('storage-map', $instance->getStorageMap(false))
            ->set('env',
                [
                    'cluster-id'       => $_cluster->cluster_id_text,
                    'default-domain'   => $_cluster->subdomain_text,
                    'signature-method' => config('dfe.signature-method', EnterpriseDefaults::DEFAULT_SIGNATURE_METHOD),
                    'storage-root'     => EnterprisePaths::MOUNT_POINT . EnterprisePaths::STORAGE_PATH,
                    'console-api-url'  => config('dfe.security.console-api-url'),
                    'console-api-key'  => config('dfe.security.console-api-key'),
                    'client-id'        => $_key->client_id,
                    'client-secret'    => $_key->client_secret,
                ])
            ->set('db',
                [
                    $instance->instance_name_text => static::buildConnectionArray($instance),
                ])
            ->set('paths',
                [
                    'private-path'       => $instance->instance_name_text . DIRECTORY_SEPARATOR . '.private',
                    'owner-private-path' => '.private',
                    'snapshot-path'      => '.private' . DIRECTORY_SEPARATOR . 'snapshots',
                ]);

        return $object ? $_md : $_md->toArray();
    }

    /**
     * @param Instance $instance
     *
     * @return array
     */
    protected static function buildConnectionArray(Instance $instance)
    {
        return [
            'id'                    => $instance->dbServer
                ? $instance->dbServer->server_id_text
                : $instance->db_server_id,
            'host'                  => $instance->db_host_text,
            'port'                  => $instance->db_port_nbr,
            'username'              => $instance->db_user_text,
            'password'              => $instance->db_password_text,
            'driver'                => 'mysql',
            'default-database-name' => '',
            'database'              => $instance->db_name_text,
            'charset'               => 'utf8',
            'collation'             => 'utf8_unicode_ci',
            'prefix'                => '',
            'db-server-id'          => $instance->dbServer ? $instance->dbServer->server_id_text
                : $instance->db_server_id,
        ];
    }

    /**
     * Returns a connection to the instance's database
     *
     * @param \DreamFactory\Enterprise\Database\Models\Instance $instance
     *
     * @return \Illuminate\Database\Connection
     */
    public function instanceConnection(Instance $instance)
    {
        $_id = 'database.connections.' . $instance->instance_id_text;

        \Config::set('database.connections.' . $_id, static::buildConnectionArray($instance));

        return \DB::connection($_id);
    }
}
