<?php namespace DreamFactory\Enterprise\Database\Traits;

use DreamFactory\Enterprise\Database\Models\EnterpriseModel;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * A trait for models that are owners. Assumes morph table has *_id and *_type_nbr columns
 */
trait KeyMaster
{
    //******************************************************************************
    //* Methods
    //******************************************************************************

    /**
     * The relationship definition to retrieve all keys
     *
     * @param string $localKey
     * @param string $type
     * @param string $id
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function appKeys($localKey = null, $type = null, $id = null)
    {
        return $this->keyMaster($localKey, $type, $id);
    }

    /**
     * @param string $localKey
     * @param string $type
     * @param string $id
     *
     * @return MorphMany
     */
    protected function keyMaster($localKey = 'id', $type = 'owner_type_nbr', $id = 'owner_id')
    {
        /** @noinspection PhpUndefinedMethodInspection */
        return $this->morphMany(EnterpriseModel::MODEL_NAMESPACE . 'AppKey', 'gatekeeper', $type, $id, $localKey);
    }
}
