<?php namespace DreamFactory\Enterprise\Database\Models;

/**
 * instance_guest_t
 *
 * @property integer  $instance_id
 * @property integer  $vendor_id
 * @property integer  $vendor_image_id
 * @property integer  $vendor_credentials_id
 * @property integer  $flavor_nbr
 * @property string   $base_image_text
 * @property string   $region_text
 * @property string   $availability_zone_text
 * @property string   $security_group_text
 * @property string   $ssh_key_text
 * @property integer  $root_device_type_nbr
 * @property string   $public_host_text
 * @property string   $public_ip_text
 * @property string   $private_host_text
 * @property string   $private_ip_text
 * @property integer  $state_nbr
 * @property string   $state_text
 *
 * Relations:
 *
 * @property Instance $instance
 */
class InstanceGuestArchive extends InstanceGuest
{
    //******************************************************************************
    //* Members
    //******************************************************************************

    /**
     * @type string The table name
     */
    protected $table = 'instance_guest_arch_t';

    //******************************************************************************
    //* Methods
    //******************************************************************************

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo|InstanceArchive
     */
    /** @noinspection PhpMissingParentCallCommonInspection */
    public function instance()
    {
        return $this->hasOne(static::MODEL_NAMESPACE . 'InstanceArchive', 'id', 'instance_id');
    }

}
