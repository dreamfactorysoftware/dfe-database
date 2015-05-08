<?php namespace DreamFactory\Library\Fabric\Database\Traits;

use DreamFactory\Library\Fabric\Database\Models\Deploy\AppKey;
use DreamFactory\Library\Fabric\Database\Models\DeployModel;

trait AuthorizedEntity
{
    //******************************************************************************
    //* Methods
    //******************************************************************************

    public static function boot()
    {
        static::created(
            function ( $model )
            {
                AppKey::createKeyFromEntity( $model );
            }
        );

        static::deleted(
            function ( DeployModel $model )
            {
                AppKey::destroyKeys( $model );
            }
        );
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function appKeys()
    {
        return $this->hasOne( DeployModel::DEPLOY_NAMESPACE . '\\AppKey', 'owner_id' );
    }

}
