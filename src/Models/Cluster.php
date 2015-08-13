<?php namespace DreamFactory\Enterprise\Database\Models;

use DreamFactory\Enterprise\Database\Contracts\OwnedEntity;
use DreamFactory\Enterprise\Database\Enums\OwnerTypes;
use DreamFactory\Enterprise\Database\Traits\AuthorizedEntity;
use DreamFactory\Enterprise\Database\Traits\KeyMaster;
use Illuminate\Database\Query\Builder;

/**
 * cluster_t
 *
 * @property int    owner_id
 * @property int    owner_type_nbr
 * @property string cluster_id_text
 * @property int    max_instances_nbr
 * @property string subdomain_text
 *
 * @method static \Illuminate\Database\Eloquent\Builder byNameOrId(string $clusterNameOrId)
 */
class Cluster extends EnterpriseModel implements OwnedEntity
{
    //******************************************************************************
    //* Traits
    //******************************************************************************

    use AuthorizedEntity, KeyMaster;

    //******************************************************************************
    //* Members
    //******************************************************************************

    /**
     * @type string The table name
     */
    protected $table = 'cluster_t';

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
        return $this->owner_type_nbr ?: OwnerTypes::SERVICE_USER;
    }

    /** @inheritdoc */
    public function owner()
    {
        return $this->user();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function user()
    {
        return $this->belongsTo(static::MODEL_NAMESPACE . 'ServiceUser', 'id', 'owner_id');
    }

    /**
     * Returns a list of servers assigned to me
     *
     * @return \DreamFactory\Enterprise\Database\Models\ClusterServer[]|\Illuminate\Database\Eloquent\Collection
     */
    public function assignedServers()
    {
        return ClusterServer::with('server')->where('cluster_id', $this->id)->get();
    }

    /**
     * @param Builder    $query
     * @param string|int $clusterNameOrId
     *
     * @return Builder
     */
    public function scopeByNameOrId($query, $clusterNameOrId)
    {
        return $query->whereRaw('cluster_id_text = :cluster_id_text OR id = :id',
            [':cluster_id_text' => $clusterNameOrId, ':id' => $clusterNameOrId]);
    }

    /**
     * @param Builder  $query
     * @param int      $ownerId
     * @param int|null $ownerType
     *
     * @return Builder
     */
    public function scopeByOwner($query, $ownerId, $ownerType = null)
    {
        return $query->where('user_id', $ownerId)->where('owner_type_nbr', $ownerType ?: OwnerTypes::USER);
    }
}