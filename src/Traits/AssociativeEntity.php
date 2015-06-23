<?php namespace DreamFactory\Enterprise\Database\Traits;

use DreamFactory\Enterprise\Database\Enums\OwnerTypes;
use DreamFactory\Enterprise\Database\Models\AssociativeEntityOwner;
use DreamFactory\Enterprise\Database\Models\EnterpriseModel;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Adds "associative entity" functionality to models
 */
trait AssociativeEntity
{
    //******************************************************************************
    //* Members
    //******************************************************************************

    /**
     * @type array An array of owner configuration information
     */
    protected $aeoInfo = false;
    /**
     * @type int The type of owner
     */
    protected $aeoType = null;

    //******************************************************************************
    //* Methods
    //******************************************************************************

    /**
     * Add the entity to the association
     *
     * @param int $toId
     * @param int $toType
     *
     * @return bool
     */
    public function associateEntity($toId, $toType = null)
    {
        $_result = [];
        $_map = $this->getOwnerInfo($toType ?: $this->aeoType);

        foreach ($_map as $_ownerType => $_ownerMap) {
            //  Only map a specific type?
            if ($toType && $toType != $_ownerType) {
                continue;
            }

            //  Map it
            $_result[$_ownerType] = false;
            $_assocClass = array_get($_map, 'associative-entity');

            if (empty($_assocClass)) {
                if (null !== ($_column = array_get($_map, 'owner-class-key'))) {
                    $this->{$_column} = $toId;
                    $_result[$_ownerType] = true;
                }
            } else {
                /** @type \DreamFactory\Enterprise\Database\Models\AssociativeEntityOwner $_assoc */
                $_assoc = new $_assocClass();
                /** @noinspection PhpUndefinedFieldInspection */
                /** @noinspection PhpUndefinedMethodInspection */
                $_result[$_ownerType] =
                    (1 == $_assoc->insert(
                            [
                                $_map['foreign-key']     => $toId,
                                $_map['owner-class-key'] => $this->id,
                            ]
                        )
                    );

                if ($_result[$_ownerType]) {
                    \Log::debug('--==**>> created association to "' . $_assoc->getTable() . '"');
                } else {
                    \Log::error('Failed to create association to "' . $_assoc->getTable() . '"');
                }
            }
        }

        return $_result;
    }

    /**
     * Remove the entity from the association
     *
     * @param int|string|EnterpriseModel $fromId
     * @param int                        $fromType
     *
     * @return bool True if removed from servitude
     */
    public function disassociateEntity($fromId, $fromType = null)
    {
        $_result = [];
        $_map = $this->getOwnerInfo($fromType ?: $this->aeoType);

        foreach ($_map as $_ownerType => $_ownerMap) {
            //  Map it
            $_result[$_ownerType] = false;

            //  Only map a specific type?
            if ($fromType && $fromType != $_ownerType) {
                continue;
            }

            $_assocClass = array_get($_map, 'associative-entity');

            if (empty($_assocClass)) {
                if (null !== ($_column = array_get($_map, 'owner-class-key'))) {
                    $this->{$_column} = $fromId;

                    //  If $model->owner_id exists, populate $model->owner_type_nbr
                    'owner_id' == $_column && isset($this->owner_type_nbr) && ($this->owner_type_nbr = $_ownerType);

                    $_result[$_ownerType] = true;
                }
            } else {
                /** @type \DreamFactory\Enterprise\Database\Models\AssociativeEntityOwner $_assoc */
                $_assoc = new $_assocClass();
                /** @noinspection PhpUndefinedFieldInspection */
                /** @noinspection PhpUndefinedMethodInspection */
                /** @noinspection PhpUndefinedFieldInspection */
                $_count = $_assoc->where($_map['foreign-key'], $fromId)
                    ->where($_map['owner-class-key'], $this->id)
                    ->delete();

                if ($_count) {
                    \Log::debug('--==**>> deleted association from "' . $_assoc->getTable() . '"');
                } else {
                    \Log::error('Failed to remove association from "' . $_assoc->getTable() . '"');
                }

                $_result[$_ownerType] = $_count;
            }
        }

        return $_result;
    }

    /**
     * Retrieves the current owner type
     *
     * @return string
     */
    public function getOwnerType()
    {
        return $this->aeoType;
    }

    /**
     * Sets the current owner type and loads the associated map
     *
     * @param string|null $ownerType
     */
    public function setOwnerType($ownerType = null)
    {
        //  Use static if null
        $ownerType = $ownerType ?: $this->aeoType;

        //  Validate
        if (!OwnerTypes::contains($ownerType)) {
            throw new \InvalidArgumentException('The owner type "' . $ownerType . '" is invalid.');
        }

        //  Set the type and load the map for it
        $this->aeoType = $ownerType;
        $this->getOwnerInfo($ownerType);
    }

    /**
     * @param string      $ownerId
     * @param string|null $ownerType
     *
     * @return AssociativeEntityOwner|\DreamFactory\Enterprise\Database\Models\Cluster|\DreamFactory\Enterprise\Database\Models\EnterpriseModel|\DreamFactory\Enterprise\Database\Models\Instance|\DreamFactory\Enterprise\Database\Models\Server|\DreamFactory\Enterprise\Database\Models\User|\stdClass
     */
    protected function getOwner($ownerId = null, $ownerType = null)
    {
        $_map = $this->getOwnerInfo($ownerType ?: $this->aeoType);

        if (null === $ownerType && count($_map) > 1) {
            throw new \InvalidArgumentException('Multiple owners exists for this model. You must specify an $ownerType.');
        }

        foreach ($_map as $_type => $_info) {
            //  Skip to our type if we have one
            if ($ownerType && $ownerType != $_type) {
                continue;
            }

            if (null !== ($_column = array_get($_info, 'owner-class-key'))) {
                empty($ownerId) && ($ownerId = $this->{$_column});
                empty($ownerType) && ($ownerType = $_type);
                break;
            }
        }

        if (!$ownerId) {
            throw new ModelNotFoundException('The owner could not be found.');
        }

        $_owner = OwnerTypes::getOwner($ownerId, $ownerType);

        //  Set our values
        $this->aeoType = $ownerType;
        $this->aeoInfo = $this->getOwnerInfo($ownerType);

        return $_owner;
    }

    /**
     * Gets the owner type mappings. Also sets static::$aeoInfo
     *
     * @param int $ownerType
     *
     * @return array
     */
    protected function getOwnerInfo($ownerType)
    {
        return $this->aeoInfo
            ?: $this->aeoInfo = OwnerTypes::getOwnerInfo($ownerType, false);
    }
}
