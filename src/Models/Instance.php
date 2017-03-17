<?php namespace DreamFactory\Enterprise\Database\Models;

use Carbon\Carbon;
use DreamFactory\Enterprise\Common\Enums\InstanceStates;
use DreamFactory\Enterprise\Common\Utility\Ini;
use DreamFactory\Enterprise\Database\Enums\AppKeyClasses;
use DreamFactory\Enterprise\Common\Enums\EnterpriseDefaults;
use DreamFactory\Enterprise\Common\Enums\EnterprisePaths;
use DreamFactory\Enterprise\Common\Enums\OperationalStates;
use DreamFactory\Enterprise\Common\Support\Metadata;
use DreamFactory\Enterprise\Common\Traits\EntityLookup;
use DreamFactory\Enterprise\Common\Traits\Guzzler;
use DreamFactory\Enterprise\Common\Utility\UniqueId;
use DreamFactory\Enterprise\Database\Contracts\OwnedEntity;
use DreamFactory\Enterprise\Database\Enums\DeactivationReasons;
use DreamFactory\Enterprise\Database\Enums\GuestLocations;
use DreamFactory\Enterprise\Database\Enums\OwnerTypes;
use DreamFactory\Enterprise\Database\Exceptions\InstanceNotActivatedException;
use DreamFactory\Enterprise\Database\Exceptions\InstanceUnlockedException;
use DreamFactory\Enterprise\Database\Traits\AuthorizedEntity;
use DreamFactory\Enterprise\Database\Traits\KeyMaster;
use DreamFactory\Enterprise\Storage\Facades\InstanceStorage;
use DreamFactory\Library\Utility\Disk;
use DreamFactory\Library\Utility\Enums\DateTimeIntervals;
use DreamFactory\Library\Utility\Uri;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use League\Flysystem\Filesystem;
use DB;


/**
 * instance_t
 *
 * @property integer       $user_id
 * @property integer       $cluster_id
 * @property integer       $guest_location_nbr
 * @property string        $instance_id_text
 * @property string        $instance_name_text
 * @property array         $instance_data_text
 * @property int           $app_server_id
 * @property int           $db_server_id
 * @property int           $web_server_id
 * @property string        $db_host_text
 * @property int           $db_port_nbr
 * @property string        $db_name_text
 * @property string        $db_user_text
 * @property string        $db_password_text
 * @property string        $storage_id_text
 * @property string        $request_id_text
 * @property Carbon        $request_date
 * @property integer       $deprovision_ind
 * @property integer       $provision_ind
 * @property integer       $trial_instance_ind
 * @property integer       $state_nbr
 * @property integer       $platform_state_nbr
 * @property integer       $ready_state_nbr
 * @property integer       $environment_id
 * @property integer       $activate_ind
 * @property Carbon        $start_date
 * @property Carbon        $end_date
 * @property Carbon        $terminate_date
 * @property Carbon        $last_state_date
 *
 * Relations:
 *
 * @property User          $user
 * @property InstanceGuest $guest
 * @property Server        $appServer
 * @property Server        $dbServer
 * @property Server        $webServer
 * @property Cluster       cluster
 *
 * @method static Builder|EloquentBuilder instanceName($instanceName)
 * @method static Builder|EloquentBuilder byNameOrId($instanceNameOrId)
 * @method static Builder|EloquentBuilder userId($userId)
 * @method static Builder|EloquentBuilder withDbName($dbName)
 * @method static Builder|EloquentBuilder onDbServer($dbServerId)
 * @method static Builder|EloquentBuilder byOwner($ownerId, $ownerType = null)
 * @method static Builder|EloquentBuilder byClusterId($clusterId)
 */
class Instance extends EnterpriseModel implements OwnedEntity
{
    //******************************************************************************
    //* Traits
    //******************************************************************************

