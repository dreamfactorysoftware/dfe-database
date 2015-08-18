<?php namespace DreamFactory\Enterprise\Database\Models;

use DreamFactory\Enterprise\Common\Enums\AppKeyClasses;
use DreamFactory\Enterprise\Common\Enums\EnterpriseDefaults;
use DreamFactory\Enterprise\Common\Enums\EnterprisePaths;
use DreamFactory\Enterprise\Common\Enums\OperationalStates;
use DreamFactory\Enterprise\Common\Facades\InstanceStorage;
use DreamFactory\Enterprise\Common\Support\Metadata;
use DreamFactory\Enterprise\Common\Traits\EntityLookup;
use DreamFactory\Enterprise\Common\Traits\Guzzler;
use DreamFactory\Enterprise\Common\Traits\StaticComponentLookup;
use DreamFactory\Enterprise\Common\Utility\UniqueId;
use DreamFactory\Enterprise\Database\Contracts\OwnedEntity;
use DreamFactory\Enterprise\Database\Enums\DeactivationReasons;
use DreamFactory\Enterprise\Database\Enums\GuestLocations;
use DreamFactory\Enterprise\Database\Enums\OwnerTypes;
use DreamFactory\Enterprise\Database\Exceptions\InstanceNotActivatedException;
use DreamFactory\Enterprise\Database\Exceptions\InstanceUnlockedException;
use DreamFactory\Enterprise\Database\Traits\AuthorizedEntity;
use DreamFactory\Enterprise\Database\Traits\KeyMaster;
use DreamFactory\Enterprise\Services\Facades\Snapshot;
use DreamFactory\Library\Utility\Disk;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use League\Flysystem\Filesystem;

/**
 * instance_t
 *
 * @property integer $user_id
 * @property integer $cluster_id
 * @property integer $guest_location_nbr
 * @property string  $instance_id_text
 * @property string  $instance_name_text
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
 * @method static Builder byOwner(mixed $ownerId, mixed $ownerType = null)
 * @method static Builder byClusterId(int $clusterId)
 */
class Instance extends EnterpriseModel implements OwnedEntity
{
    //******************************************************************************
    //* Traits
    //******************************************************************************

