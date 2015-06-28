<?php namespace DreamFactory\Enterprise\Database\Models;

/**
 * snapshot_t
 *
 * @property int    $user_id
 * @property int    $instance_id
 * @property int    $route_hash_id
 * @property string $snapshot_id_text
 * @property int    $public_ind
 * @property string $public_url_text
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
    protected $casts = [
        'public_ind' => 'bool',
    ];

    //******************************************************************************
    //* Methods
    //******************************************************************************

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function user()
    {
        return
            $this->hasOne(static::DEPLOY_NAMESPACE . '\\User', 'id', 'user_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function instance()
    {
        return
            $this->hasOne(static::DEPLOY_NAMESPACE . '\\Instance', 'id', 'instance_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function routeHash()
    {
        return
            $this->hasOne(static::DEPLOY_NAMESPACE . '\\RouteHash', 'id', 'route_hash_id');
    }
}