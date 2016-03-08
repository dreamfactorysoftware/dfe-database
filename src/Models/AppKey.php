<?php namespace DreamFactory\Enterprise\Database\Models;

use DreamFactory\Enterprise\Database\Enums\AppKeyClasses;
use DreamFactory\Enterprise\Common\Enums\EnterpriseDefaults;
use DreamFactory\Enterprise\Common\Traits\StaticComponentLookup;
use DreamFactory\Enterprise\Database\Contracts\OwnedEntity;
use DreamFactory\Enterprise\Database\Enums\OwnerTypes;
use DreamFactory\Enterprise\Database\Traits\Gatekeeper;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Facades\Log;

/**
 * app_key_t
 *
 * @property int    $id
 * @property string $key_class_text
 * @property string $client_id
 * @property string $client_secret
 * @property int    $owner_id
 * @property int    $owner_type_nbr
 * @property string $label_text
 * @property int    $active_ind
 * @property string $created_at
 * @property string $updated_at
 *
 * @method static Builder forInstance($instanceId)
 * @method static Builder byOwner($ownerId, $ownerType = null)
 * @method static Builder byOwnerType($ownerType)
 * @method static Builder byClass($keyClass, $ownerId = null)
 * @method static Builder byClientId($clientId)
 */
class AppKey extends EnterpriseModel implements OwnedEntity
{
    //******************************************************************************
    //* Traits
    //******************************************************************************

    use Gatekeeper, StaticComponentLookup;

    //******************************************************************************
    //* Constants
    //******************************************************************************

    /** @inheritdoc */
    const CREATED_AT = 'created_at';
    /** @inheritdoc */
    const UPDATED_AT = 'updated_at';

    //******************************************************************************
    //* Members
    //******************************************************************************

    /** @inheritdoc */
    protected $table = 'app_key_t';
    /** @inheritdoc */
    protected $hidden = ['server_secret', 'client_secret'];
    /** @inheritdoc */
    protected $casts = [
        'id'             => 'integer',
        'owner_id'       => 'integer',
        'owner_type_nbr' => 'integer',
        'active_ind'     => 'boolean',
    ];

    //******************************************************************************
    //* Methods
    //******************************************************************************

    /**
     * Generate a client id and secret
     */
    protected static function boot()
    {
        parent::boot();

        //  Fired before creating or updating...
        static::saving(function($row) {
            static::enforceBusinessLogic($row);

            if (empty($row->server_secret)) {
                $row->server_secret = config('dfe.security.console-api-key', 'this-value-is-not-set');
            }
        });

        static::creating(function($row) {
            if (empty($row->key_class_text)) {
                $row->key_class_text = AppKeyClasses::OTHER;
            }

            if (empty($row->client_id) || empty($row->client_secret)) {
                $_algorithm = config('dfe.signature-method', EnterpriseDefaults::DEFAULT_SIGNATURE_METHOD);

                $row->client_id = hash_hmac($_algorithm, str_random(40), $row->server_secret);
                $row->client_secret = hash_hmac($_algorithm, str_random(40), $row->server_secret . $row->client_id);
            }
        });
    }

    /**
     * Definition of the "owner" relationship
     *
     * @return BelongsTo|MorphTo|MorphToMany|BelongsToMany|mixed
     */
    public function owner()
    {
        return $this->morphTo('owner', 'owner_type_nbr', 'owner_id');
    }

    /** @inheritdoc */
    public function getMorphClass()
    {
        return $this->owner_type_nbr;
    }

    /**
     * @param Builder $query
     * @param int     $instanceId
     *
     * @return Builder
     */
    public function scopeForInstance($query, $instanceId)
    {
        return $this->scopeByOwner($query, $instanceId, OwnerTypes::INSTANCE);
    }

    /**
     * @param Builder $query
     * @param int     $ownerId
     * @param int     $ownerType
     *
     * @return Builder
     */
    public function scopeByOwner($query, $ownerId, $ownerType = null)
    {
        $_query = $query->where('owner_id', $ownerId);

        if (null !== $ownerType) {
            $_query = $_query->where('owner_type_nbr', $ownerType);
        }

        return $_query;
    }

    /**
     * @param Builder $query
     * @param string  $clientId
     *
     * @return Builder
     */
    public function scopeByClientId($query, $clientId)
    {
        return $query->where('client_id', $clientId);
    }

    /**
     * @param Builder $query
     * @param string  $keyClass
     * @param int     $ownerId
     *
     * @return Builder
     */
    public function scopeByClass($query, $keyClass, $ownerId = null)
    {
        $_query = $query->where('key_class_text', $keyClass);

        if (null !== $ownerId) {
            $_query = $_query->where('owner_id', $ownerId);
        }

        return $_query;
    }

    /**
     * @param Builder $query
     * @param int     $ownerType
     *
     * @return Builder
     * @internal param int $ownerId
     *
     */
    public function scopeByOwnerType($query, $ownerType)
    {
        return $query->where('owner_type_nbr', $ownerType);
    }

