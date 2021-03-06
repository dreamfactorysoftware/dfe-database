<?php namespace DreamFactory\Enterprise\Database\Models;

use DreamFactory\Enterprise\Common\Enums\ServerTypes;
use DreamFactory\Enterprise\Common\Traits\EntityLookup;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Query\Builder;

/**
 * server_t
 *
 * @property int    server_type_id
 * @property string server_id_text
 * @property int    mount_id
 * @property string host_text
 * @property array  config_text
 *
 * @property Mount  mount
 *
 * @method static Builder|\Illuminate\Database\Eloquent\Builder byNameOrId($nameOrId)
 */
class Server extends EnterpriseModel
{
    //******************************************************************************
    //* Traits
    //******************************************************************************

    use EntityLookup;

    //******************************************************************************
    //* Members
    //******************************************************************************

    /**
     * @type string The table name
     */
    protected $table = 'server_t';
    /** @inheritdoc */
    protected $casts = [
        'id'             => 'integer',
        'server_type_id' => 'integer',
        'mount_id'       => 'integer',
        'config_text'    => 'array',
    ];

    //******************************************************************************
    //* Methods
    //******************************************************************************

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne|ServerType
     */
    public function serverType()
    {
        return $this->belongsTo(static::MODEL_NAMESPACE . 'ServerType');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne|Mount
     */
    public function mount()
    {
        return $this->hasOne(static::MODEL_NAMESPACE . 'Mount', 'id', 'mount_id');
    }

    /**
     * Cluster in which I belong
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany|Cluster
     */
    public function cluster()
    {
        return Cluster::whereRaw('id IN (SELECT csa.cluster_id FROM cluster_server_asgn_t csa WHERE csa.server_id = :server_id)',
            [':server_id' => $this->id])->first();
    }

    /**
     * Returns a list of servers assigned to me
     *
     * @return Collection|InstanceServer[]
     */
    public function instances()
    {
        return InstanceServer::with('instance')->where('server_id', $this->id)->get();
    }

    /**
     * @param int|string|Cluster $clusterId
     *
     * @return bool True if server removed from cluster
     */
    public function removeFromCluster($clusterId)
    {
        $_cluster = $this->findCluster($clusterId);

        if ($this->belongsToCluster($_cluster->id)) {
            return 1 == ClusterServer::where('cluster_id', '=', $_cluster->id)->where('server_id', '=', $this->id)->delete();
        }

        return false;
    }

    /**
     * @param int|string $clusterId
     *
     * @return bool
     */
    public function addToCluster($clusterId)
    {
        $_cluster = $this->findCluster($clusterId);

        if (!$this->belongsToCluster($_cluster->id)) {
            return 1 == ClusterServer::insert(['cluster_id' => $_cluster->id, 'server_id' => $this->id]);
        }

        return false;
    }

    /**
     * @param int $clusterId
     *
     * @return bool True if this instance
     */
    public function belongsToCluster($clusterId)
    {
        return 0 != ClusterServer::whereRaw('cluster_id = :cluster_id AND server_id = :server_id',
            [
                ':cluster_id' => $clusterId,
                ':server_id'  => $this->id,
            ])->count();
    }

    /**
     * @param \Illuminate\Database\Query\Builder $query
     * @param string|int                         $nameOrId
     *
     * @return Builder
     */
    public function scopeByNameOrId($query, $nameOrId)
    {
        return $query->whereRaw('server_id_text = :server_id_text OR id = :id',
            [':server_id_text' => $nameOrId, ':id' => $nameOrId]);
    }

    /**
     * App servers only scope
     *
     * @param Builder $query
     *
     * @return Builder
     */
    public function scopeAppServers($query)
    {
        return $this->scopeByType($query, ServerTypes::APP);
    }

    /**
     * Db servers only scope
     *
     * @param Builder $query
     *
     * @return Builder
     */
    public function scopeDbServers($query)
    {
        return $this->scopeByType($query, ServerTypes::DB);
    }

    /**
     * Web servers only scope
     *
     * @param Builder $query
     *
     * @return Builder
     */
    public function scopeWebServers($query)
    {
        return $this->scopeByType($query, ServerTypes::WEB);
    }

    /**
     * Limit results by server type
     *
     * @param Builder $query
     * @param int     $typeId
     *
     * @return Builder
     */
    public function scopeByType($query, $typeId)
    {
        return $query->where('server_type_id', '=', (int)$typeId);
    }

    /**
     * @param int $type
     *
     * @return bool
     */
    public function isServerType($type)
    {
        return $type == $this->server_type_id;
    }

}
