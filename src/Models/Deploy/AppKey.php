<?php
namespace DreamFactory\Library\Fabric\Database\Models\Deploy;

use DreamFactory\Enterprise\Common\Enums\AppKeyClasses;
use DreamFactory\Library\Fabric\Database\Enums\OwnerTypes;
use DreamFactory\Library\Fabric\Database\Models\DeployModel;
use Illuminate\Database\Query\Builder;

/**
 * app_key_t
 *
 * @property string client_id
 * @property string client_secret
 * @property int    owner_id
 * @property int    owner_type_nbr
 *
 * @method static Builder forInstance( int $instanceId )
 * @method static Builder byOwner( int $ownerId, int $ownerType = null )
 * @method static Builder byOwnerType( int $ownerType )
 * @method static Builder byClass( string $keyClass, int $ownerId = null )
 */
class AppKey extends DeployModel
{
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

    //******************************************************************************
    //* Methods
    //******************************************************************************

    /**
     * Generate a client id and secret
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(
            function ( $row )
            {
                if ( empty( $row->key_class_text ) )
                {
                    $row->key_class_text = AppKeyClasses::OTHER;
                }

                if ( empty( $row->server_secret ) )
                {
                    if ( null === ( $_key = config( 'dfe-ops-client.console-api-key', config( 'dfe.console-api-key' ) ) ) )
                    {
                        throw new \RuntimeException( 'Please ensure "dfe-ops-client" is installed and configured properly.' );
                    }

                    $row->server_secret = $_key;
                }

                if ( empty( $row->client_id ) || empty( $row->client_secret ) )
                {
                    $_algorithm = config( 'dfe-ops-client.signature-method', config( 'dfe.signature-method', 'sha256' ) );

                    $row->client_id = hash_hmac( $_algorithm, str_random( 40 ), $row->server_secret );
                    $row->client_secret = hash_hmac( $_algorithm, str_random( 40 ), $row->server_secret . $row->client_id );
                }
            }
        );
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo( static::DEPLOY_NAMESPACE . '\\User', 'owner_id', 'id' );
    }

    /**
     * @param Builder $query
     * @param int     $instanceId
     *
     * @return Builder
     */
    public function scopeForInstance( $query, $instanceId )
    {
        return $this->scopeByOwner( $query, $instanceId, OwnerTypes::INSTANCE );
    }

    /**
     * @param Builder $query
     * @param int     $ownerId
     * @param int     $ownerType
     *
     * @return Builder
     */
    public function scopeByOwner( $query, $ownerId, $ownerType = null )
    {
        $query = $query->where( 'owner_id', $ownerId );

        if ( null !== $ownerType )
        {
            $query = $query->where( 'owner_type_nbr', $ownerType );
        }

        return $query;
    }

    /**
     * @param Builder $query
     * @param string  $keyClass
     * @param int     $ownerId
     *
     * @return Builder
     */
    public function scopeByClass( $query, $keyClass, $ownerId = null )
    {
        $query = $query->where( 'key_class_text', $keyClass );

        if ( null !== $ownerId )
        {
            $query = $query->where( 'owner_id', $ownerId );
        }

        return $query;
    }

    /**
     * @param Builder $query
     * @param int     $ownerType
     *
     * @return Builder
     * @internal param int $ownerId
     *
     */
    public function scopeByOwnerType( $query, $ownerType )
    {
        return $query->where( 'owner_type_nbr', $ownerType );
    }

    /**
     * @param int        $ownerId
     * @param string|int $ownerType
     * @param array      $fill Any extra attributes to update
     *
     * @return bool|AppKey False if owner is not authorized or on error, otherwise the created AppKey model is returned
     */
    public static function createKey( $ownerId, $ownerType, $fill = [] )
    {
        $_owner = OwnerTypes::getOwner( $ownerId, $ownerType );

        return static::_makeKey( $_owner->id, $ownerType, AppKeyClasses::fromOwnerType( $ownerType ), $fill );
    }

    /**
     * @param int        $ownerId
     * @param string|int $ownerType
     * @param string     $keyClass
     * @param array      $fill Any extra attributes to update
     *
     * @return bool|AppKey False if owner is not authorized or on error, otherwise the created AppKey model is returned
     */
    protected static function _makeKey( $ownerId, $ownerType, $keyClass, $fill = [] )
    {
        $_model = new static();
        $_model->fill(
            array_merge(
                $fill,
                [
                    'owner_id'       => $ownerId,
                    'owner_type_nbr' => $ownerType,
                    'key_class_text' => $keyClass,
                ]
            )
        );

        if ( !$_model->save() )
        {
            throw new \LogicException( 'Key creation fail' );
        }

        return $_model;
    }

    /**
     * @param DeployModel $entity
     *
     * @return bool|AppKey False if entity is not authorized otherwise the created AppKey model is returned
     */
    public static function createKeyFromEntity( DeployModel $entity )
    {
        list( $_ownerId, $_ownerType ) = static::_getOwnerType( $entity );

        if ( null === $_ownerId && null === $_ownerType )
        {
            \Log::debug( 'authorization key NOT created for new row: ' . $entity->getTable() );

            return false;
        }

        return static::_makeKey( $_ownerId, $_ownerType, AppKeyClasses::fromOwnerType( $_ownerType ) );
    }

    /**
     * Destroys all keys owned by this $entity
     *
     * @param DeployModel $entity
     *
     * @return bool|int
     */
    public static function destroyKeys( DeployModel $entity )
    {
        if ( false === ( list( $_ownerId, $_ownerType ) = static::_getOwnerType( $entity ) ) )
        {
            //  Unnecessary
            return false;
        }

        return static::byOwner( $_ownerId, $_ownerType )->delete();
    }

    /**
     * Get an entity's owner and type
     *
     * @param DeployModel $entity
     *
     * @return array|bool Array of attributes ['owner_id' => int, 'owner_type_nbr' => int] or FALSE if no key required
     */
    protected static function _getOwnerType( DeployModel $entity )
    {
        //  Don't bother with archive or assignment tables
        if ( !in_array( substr( $entity->getTable(), -7 ), ['_asgn_t', '_arch_t'] ) )
        {
            //  Anything with owner and type get tagged
            if ( isset( $entity->owner_id, $entity->owner_type_nbr ) )
            {
                //  No owner to speak of...
                if ( 0 == $entity->owner_id && empty( $entity->owner_type_nbr ) )
                {
                    return [null, null];
                }

                return [$entity->owner_id, $entity->owner_type_nbr];
            }

            //  A user_id only means a user owns the entity (can't be zero either...)
            if ( isset( $entity->user_id ) && !empty( $entity->user_id ) )
            {
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
    public static function getKeys( $keyClass = AppKeyClasses::USER, $ownerId = null )
    {
        if ( empty( $keyClass ) )
        {
            return static::byOwner( $ownerId )->get();
        }

        return static::byClass( $keyClass, $ownerId )->get();
    }

}