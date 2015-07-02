<?php namespace DreamFactory\Enterprise\Database\Models;

class InstanceJanitor extends EnterpriseModel
{
    //******************************************************************************
    //* Members
    //******************************************************************************

    /**
     * @type string The table name
     */
    protected $table = 'instance_janitor_t';

    //******************************************************************************
    //* Methods
    //******************************************************************************

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->hasOne(static::MODEL_NAMESPACE . 'ServiceUser', 'user_id');
    }
}