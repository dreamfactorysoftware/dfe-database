<?php
namespace DreamFactory\Enterprise\Database\Models;

use DreamFactory\Enterprise\Database\Enums\OwnerTypes;
use DreamFactory\Enterprise\Database\Traits\AuthorizedEntity;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Query\Builder;

/**
 * cluster_t
 *
 * @property int    user_id
 * @property string cluster_id_text
 * @property string subdomain_text
 *
 * @method static Builder byNameOrId(string $clusterNameOrId)
 */
class Cluster extends SelfAssociativeEntity
{
    //******************************************************************************
    //* Traits
    //******************************************************************************

    use AuthorizedEntity;

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

    /** @inheritdoc */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->setOwnerType(OwnerTypes::SERVICE_USER);
    }

    /**
     * Our instances relationship
     *
     * @return Collection
     */
    public function servers()
    {
        return Server::whereRaw(
            'id IN ( SELECT csa.cluster_id FROM cluster_server_asgn_t csa WHERE csa.cluster_id  = :cluster_id )',
            ['cluster_id' => $this->id]
        )->get();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function user()
    {
        return $this->belongsTo(__NAMESPACE__ . '\\ServiceUser', 'id', 'owner_id');
    }

    /**
     * @param Builder    $query
     * @param string|int $clusterNameOrId
     *
     * @return Builder
     */
    public function scopeByNameOrId($query, $clusterNameOrId)
    {
        return $query->whereRaw(
            'cluster_id_text = :cluster_id_text OR id = :id',
            [':cluster_id_text' => $clusterNameOrId, ':id' => $clusterNameOrId]
        );
    }

}