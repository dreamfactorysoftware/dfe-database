<?php namespace DreamFactory\Enterprise\Database\Traits;

use DreamFactory\Enterprise\Database\Enums\OwnerTypes;
use DreamFactory\Enterprise\Database\Models\AppKey;
use DreamFactory\Enterprise\Database\Models\ServiceUser;

/**
 * A trait for models that own app_key_t rows
 */
trait KeyHolder
{
    //******************************************************************************
    //* Methods
    //******************************************************************************

    /**
     * @param string $class   The key classes to return, otherwise all
     * @param int    $ownerId The owner of the key
     *
     * @return \Illuminate\Database\Eloquent\Collection|static[]|null
     */
    public function getKeysByClass($class, $ownerId = null)
    {
        return AppKey::byClass($class, $ownerId)->get();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection|static[]|null
     */
    public function getUserKeys()
    {
        /** @noinspection PhpUndefinedFieldInspection */

        return AppKey::byOwner($this->id, ($this instanceof ServiceUser) ? OwnerTypes::SERVICE_USER : OwnerTypes::USER)
            ->get();
    }

    /**
     * Retrieve rows matching a specific $clientId
     *
     * @param string $clientId
     *
     * @return \Illuminate\Database\Eloquent\Collection|null|static[]
     */
    public function getKeysByClientId($clientId)
    {
        return AppKey::byClientId($clientId)->get();
    }

}