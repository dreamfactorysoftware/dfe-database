<?php namespace DreamFactory\Enterprise\Database\Models;

use DreamFactory\Enterprise\Database\Enums\OwnerTypes;
use DreamFactory\Enterprise\Database\Traits\KeyMaster;

class OwnerHash extends EnterpriseModel
{
    //******************************************************************************
    //* Traits
    //******************************************************************************

    use KeyMaster;

    //******************************************************************************
    //* Members
    //******************************************************************************

    /**
     * @type string The table name
     */
    protected $table = 'owner_hash_t';

    //******************************************************************************
    //* Methods
    //******************************************************************************

    /**
     * Return the owner type of this model
     *
     * @return string
     */
    public function getMorphClass()
    {
        return OwnerTypes::USER;
    }

}