    /**
     * @param int        $ownerId
     * @param string|int $ownerType
     * @param array      $fill Any extra attributes to update
     *
     * @return bool|AppKey False if owner is not authorized or on error, otherwise the created AppKey model is returned
     */
    public static function createKey($ownerId, $ownerType, $fill = [])
    {
        $_owner = OwnerTypes::getOwner($ownerId, $ownerType);

        return static::_makeKey($_owner->id, $ownerType, AppKeyClasses::fromOwnerType($ownerType), $fill);
    }

    /**
     * @param int        $ownerId
     * @param string|int $ownerType
     * @param string     $keyClass
     * @param array      $fill Any extra attributes to update
     *
     * @return bool|\DreamFactory\Enterprise\Database\Models\AppKey False if owner is not authorized or on error, otherwise the created AppKey model
     *                                                              is returned
     * @throws \Exception
     */
    protected static function _makeKey($ownerId, $ownerType, $keyClass, $fill = [])
    {
        try {
            return static::create(array_merge($fill,
                [
                    'owner_id'       => $ownerId,
                    'owner_type_nbr' => $ownerType,
                    'key_class_text' => $keyClass,
                ]));
        } catch (\Exception $_ex) {
            Log::error('Error creating app_key for ownerId ' . $ownerId);
            throw $_ex;
        }
    }

    /**
     * @param EnterpriseModel $entity
     * @param int|null        $ownerType
     *
     * @return bool|\DreamFactory\Enterprise\Database\Models\AppKey return a new key for the $entity or false if the entity is not recognized
     * @throws \Exception
     */
    public static function createKeyForEntity(EnterpriseModel $entity, $ownerType = null)
    {
        $_type = $ownerType ?: OwnerTypes::getTypeFromModel($entity);

        if (null === $_type) {
            \Log::error('Entity "' . get_class($entity) . '" has no associated OWNER TYPE.');

            return false;
        }

        $_ownerId = $entity->id;

        $_key = static::_makeKey($_ownerId, $_type, AppKeyClasses::fromOwnerType($_type));
        ($entity instanceof User) && $entity->update(['api_token_text' => $_key->client_id]);

        return $_key;
    }

    /**
     * @param EnterpriseModel $entity
     *
     * @return bool|AppKey False if entity is not authorized otherwise the created AppKey model is returned
     * @deprecated All calling code should use static::createKeyForEntity
     */
    public static function createKeyFromEntity(EnterpriseModel $entity)
    {
        return static::createKeyForEntity($entity);
    }

    /**
     * Destroys all keys owned by this $entity
     *
     * @param EnterpriseModel $entity
     *
     * @return bool|int
     */
    public static function destroyKeys(EnterpriseModel $entity)
    {
        if (false === (list($_ownerId, $_ownerType) = static::_getOwnerType($entity))) {
            //  Unnecessary
            return false;
        }

        return static::byOwner($_ownerId, $_ownerType)->delete();
    }

    /**
     * Get an entity's owner and type
     *
     * @param EnterpriseModel $entity
     *
     * @return array|bool Array of attributes ['owner_id' => int, 'owner_type_nbr' => int] or FALSE if no key required
     */
    protected static function _getOwnerType(EnterpriseModel $entity)
    {
        //  Don't bother with archive or assignment tables
        if (!in_array(substr($entity->getTable(), -7), ['_asgn_t', '_arch_t'])) {
            //  Try user/service_user first
            if ($entity instanceof User) {
                return [$entity->id, OwnerTypes::USER];
            }

            if ($entity instanceof ServiceUser) {
                return [$entity->id, OwnerTypes::SERVICE_USER];
            }

            //  Anything with owner and type get tagged
            if (isset($entity->owner_id, $entity->owner_type_nbr)) {
                //  No owner to speak of...
                if (empty($entity->owner_id) && empty($entity->owner_type_nbr)) {
                    return [null, null];
                }

                return [$entity->owner_id, $entity->owner_type_nbr];
            }

            //  A user_id only means a user owns the entity (can't be zero either...)
            if (isset($entity->user_id) && !empty($entity->user_id)) {
                return [$entity->user_id, OwnerTypes::USER];
            }
        }

        return [null, null];
    }

    /**
     * @param string $keyClass The key classes to return, otherwise all
     * @param int    $ownerId  The owner of the key
     *
     * @return \Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Eloquent\Model|null|static|static[]
     */
    public static function getKeys($keyClass = AppKeyClasses::USER, $ownerId = null)
    {
        if (empty($keyClass)) {
            return static::byOwner($ownerId)->get();
        }

        return static::byClass($keyClass, $ownerId)->get();
    }

    /**
     * @param int $ownerId
     * @param int $ownerType
     *
     * @return AppKey
     */
    public static function mine($ownerId, $ownerType)
    {
        return static::byOwner($ownerId, $ownerType)->first();
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    /** @inheritdoc */
    protected static function enforceBusinessLogic($row)
    {
        //  If there is no owner, there can't be a type.
        null === $row->owner_id && $row->owner_type_nbr = null;
    }
}
