<?php namespace DreamFactory\Enterprise\Database\Traits;

/**
 * What KeyMaster is seeking
 *
 * @property int $owner_id
 * @property int $owner_type_nbr
 */
trait Gatekeeper
{
    //******************************************************************************
    //* Methods
    //******************************************************************************

    /**
     * Define a polymorphic, inverse one-to-one or many relationship.
     *
     * @param  string $name
     * @param  string $type
     * @param  string $id
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function gatekeeper($name = 'gatekeeper', $type = 'owner_type_nbr', $id = 'owner_id')
    {
        /** @noinspection PhpUndefinedMethodInspection */
        return $this->morphTo($name, $type, $id);
    }
}
