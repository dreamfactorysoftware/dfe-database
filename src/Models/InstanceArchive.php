<?php namespace DreamFactory\Enterprise\Database\Models;

/**
 * instance_arch_t
 */
class InstanceArchive extends Instance
{
    //******************************************************************************
    //* Members
    //******************************************************************************

    /**
     * @type string The table name
     */
    protected $table = 'instance_arch_t';

    //******************************************************************************
    //* Methods
    //******************************************************************************

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function guest()
    {
        return $this->hasOne(__NAMESPACE__ . '\\InstanceGuestArchive', 'id', 'instance_id');
    }
}