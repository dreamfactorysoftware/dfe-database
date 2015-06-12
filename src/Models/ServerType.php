<?php namespace DreamFactory\Enterprise\Database\Models;

/**
 * @property string $type_name_text
 * @property string $schema_text
 */
class ServerType extends EnterpriseModel
{
    //******************************************************************************
    //* Members
    //******************************************************************************

    /**
     * @type string The table name
     */
    protected $table = 'server_type_t';
    /** @inheritdoc */
    protected $casts = [
        'schema_text' => 'array',
    ];

    //******************************************************************************
    //* Methods
    //******************************************************************************

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function servers()
    {
        return $this->belongsToMany( __NAMESPACE__ . '\\Server', 'server_type_id' );
    }
}