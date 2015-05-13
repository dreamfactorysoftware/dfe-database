<?php
namespace DreamFactory\Library\Fabric\Database\Models\Deploy;

use DreamFactory\Library\Fabric\Database\Enums\OwnerTypes;
use DreamFactory\Library\Fabric\Database\Models\DeployModel;

/**
 * snapshot_t
 *
 * @property string $snapshot_id_text
 * @property int    $user_id
 * @property int    $instance_id
 * @property string $url_text
 * @property string $expire_date
 */
class Snapshot extends DeployModel
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