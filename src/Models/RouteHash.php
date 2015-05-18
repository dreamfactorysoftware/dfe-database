<?php namespace DreamFactory\Enterprise\Database\Models;

/**
 * route_hash_t
 *
 * @property int    $type_nbr
 * @property int    $mount_id
 * @property string $hash_text
 * @property string $actual_path_text
 * @property string expireDate
 */
class RouteHash extends EnterpriseModel
{
    //******************************************************************************
    //* Members
    //******************************************************************************

    /**
     * @type string The table name
     */
    protected $table = 'route_hash_t';

    //******************************************************************************
    //* Methods
    //******************************************************************************

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function mount()
    {
        return
            $this->hasOne( __NAMESPACE__ . '\\Mount' );
    }
}