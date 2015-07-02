<?php namespace DreamFactory\Enterprise\Database\Models;

use DreamFactory\Enterprise\Common\Traits\EntityLookup;

/**
 * Base class for entities that refer back to themselves
 */
class SelfAssociativeEntity extends EnterpriseModel
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
            $this->owner_id = $this->owner_type_nbr = $this->entityOwner = null;

            return $this;
        }

        $_owner = $this->_locateOwner($ownerId, $ownerType);

        $this->entityOwner = $_owner;

        $this->owner_id = $_owner->id;
        $this->owner_type_nbr = $_owner->owner_type_nbr;

        return $this;
    }

    /**
     * Returns the owner of this entity
     *
     * @param int        $ownerId
     * @param int|string $ownerType
     *
     * @return \DreamFactory\Enterprise\Database\Models\Cluster|\DreamFactory\Enterprise\Database\Models\Instance|\DreamFactory\Enterprise\Database\Models\Server|\DreamFactory\Enterprise\Database\Models\User
     */
    public function getOwner($ownerId, $ownerType)
    {
        return $this->entityOwner;
    }
}