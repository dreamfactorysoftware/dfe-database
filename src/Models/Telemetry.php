<?php namespace DreamFactory\Enterprise\Database\Models;

/**
 * telemetry_t
 *
 * @property string provider_id_text
 * @property string gather_date
 * @property array  data_text
 */
class Telemetry extends EnterpriseModel
{
    //******************************************************************************
    //* Members
    //******************************************************************************

    /** @inheritdoc */
    protected $table = 'telemetry_t';
    /** @inheritdoc */
    protected $casts = [
        'data_text'   => 'array',
        'gather_date' => 'datetime',
        'create_date' => 'datetime',
        'lmod_date'   => 'datetime',
    ];
}
