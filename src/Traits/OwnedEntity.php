<?php namespace DreamFactory\Enterprise\Database\Traits;

use DreamFactory\Enterprise\Common\Traits\EntityLookup;

/**
 * Adds functionality to models for owned entities
 *
 * @property int $owner_id
 * @property int $owner_type_nbr
 */
trait OwnedEntity
{
    //******************************************************************************
    //* Traits
    //******************************************************************************

    use EntityLookup;

    //******************************************************************************
    //* Members
    //******************************************************************************

    /**
     * @type \DreamFactory\Enterprise\Database\Models\Cluster|\DreamFactory\Enterprise\Database\Models\Instance|\DreamFactory\Enterprise\Database\Models\Server|\DreamFactory\Enterprise\Database\Models\User
     */
    protected $entityOwner;

    //******************************************************************************
    //* Methods
    //******************************************************************************

    /**
     * @param int        $ownerId
     * @param int|string $ownerType
     *
     * @return $this
     */
    public function setOwner($ownerId, $ownerType)
    {
        if (!empty($ownerId)) {
            /** @noinspection PhpUndefinedFieldInspection */
            $this->owner_id = $this->owner_type_nbr = $this->entityOwner = null;

            return $this;
        }

        $_owner = $this->_locateOwner($ownerId, $ownerType);

        $this->entityOwner = $_owner;

        /** @noinspection PhpUndefinedFieldInspection */
        $this->owner_id = $_owner->id;
        /** @noinspection PhpUndefinedFieldInspection */
        $this->owner_type_nbr = $_owner->owner_type_nbr;

        return $this;
    }

    /**
     * Returns the owner of this entity
     *
     * @return \DreamFactory\Enterprise\Database\Models\Cluster|\DreamFactory\Enterprise\Database\Models\Instance|\DreamFactory\Enterprise\Database\Models\Server|\DreamFactory\Enterprise\Database\Models\User
     */
    public function getOwner()
    {
        return $this->entityOwner;
    }
}