    use EntityLookup, AuthorizedEntity, KeyMaster, Guzzler;

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
    const HOST_NAME_PATTERN = "/^([a-zA-Z0-9_])+$/";
    /**
     * @type int The number of minutes to cache stuff
     */
    const DEFAULT_CACHE_TTL = 5;

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
    /**
     * @type array Packages used during provisioning
     */
    protected $packages;

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

        static::creating(function(Instance $instance) {
            $instance->instance_name_text = $instance->sanitizeName($instance->instance_name_text);
            $instance->checkStorageKey();
        });

        static::created(function(Instance $instance) {
            $instance->refreshMetadata();
        });

        static::updating(function(Instance $instance) {
            $instance->refreshMetadata();
        });

        static::saving(function(Instance $instance) {
            !empty($instance->id) && $instance->refreshMetadata();
        });

        static::deleted(function(Instance $instance) {
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

    /**
     * @param string|Carbon $days The number of days since creation that an instance is given to activate
     * @param array|null    $ids  Limit results to this optional list of instance-id's
     *
     * @return array
     */
    public static function eligibleDeactivations($days, $ids = null)
    {
        $_sql = <<<MYSQL
SELECT 
    i.id as `instance_id`,
    i.instance_id_text,
    i.activate_ind,
    i.platform_state_nbr,
    i.create_date
FROM instance_t i 
WHERE 
    DATEDIFF(CURRENT_DATE, i.create_date) >= :days 
ORDER BY 
    i.create_date
MYSQL;
        return \DB::select($_sql, [':days' => $days]);
    }

    public static function getEligibleReminders($reminderDays){
        $_sql = <<<MYSQL
SELECT 
    i.instance_id_text,
    i.activate_ind,
    i.instance_data_text,
    i.create_date,
    user_t.first_name_text as `firstname`,
    user_t.email_addr_text as`email`,
    user_t.nickname_text as `display_name`
FROM instance_t i 
INNER JOIN user_t ON(user_t.id = i.user_id)
WHERE 
    DATEDIFF(CURRENT_DATE, i.create_date) = :days
ORDER BY 
    i.create_date
MYSQL;
        $reminders = DB::select($_sql, [':days' => $reminderDays]);
        if(isset($reminders[0])){
            return $reminders[0];
        }
        return $reminders;
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
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany|\DreamFactory\Enterprise\Database\Models\Snapshot[]
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
     * Returns any metrics gathered for this instance on a specific date.
     *
     * @param Carbon|string $gatherDate The date to check. If not provided, today is used.
     *
     * @return MetricsDetail|null
     */
    public function metrics($gatherDate = null)
    {
        $gatherDate = $gatherDate ?: date('Y-m-d');

        return MetricsDetail::where([
            'instance_id' => $this->id,
            'user_id'     => $this->user_id,
            'gather_date' => $gatherDate,
        ])->first();
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
        /** @noinspection PhpUndefinedMethodInspection */
        return static::byNameOrId($schemaInfo['instance']->id)->firstOrFail()->updateInstanceState(false, true, $actionReason);
    }

    /**
     * @param bool|null $activate
     * @param int|null  $readyState
     * @param int|null  $state
     */
    protected function setStates($activate = null, $readyState = null, $state = null)
    {
        $activate = $activate ?: $this->activate_ind;
        $readyState = $readyState ?: $this->ready_state_nbr;
        $this->platform_state_nbr = $state ?: OperationalStates::NOT_ACTIVATED;

        //  Fully activated
        if ($activate && InstanceStates::READY == $readyState) {
            $this->activate_ind = true;
            $this->ready_state_nbr = $readyState;
            $this->platform_state_nbr = $state ?: OperationalStates::ACTIVATED;

            return;
        }

        //  Active, no admin
        if ($activate && InstanceStates::ADMIN_REQUIRED == $readyState) {
            $this->activate_ind = false;
            $this->ready_state_nbr = $readyState;

            return;
        }

        $this->activate_ind = $activate;
        $this->ready_state_nbr = $readyState;
    }

    /**
     * @param bool     $activate   To activate or not to activate. That is the boolean.
     * @param bool     $sync       If true, deactivation_t rows are kept in sync
     * @param int      $reason     Reason for deactivation
     * @param int|null $readyState The instance's actual operational/ready state
     *
     * @return bool
     */
    public function updateInstanceState($activate = true, $sync = true, $reason = DeactivationReasons::NON_USE, $readyState = null)
    {
        //  Don't do anything if the values are the same
        if ($this->activate_ind == ($activate ? 1 : 0) &&
            $this->platform_state_nbr == ($activate ? OperationalStates::ACTIVATED : OperationalStates::NOT_ACTIVATED) &&
            (null !== $readyState && $this->ready_state_nbr == $readyState)
        ) {
            return true;
        }

        $this->setStates($activate, $readyState);
        $this->activate_ind = $activate;
        $this->last_state_date = Carbon::now();
        $this->platform_state_nbr = $activate ? OperationalStates::ACTIVATED : OperationalStates::NOT_ACTIVATED;

        if (null !== $readyState) {
            $this->ready_state_nbr = $readyState;
        }

        if (!$this->save()) {
            \Log::error('[dfe.database.models.instance:updateInstanceState] instance state update failure for instance "' . $this->instance_id_text . '"',
                ['activate_ind' => $this->activate_ind, 'ready_state_nbr' => $this->ready_state_nbr, 'platform_state_nbr' => $this->platform_state_nbr]);

            return false;
        }

        logger('[dfe.database.models.instance:updateInstanceState] activation status updated for instance "' . $this->instance_id_text . '"',
            ['activate_ind' => $this->activate_ind, 'ready_state_nbr' => $this->ready_state_nbr, 'platform_state_nbr' => $this->platform_state_nbr]);

        //  Sync if wanted
        return $sync ? $this->syncActivation($this->activate_ind, $reason) : true;
    }

    /**
     * @param bool $active       True to activate, false to deactivate
     * @param int  $actionReason Reason for deactivation
     *
     * @return bool
     */
    protected function syncActivation($active = true, $actionReason = DeactivationReasons::NON_USE)
    {
        try {
            if (null === ($_row = Deactivation::instanceId($this->id)->userId($this->user_id)->first())) {
                //  Find prior or make new
                /** @type Deactivation $_row */
                $_row = Deactivation::create([
                    'user_id'           => $this->user_id,
                    'instance_id'       => $this->id,
                    //  Set activation date to 7 days from now.
                    'activate_by_date'  => date('Y-m-d H-i-s', time() + (7 * DateTimeIntervals::SECONDS_PER_DAY)),
                    'action_reason_nbr' => $actionReason,
                ]);
            }

            //  Delete activation row if activated
            if (true === $active) {
                if (0 != Deactivation::instanceId($this->id)->delete()) {
                    logger('[dfe.database.models.instance:syncActivation] deactivation cleared for instance "' . $this->instance_id_text . '"');
                }

                return true;
            }

            //  Increment the count
            return $_row->update(['extend_count_nbr' => $_row->extend_count_nbr + 1]);
        } catch (\Exception $_ex) {
            \Log::error('[dfe.database.models.instance:syncActivation] ' . $_ex->getMessage());
        }

        return false;
    }

    /**
     * @param Builder $query
     * @param int     $userId
     *
     * @return Builder
     */
    public function scopeUserId($query, $userId)
    {
        if (!empty($userId)) {
            return $query->where('user_id', $userId);
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
     * Ensures that a storage key has been assigned
     */
    public function checkStorageKey()
    {
        if (empty($this->storage_id_text)) {
            $this->storage_id_text = UniqueId::generate(__CLASS__);

            $this->user && InstanceStorage::buildStorageMap($this->user->storage_id_text);
        }
    }

    /**
     * @param int|string|Server $serverId
     *
     * @return bool
     */
    public function removeFromServer($serverId)
    {
        $_server = $this->findServer($serverId);

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
        $this->removeFromServer($_server = $this->findServer($serverId));

        return 1 == InstanceServer::insert(['server_id' => $_server->id, 'instance_id' => $this->id]);
    }

    /**
     * @param int|string $serverId
     *
     * @return bool True if this instance
     */
    public function belongsToServer($serverId)
    {
        $_server = $this->findServer($serverId);

        /** @noinspection PhpUndefinedMethodInspection */

        return 0 != InstanceServer::whereRaw('server_id = :server_id AND instance_id = :instance_id',
                [
                    ':server_id'   => $_server->id,
                    ':instance_id' => $this->id,
                ])->count();
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
        $_sanitized = \Cache::get('dfe.instance.sanitized', []);

        if (isset($_sanitized[$name])) {
            return $_sanitized[$name];
        }

        //	This replaces any disallowed characters with dashes
        $_clean =
            trim(strtolower(str_replace([' ', '_'], '-', trim(str_replace('--', '-', preg_replace(static::CHARACTER_PATTERN, '-', $name)), ' -_'))), ' -_');

        //  Ensure non-admin user instances are prefixed
        $_prefix = null;

        if (!$isAdmin) {
            $_prefix = config('dfe.instance-prefix', 'dfe-');

            if ($_prefix != substr($_clean, 0, strlen($_prefix))) {
                $_clean = trim(str_replace('--', '-', $_prefix . $_clean), ' -_');
            }
        }

        if (static::isVerboten($_clean)) {
            \Log::error('[sanitizer] forbidden name: ' . $name . ' => ' . $_clean);

            return false;
        }

        //	Check host name
        if (preg_match(static::HOST_NAME_PATTERN, $_clean)) {
            \Log::info('[sanitizer] non-standard name allowed: "' . $_clean . '"');
        }

        //  Cache it...
        $_sanitized[$name] = $_clean;

        \Cache::put('dfe.instance.sanitized', $_sanitized, static::DEFAULT_CACHE_TTL);

        return $_clean;
    }

    /**
     * Tests if an instance name is allowed
     *
     * @param string $name
     *
     * @return bool
     */
    protected static function isVerboten($name)
    {
        $_unavailableNames = \Cache::get('dfe.instance.forbidden-names', []);

        if (empty($_unavailableNames) && function_exists('config')) {
            $_unavailableNames = config('forbidden-names', []);

            if (!is_array($_unavailableNames) || empty($_unavailableNames)) {
                $_unavailableNames = [];
            }

            \Cache::put('dfe.instance.forbidden-names', $_unavailableNames, static::DEFAULT_CACHE_TTL);
        }

        return in_array($name, $_unavailableNames);
    }

    /**
     * Retrieves an instances' metadata which is stored in the instance_data_text column, filling in any missing values.
     *
     * @param bool       $sync    If true, the current information will be updated into the instance row
     * @param string     $key     If specified, return only this metadata item, otherwise all
     * @param mixed|null $default The value to return if $key is not found
     *
     * @return array
     */
    public function getMetadata($sync = false, $key = null, $default = null)
    {
        if (empty($_data = $this->instance_data_text)) {
            $this->refreshMetadata($sync);
            $_data = $this->instance_data_text;
        }

        return $key ? data_get($_data, $key, $default) : $_data;
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
     * Sets a single item in the instance metadata array
     *
     * @param string     $key
     * @param mixed|null $value
     *
     * @return \DreamFactory\Enterprise\Database\Models\Instance
     */
    public function setMetadataItem($key, $value = null)
    {
        if (empty($_md = $this->instance_data_text)) {
            $_md = $this->refreshMetadata(false);
        }

        array_set($_md, $key, $value);

        return $this->setMetadata($_md);
    }

    /**
     * Returns the hash (storage's "root-hash") for the user
     *
     * @return string|null
     */
    public function getOwnerHash()
    {
        return $this->user && $this->user->getHash() || null;
    }

    /*------------------------------------------------------------------------------*/
    /* Paths & Mounts                                                               */
    /*------------------------------------------------------------------------------*/

    /**
     * @param string|null $append
     * @param bool        $create
     *
     * @return string
     */
    public function getStoragePath($append = null, $create = false)
    {
        return InstanceStorage::getStoragePath($this, $append, $create);
    }

    /**
     * @param string|null $append Optional path to append
     *
     * @return string
     */
    public function getTrashPath($append = null)
    {
        return InstanceStorage::getTrashPath($append);
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
     * Returns the
     *
     * @return string
     */
    public function getPackagePath()
    {
        return InstanceStorage::getPackagePath($this);
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
     * @param string|null $append
     * @param bool        $create
     *
     * @return string
     */
    public function getSnapshotPath($append = null, $create = false)
    {
        return InstanceStorage::getSnapshotPath($this, $append, $create);
    }

    /**
     * Returns a path where you can write instance-specific temporary data
     *
     * @param string|null $append Optional appendage to path
     *
     * @return string
     */
    public function getWorkPath($append = null)
    {
        return InstanceStorage::getWorkPath($this, $append);
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
     * @param string|null $append Optional path to append
     * @param bool        $create Create if non-existent
     *
     * @return \League\Flysystem\Filesystem
     */
    public function getTrashMount($append = null, $create = true)
    {
        return InstanceStorage::getTrashMount($append, $create);
    }

    /**
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
     * @return \League\Flysystem\Filesystem
     */
    public function getStorageRootMount($tag = null)
    {
        return InstanceStorage::getStorageRootMount($this, $tag);
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
    public function getPackageStorageMount($tag = null)
    {
        return InstanceStorage::getPackageStorageMount($this, $tag);
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

    /*------------------------------------------------------------------------------*/
    /* Metadata                                                                        */
    /*------------------------------------------------------------------------------*/

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

        $_cluster = static::findCluster($instance->cluster_id);

        $_md = new Metadata(array_merge(static::$metadataTemplate,
            [
                'storage-map' => InstanceStorage::buildStorageMap($instance->user->storage_id_text),
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
            'storage-root'       => InstanceStorage::getStorageRootPath(),
            'storage-path'       => $instance->getStoragePath(),
            'private-path'       => $instance->getPrivatePath(),
            'owner-private-path' => $instance->getOwnerPrivatePath(),
            'snapshot-path'      => $instance->getSnapshotPath(),
            'trash-path'         => $instance->getTrashPath(),
            'package-path'       => $instance->getPackagePath(),
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
            'cluster-id'           => $cluster->cluster_id_text,
            'instance-id'          => $instance->instance_name_text,
            'default-domain'       => $cluster->subdomain_text,
            'signature-method'     => config('dfe.signature-method', EnterpriseDefaults::DEFAULT_SIGNATURE_METHOD),
            'storage-root'         => config('provisioning.storage-root', EnterprisePaths::MOUNT_POINT . EnterprisePaths::STORAGE_PATH),
            'console-api-url'      => config('dfe.security.console-api-url'),
            'console-api-key'      => config('dfe.security.console-api-key'),
            'client-id'            => $key ? $key->client_id : null,
            'client-secret'        => $key ? $key->client_secret : null,
            'audit-host'           => config('dfe.audit.host'),
            'audit-port'           => config('dfe.audit.port'),
            'audit-message-format' => config('dfe.audit.message-format'),
            'packages'             => $instance->getPackages(),
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
        $_limits = Limit::byClusterInstance($instance->cluster_id, $instance->id)->get();

        $_api_array = [];

        // Added label_text so the instance can identify which limit was triggered in the 429 message

        foreach ($_limits as $_limit) {
            $_api_array[$_limit->limit_key_text] = [
                'limit'  => $_limit->limit_nbr,
                'period' => $_limit->period_nbr,
                'name'   => $_limit->label_text,
            ];
        }

        // In the future, there could be additional keys, such as 'bandwidth' or 'storage'
        return [
            'api' => (array)$_api_array,
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
            'id'                    => $instance->dbServer ? $instance->dbServer->server_id_text : $instance->db_server_id,
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
            'db-server-id'          => $instance->dbServer ? $instance->dbServer->server_id_text : $instance->db_server_id,
        ];
    }

    /**
     * Returns a connection to the instance's database
     *
     * @param \DreamFactory\Enterprise\Database\Models\Instance|null $instance The instance to connect, or $this will be used.
     *
     * @return \Illuminate\Database\Connection
     */
    public function instanceConnection(Instance $instance = null)
    {
        $instance = $instance ?: $this;

        $_id = 'database.connections.' . $instance->instance_id_text;
        config(['database.connections.' . $_id => static::buildConnectionArray($instance)]);

        return \DB::connection($_id);
    }

    /**
     * Returns the endpoint of a provisioned instance
     *
     * @param bool $protocol If true, the default, the protocol is prepended creating a full URL. False to return just the host name
     *
     * @return string
     */
    public function getProvisionedEndpoint($protocol = true)
    {
        $_proto = $protocol ? (config('dfe.default-domain-protocol', EnterpriseDefaults::DEFAULT_DOMAIN_PROTOCOL) . '://') : null;

        return $_proto . $this->instance_name_text . '.' . trim($this->cluster->subdomain_text, '.');
    }

    /**
     * Creates token to talk to the instance
     *
     * @return string
     */
    public function generateToken()
    {
        $_md = $this->getMetadata(false, 'env');
        $_token = hash(config('dfe.signature-method', EnterpriseDefaults::SIGNATURE_METHOD),
            $_md['cluster-id'] . $_md['instance-id']);

        //logger('generated token "' . $_token . '" for "' . $_hash . '"');

        return $_token;
    }

    /**
     * @param string $resource A resource to retrieve
     * @param array  $payload  Any payload to send
     * @param array  $options  Any guzzle options to use
     * @param string $method   The HTTP method to use
     *
     * @return array|bool|\stdClass
     */
    public function getResource($resource, $payload = [], $options = [], $method = Request::METHOD_GET)
    {
        static $_prefix;

        !$_prefix && $_prefix = config('provisioners.hosts.' . GuestLocations::resolve($this->guest_location_nbr) . '.resource-prefix');

        return $this->call(Uri::segment([$_prefix, $resource]), $payload, $options, $method);
    }

    /**
     * Makes a shout out to an instance's private back-end. Should be called bootyCall()  ;)
     *
     * @param string $uri     The REST uri (i.e. "/[rest|api][/v[1|2]]/db", "/rest/system/users", etc.) to retrieve from the instance
     * @param array  $payload Any payload to send with request
     * @param array  $options Any options to pass to transport layer
     * @param string $method  The HTTP method. Defaults to "POST"
     * @param bool   $object  If true, the default, the response is returned as an object. If false, an array is returned.
     *
     * @return array|bool|\stdClass
     */
    public function call($uri, $payload = [], $options = [], $method = Request::METHOD_POST, $object = true)
    {
        $_token = $this->generateToken();

        $options['headers'] = array_merge(array_get($options, 'headers', []), [EnterpriseDefaults::CONSOLE_X_HEADER => $_token,]);
        $options['headers']['Content-Type'] = array_get($options['headers'], 'Content-Type', 'application/json');

        try {
            return $this->guzzleAny(Uri::segment([$this->getProvisionedEndpoint(), $uri], false),
                $payload,
                $options,
                $method,
                $object);
        } catch (\Exception $_ex) {
            return false;
        }
    }

    /**
     * @return string the base resource URI to use
     */
    public function getResourceUri()
    {
        return config('provisioners.hosts.' . GuestLocations::resolve($this->guest_location_nbr) . '.resource-uri');
    }

    /**
     * Returns the packages in the metadata. NON-REFRESHING! Intended to be called late.
     *
     * @return array
     */
    public function getPackages()
    {
        return $this->packages ?: [];
    }

    /**
     * @param string|array $packages
     *
     * @return \DreamFactory\Enterprise\Database\Models\Instance
     */
    public function setPackages($packages)
    {
        $this->packages = Ini::parseDelimitedString($packages);

        return $this;
    }
}
