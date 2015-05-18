<?php
namespace DreamFactory\Enterprise\Database\Models;

use DreamFactory\Enterprise\Database\Enums\OwnerTypes;

/**
 * snapshot_t
 *
 * @property string $snapshot_id_text
 * @property int    $user_id
 * @property int    $instance_id
 * @property string $url_text
 * @property string $expire_date
 */
class Snapshot extends EnterpriseModel
{
    //******************************************************************************
    //* Members
    //******************************************************************************

    /**
     * @type string The table name
     */
    protected $table = 'snapshot_t';
    /** @inheritdoc */
    protected static $_assignmentOwnerType = OwnerTypes::USER;

    //******************************************************************************
    //* Methods
    //******************************************************************************

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function user()
    {
        return
            $this->hasOne( static::DEPLOY_NAMESPACE . '\\User', 'id', 'user_id' );
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function instance()
    {
        return
            $this->hasOne( static::DEPLOY_NAMESPACE . '\\Instance', 'id', 'instance_id' );
    }
}