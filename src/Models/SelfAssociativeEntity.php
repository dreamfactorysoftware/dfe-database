<?php namespace DreamFactory\Enterprise\Database\Models;

/**
 * Base class for entities that refer back to themselves
 */
class SelfAssociativeEntity extends EnterpriseModel
{
    //******************************************************************************
    //* Members
    //******************************************************************************

    /**
     * @type string The column name holding the owner id
     */
    protected $saeColumn;
    /**
     * @type string The type of owner
     */
    protected $saeType;

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
        $this->{$this->saeColumn} = $ownerId;
        $ownerType && isset($this->owner_type_nbr) && ($this->owner_type_nbr = $ownerType);

        return $this;
    }

    /**
     * @return string
     */
    public function getOwnerColumn()
    {
        return $this->saeColumn;
    }

    /**
     * @param string $column
     *
     * @return $this
     */
    public function setOwnerColumn($column)
    {
        $this->saeColumn = $column;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getOwnerType()
    {
        return $this->saeType;
    }

    /**
     * @param int $ownerType
     *
     * @return $this
     */
    public function setOwnerType($ownerType)
    {
        $this->saeType = $ownerType;

        return $this;
    }

}