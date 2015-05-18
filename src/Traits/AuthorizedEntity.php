<?php namespace DreamFactory\Enterprise\Database\Traits;

use DreamFactory\Enterprise\Database\Models\AppKey;
use DreamFactory\Enterprise\Database\Models\BaseEnterpriseModel;

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
            function ( BaseEnterpriseModel $model )
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
        return $this->hasOne( BaseEnterpriseModel::DEPLOY_NAMESPACE . '\\AppKey', 'owner_id' );
    }

}
