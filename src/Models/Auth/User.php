<?php
namespace DreamFactory\Library\Fabric\Database\Models\Auth;

use DreamFactory\Library\Fabric\Database\Models\AuthModel;

/**
 * fabric_auth.user_t
 */
class User extends AuthModel
{
    //******************************************************************************
    //* Members
    //******************************************************************************

    /**
     * @type string The table name
     */
    protected $table = 'user_t';

    //******************************************************************************
    //* Methods
    //******************************************************************************

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function instances()
    {
        return $this->hasMany(
            'DreamFactory\\Library\\Fabric\\Database\\Models\\Deploy\\Instance',
            'user_id',
            'id'
        );
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function hashes()
    {
        return $this->belongsTo(
            'DreamFactory\\Library\\Fabric\\Database\\Models\\Deploy\\OwnerHash',
            'owner_id',
            'id'
        );
    }
}