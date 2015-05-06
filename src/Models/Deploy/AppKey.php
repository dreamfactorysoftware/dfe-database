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
 * @method static Builder byOwner( int $ownerId )
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

                if ( null === ( $_key = config( 'dfe.console-api-key', config( 'dashboard.console-api-key' ) ) ) )
                {
                    throw new \RuntimeException( 'Cannot find proper keys for application key creation. Please check your configuration.' );
                }

                $row->server_secret = $_key;

                if ( empty( $row->client_id ) )
                {
                    $row->client_id = hash_hmac( config( 'dfe.signature-method', 'sha256' ), str_random( 40 ), $_key );
                    $row->client_secret =
                        hash_hmac( config( 'dfe.signature-method', 'sha256' ), str_random( 40 ), $_key . $row->client_id );
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
        return $this->scopeByOwner( $query, $instanceId )->where( 'owner_type_nbr', OwnerTypes::INSTANCE );
    }

    /**
     * @param Builder $query
     * @param int     $ownerId
     *
     * @return Builder
     */
    public function scopeByOwner( $query, $ownerId )
    {
        return $query->where( 'owner_id', $ownerId );
    }
}