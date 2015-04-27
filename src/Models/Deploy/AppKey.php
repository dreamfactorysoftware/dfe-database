<?php
namespace DreamFactory\Library\Fabric\Database\Models\Deploy;

use DreamFactory\Library\Fabric\Database\Models\DeployModel;

/**
 * app_key_t
 *
 * @property string client_id
 * @property string client_secret
 * @property int    owner_id
 * @property int    owner_type_nbr
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

    /**
     * @type string The table name
     */
    protected $table = 'app_key_t';

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
                if ( empty( $row->app_id_text ) )
                {
                    $row->app_id_text = '[entity:unknown]';
                }

                if ( null === ( $_key = config( 'dfe.client-hash-key', config( 'dashboard.client-hash-key' ) ) ) )
                {
                    throw new \RuntimeException( 'Cannot find proper keys for application key creation. Please check your configuration.' );
                }

                $row->client_id = hash_hmac( 'sha256', str_random( 40 ), $_key );
                $row->client_secret = hash_hmac( 'sha256', str_random( 40 ), $_key . $row->client_id );
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

}