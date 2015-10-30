<?php namespace DreamFactory\Enterprise\Database\Contracts;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

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
}
