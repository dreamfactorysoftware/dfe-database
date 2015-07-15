<?php namespace DreamFactory\Enterprise\Database\Contracts;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Query\Builder;

/**
 * Something that has an owner
 */
interface OwnedEntity
{
    //******************************************************************************
    //* Methods
    //******************************************************************************

    /**
     * Definition of the "owner" relationship
     *
     * @return BelongsTo|MorphTo|MorphToMany|BelongsToMany|mixed
     */
    public function owner();

    /**
     * @param Builder    $query
     * @param string|int $ownerId
     * @param string|int $ownerType
     *
     * @return mixed
     */
    public function scopeByOwner($query, $ownerId, $ownerType = null);
}