    use EntityLookup, AuthorizedEntity, StaticComponentLookup, KeyMaster, Guzzler;

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
        'environment_id'     => 'integer',
        'db_port_nbr'        => 'integer',
        'state_nbr'          => 'integer',
        'platform_state_nbr' => 'integer',
        'ready_state_nbr'    => 'integer',
        'provision_ind'      => 'boolean',
        'deprovision_ind'    => 'boolean',
        'trial_instance_ind' => 'boolean',
        'activate_ind'       => 'boolean',
    ];
    /**
     * @type array The template for metadata stored in
     */
    protected static $metadataTemplate;

    //******************************************************************************
    //* Methods
    //******************************************************************************

    /**
     * Boot method to wire in our events
     */
    public static function boot()
    {
        parent::boot();

        static::buildMetadataTemplate();

        static::creating(function (Instance $instance){
            $instance->instance_name_text = $instance->sanitizeName($instance->instance_name_text);
        });

        static::created(function (Instance $instance){
            $instance->refreshMetadata();
        });

        static::updating(function (Instance $instance){
            $instance->refreshMetadata();
        });

        static::deleted(function (Instance $instance){
            AppKey::byOwner($instance->id, OwnerTypes::INSTANCE)->delete();
        });
    }

    /**
     * @param \DreamFactory\Enterprise\Database\Models\EnterpriseModel|mixed $row
     */
    protected static function enforceBusinessLogic($row)
    {
        parent::enforceBusinessLogic($row);

        $row->checkStorageKey();
    }

    /** @inheritdoc */
    public function owner()
    {
        return $this->belongsTo(static::MODEL_NAMESPACE . 'User', 'id', 'user_id');
    }

    /** @inheritdoc */
    public function appKeys($localKey = null, $type = null, $id = null)
    {
        return $this->keyMaster($localKey ?: 'user_id', $type, $id);
    }

    /**
     * Return the owner type of this model
     *
     * @return string
     */
    /** @noinspection PhpMissingParentCallCommonInspection */
    public function getMorphClass()
    {
        return OwnerTypes::USER;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough|Server[]
     */
    public function servers()
    {
        return $this->hasManyThrough(static::MODEL_NAMESPACE . 'InstanceServer',
            static::MODEL_NAMESPACE . 'Server',
            'instance_id',
            'server_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne|Cluster
     */
    public function cluster()
    {
        return $this->hasOne(static::MODEL_NAMESPACE . 'Cluster', 'id', 'cluster_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne|Server
     */
    public function webServer()
    {
        return $this->hasOne(static::MODEL_NAMESPACE . 'Server', 'id', 'web_server_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne|Server
     */
    public function dbServer()
    {
        return $this->hasOne(static::MODEL_NAMESPACE . 'Server', 'id', 'db_server_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne|Server
     */
    public function appServer()
    {
        return $this->hasOne(static::MODEL_NAMESPACE . 'Server', 'id', 'app_server_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne|\DreamFactory\Enterprise\Database\Models\User
     */
    public function user()
    {
        return $this->hasOne(static::MODEL_NAMESPACE . 'User', 'id', 'user_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo|InstanceGuest
     */
    public function guest()
    {
        return $this->belongsTo(static::MODEL_NAMESPACE . 'InstanceGuest');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany|Snapshot[]
     */
    public function snapshots()
    {
        return $this->belongsToMany(static::MODEL_NAMESPACE . 'Snapshot');
    }

    /**
     * @param string $operation The operation performed
     * @param array  $data      Any data to support the operation
     *
     * @return bool
     */
    public function addOperation($operation, $data = [])
    {
        if (is_object($data) && method_exists($data, 'toArray')) {
            $data = $data->toArray();
        }

        if (!is_array($data)) {
            $data = [];
        }

        $_md = $this->instance_data_text ?: [];
        $_ops = array_get($_md, Metadata::OPERATIONS_LOG_KEY, []);
        $_ops[] = [$operation => array_merge($data, ['timestamp' => date('c')])];
        $_md[Metadata::OPERATIONS_LOG_KEY] = $_ops;
        $this->instance_data_text = $_md;

        return $this->save();
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
        return $query->whereRaw('instance_name_text = :instance_name_text OR instance_id_text = :instance_id_text or id = :id',
            [
                ':instance_name_text' => $instanceNameOrId,
                ':instance_id_text'   => $instanceNameOrId,
                ':id'                 => $instanceNameOrId,
            ]);
    }

    /**
     * @param Builder $query
     * @param int     $clusterId
     *
     * @return Builder
     */
    public function scopeByClusterId($query, $clusterId)
    {
        return $query->where('cluster_id', $clusterId);
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
     * @param string|null $append Optional path to append
     *
     * @return string
     */
    public function getTrashPath($append = null)
    {
        return InstanceStorage::getTrashPath($this, $append);
    }

    /**
     * @param string|null $append Optional path to append
     * @param bool        $create Create if non-existent
     *
     * @return \League\Flysystem\Filesystem
     */
    public function getTrashMount($append = null, $create = true)
    {
        return InstanceStorage::getTrashMount($this, $append, $create);
    }

    /**
     * @param string|null $append
     * @param bool        $create
     *
     * @return string
     */
    public function getSnapshotPath($append = null, $create = false)
    {
        return InstanceStorage::getSnapshotPath($append, $create);
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

    public function getWorkPath()
    {
        return InstanceStorage::getWorkPath($this);
    }

    /**
     * @param string $workPath
     *
     * @return string
     */
    public function deleteWorkPath($workPath)
    {
        return InstanceStorage::deleteWorkPath($workPath);
    }

    /**
     * Return the instance owner's private path
     *
     * @return mixed
     */
    public function getOwnerPrivatePath()
    {
        return InstanceStorage::getOwnerPrivatePath();
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
            return 1 == InstanceServer::whereRaw('server_id = :server_id AND instance_id = :instance_id',
                [':server_id' => $_server->id, ':instance_id' => $this->id])->delete();
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

        return 0 != InstanceServer::whereRaw('server_id = :server_id AND instance_id = :instance_id',
            [
                ':server_id'   => $_server->id,
                ':instance_id' => $this->id,
            ])->count();
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
     * @param bool   $isAdmin
     *
     * @return bool|string Returns the sanitized name or FALSE if not available
     */
    public static function isNameAvailable($name, $isAdmin = false)
    {
        if (false === ($_sanitized = static::sanitizeName($name, $isAdmin))) {
            return false;
        }

        return (0 == static::byNameOrId($_sanitized)->count() ? $_sanitized : false);
    }

    /**
     * Ensures the instance name meets quality standards
     *
     * @param string $name
     * @param bool   $isAdmin Set to true if owner is admin
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
        $_clean = str_replace([' ', '_'],
            '-',
            trim(str_replace('--', '-', preg_replace(static::CHARACTER_PATTERN, '-', $name)), ' -_'));

        //  Ensure non-admin user instances are prefixed
        if ($isAdmin) {
            $_prefix = null;
        } else {
            $_prefix = function_exists('config') ? config('dfe.instance-prefix') : 'dfe-';

            if ($_prefix != substr($_clean, 0, strlen($_prefix))) {
                $_clean = trim(str_replace('--', '-', $_prefix . $_clean), ' -_');
            }
        }

        if (null === $_unavailableNames && function_exists('config')) {
            $_unavailableNames = config('forbidden-names', []);

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
    public function getMetadata($sync = false, $key = null)
    {
        $_data = $this->instance_data_text;

        if (empty($_data)) {
            $this->refreshMetadata($sync);
            $_data = $this->instance_data_text;
        }

        return $key ? array_get($_data, $key) : $_data;
    }

    /**
     * Retrieves an instances' metadata in object form.
     *
     * @return Metadata
     */
    public function getMetadataObject()
    {
        return new Metadata($this->getMetadata());
    }

    /**
     * Sets an instances metadata
     *
     * @param Metadata|array $md The metadata to set
     *
     * @return Instance
     */
    public function setMetadata($md = [])
    {
        $this->instance_data_text = ($md instanceof Metadata) ? $md->toArray() : $md;

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
     * Returns the ROOT storage path for a user. Under which is all instances and private areas
     *
     * @param string $append
     *
     * @return mixed|string
     */
    public function getBaseStoragePath($append = null)
    {
        static $_cache = [];

        $_ck =
            hash(config('dfe.signature-method', EnterpriseDefaults::SIGNATURE_METHOD),
                'rsp.' . $this->id . Disk::segment($append, true));

        if (!is_numeric($this->guest_location_nbr)) {
            $this->guest_location_nbr = GuestLocations::resolve($this->guest_location_nbr, true);
        }

        if (null === ($_path = array_get($_cache, $_ck))) {
            switch ($this->guest_location_nbr) {
                case GuestLocations::DFE_CLUSTER:
                    $_path = Disk::path([InstanceStorage::getStorageRoot(), $this->getSubRootHash(), $append]);
                    break;

                default:
                    $_path = storage_path($append);
                    break;
            }

            $_cache[$_ck] = $_path;
        }

        \Log::debug('storage path: ' . $_path);

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
                /** @type User $_user */
                $_user = $this->user ?: User::findOrFail($this->user_id);
                $_map = array_merge(['zone' => null,], $_user->getStorageMap());
                $_zone = null;

                switch (config('provisioning.storage-zone-type')) {
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
                        $_zone = config('provisioning.static-zone-name');
                        break;
                }

                if (empty($_zone)) {
                    throw new \RuntimeException('Storage zone or type invalid. Cannot provision storage.');
                }

                $_map['zone'] = $_zone;
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
    public function getSnapshotMount($tag = null)
    {
        return InstanceStorage::getSnapshotMount($this, $tag);
    }

    /**
     * Returns the instance's storage area as a filesystem
     *
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
     * @param Builder $query
     * @param int     $ownerId
     * @param int     $ownerType
     *
     * @return mixed
     */
    public function scopeByOwner($query, $ownerId, $ownerType = null)
    {
        return $query->where('user_id', $ownerId)->where('owner_type_nbr', $ownerType ?: OwnerTypes::USER);
    }

    /**
     * @param Instance $instance
     * @param bool     $object If true, the Metadata object is returned instead of the array
     *
     * @return array|\DreamFactory\Enterprise\Common\Support\Metadata
     */
    public static function makeMetadata(Instance $instance, $object = false)
    {
        if (null === ($_key = AppKey::mine($instance->id, OwnerTypes::INSTANCE))) {
            //  Create an instance key
            $_key = AppKey::create([
                'key_class_text' => AppKeyClasses::INSTANCE,
                'owner_id'       => $instance->id,
                'owner_type_nbr' => OwnerTypes::INSTANCE,
                'server_secret'  => config('dfe.security.console-api-key'),
            ]);

            if (null === $_key) {
                throw new \RuntimeException('Instance is unlicensed.');
            }
        }

        $_cluster = static::_lookupCluster($instance->cluster_id);

        $_md = new Metadata(array_merge(static::$metadataTemplate,
            [
                'storage-map' => $instance->getStorageMap(false),
                'env'         => static::buildEnvironmentMetadata($instance, $_cluster, $_key),
                'db'          => static::buildDatabaseMetadata($instance),
                'paths'       => static::buildPathMetadata($instance),
                'audit'       => static::buildAuditMetadata($instance),
                'limits'      => static::buildLimitsMetadata($instance),
            ]), $instance->instance_name_text . '.json', $instance->getOwnerPrivateStorageMount());

        return $object ? $_md : $_md->toArray();
    }

    /**
     * Build the 'paths' section of the metadata
     *
     * @param \DreamFactory\Enterprise\Database\Models\Instance $instance
     *
     * @return array
     */
    protected static function buildPathMetadata(Instance $instance)
    {
        return [
            'storage-root'       => InstanceStorage::getStorageRoot(),
            'storage-path'       => $instance->getStoragePath(),
            'private-path'       => $instance->getPrivatePath(),
            'owner-private-path' => $instance->getOwnerPrivatePath(),
            'snapshot-path'      => $instance->getSnapshotPath(),
        ];
    }

    /**
     * Build the 'db' section of the metadata
     *
     * @param \DreamFactory\Enterprise\Database\Models\Instance $instance
     *
     * @return array
     */
    public static function buildDatabaseMetadata(Instance $instance)
    {
        return [
            $instance->instance_name_text => static::buildConnectionArray($instance),
        ];
    }

    /**
     * Returns an array of data used to send to the auditing system
     *
     * @param \DreamFactory\Enterprise\Database\Models\Instance $instance
     *
     * @return array
     */
    public static function buildAuditMetadata(Instance $instance)
    {
        return [
            'user-id'             => $instance->user_id,
            'owner-email-address' => $instance->user->email_addr_text,
            'instance-id'         => $instance->instance_id_text,
            'cluster-id'          => $instance->cluster->cluster_id_text,
            'web-server-id'       => $instance->webServer->server_id_text,
            'db-server-id'        => $instance->dbServer->server_id_text,
            'app-server-id'       => $instance->appServer->server_id_text,
        ];
    }

    /**
     * Build the 'env' section of the metadata
     *
     * @param \DreamFactory\Enterprise\Database\Models\Instance $instance
     * @param \DreamFactory\Enterprise\Database\Models\Cluster  $cluster
     * @param \DreamFactory\Enterprise\Database\Models\AppKey   $key
     *
     * @return array
     */
    public static function buildEnvironmentMetadata(Instance $instance, Cluster $cluster, AppKey $key = null)
    {
        return [
            'cluster-id'       => $cluster->cluster_id_text,
            'instance-id'      => $instance->instance_name_text,
            'default-domain'   => $cluster->subdomain_text,
            'signature-method' => config('dfe.signature-method', EnterpriseDefaults::DEFAULT_SIGNATURE_METHOD),
            'storage-root'     => config('provisioning.storage-root',
                EnterprisePaths::MOUNT_POINT . EnterprisePaths::STORAGE_PATH),
            'console-api-url'  => config('dfe.security.console-api-url'),
            'console-api-key'  => config('dfe.security.console-api-key'),
            'client-id'        => $key ? $key->client_id : null,
            'client-secret'    => $key ? $key->client_secret : null,
        ];
    }

    /**
     * @param Instance $instance
     *
     * @return array
     */
    protected static function buildConnectionArray(Instance $instance)
    {
        return [
            'id'                    => $instance->dbServer ? $instance->dbServer->server_id_text
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
     * Build the limits array
     *
     * @param \DreamFactory\Enterprise\Database\Models\Instance $instance
     *
     * @return array
     */
    public static function buildLimitsMetadata(Instance $instance)
    {
        /** @type Limit[] $_limits */
        $_limits = Limit::byClusterInstance($instance->id, $instance->cluster_id)->get();

        $_api_array = [];

        foreach ($_limits as $_limit) {
            $_api_array[] =
                [$_limit->limit_key_text => ['limit' => $_limit->value_nbr, 'period' => $_limit->period_nbr]];
        }

        // In the future, there could be additional keys, such as 'bandwidth' or 'storage'
        return [
            'api' => $_api_array,
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

    /**
     * Returns the endpoint of a provisioned instance
     *
     * @return string
     */
    public function getProvisionedEndpoint()
    {
        return 'http://' . $this->instance_name_text . '.' . trim($this->cluster->subdomain_text, '.');
    }

    /**
     * Makes a shout out to an instance's private back-end. Should be called bootyCall()  ;)
     *
     * @param string $uri     The REST uri (i.e. "/db", "/system/users", etc.) to retrieve from the instance
     * @param array  $payload Any payload to send with request
     * @param array  $options Any options to pass to transport layer
     * @param string $method  The HTTP method. Defaults to "POST"
     *
     * @return array|bool|\stdClass
     */
    public function call($uri, $payload = [], $options = [], $method = Request::METHOD_POST)
    {
        return $this->guzzleAny($this->getProvisionedEndpoint() . '/' . ltrim($uri, '/'), $payload, $options, $method);
    }

    /**
     * Creates a sub-path (think "identifier") that may be used under any "root"
     * to uniquely identify an owner's area
     *
     * @param array|null $map The instance storage map
     *
     * @return string A $separator delimited identifier/path under a root for an instance
     */
    public function getSubRootHash($map = null)
    {
        $map = !is_array($map) ? $this->getStorageMap(false) : $map;

        return Disk::segment(array_only($map, ['zone', 'partition', 'root-hash']));
    }

    /**
     * Builds the metadata template based on the allowed keys of the Metadata class
     */
    protected static function buildMetadataTemplate()
    {
        $_md = new Metadata();
        $_base = [];

        foreach ($_md->getAllowedKeys() as $_key) {
            $_base[$_key] = [];
        }

        static::$metadataTemplate = $_base;
    }

    /**
     * Construct and returns the fully qualify URL of an instance
     *
     * @return string
     */
    protected function buildInstanceUrl()
    {
        return config('dfe.default-domain-protocol') . '://' . $this->instance_id_text . config('dfe.default-domain');
    }
}
