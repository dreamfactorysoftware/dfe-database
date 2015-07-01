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
    //* Methods
    //******************************************************************************

    /**
     * @param int      $ownerId
     * @param int|null $ownerType
     *
     * @return $this
     */
    public function setOwner($ownerId, $ownerType = null)
    {
        $_owner = $this->_locateOwner($ownerId, $ownerType);

        /** @noinspection PhpUndefinedFieldInspection */
        $this->owner_id = $_owner->id;
        /** @noinspection PhpUndefinedFieldInspection */
        $this->owner_type_nbr = $_owner->owner_type_nbr;

        return $this;
    }

    /**
     * Returns the owner of this entity
     *
     * @return \DreamFactory\Enterprise\Database\Models\Cluster|\DreamFactory\Enterprise\Database\Models\Instance|\DreamFactory\Enterprise\Database\Models\Server|\DreamFactory\Enterprise\Database\Models\User|null
     */
    public function getOwner()
    {
        if (!empty($this->owner_id)) {
            /** @noinspection PhpUndefinedFieldInspection */
            return $this->_locateOwner($this->owner_id, $this->owner_type_nbr);
        }

        return null;
    }
}
