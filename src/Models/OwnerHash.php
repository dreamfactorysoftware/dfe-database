<?php namespace DreamFactory\Enterprise\Database\Models;

use DreamFactory\Enterprise\Database\Enums\OwnerTypes;

class OwnerHash extends AssociativeEntityOwner
{
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
     * @param array $attributes
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        //  Servers are the owner of mounts for association
        $this->owner_type_nbr = OwnerTypes::USER;
    }

    /**
     * Our owners
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function owners()
    {
        return $this->hasMany(__NAMESPACE__ . '\\User', 'id', 'owner_id');
    }

}