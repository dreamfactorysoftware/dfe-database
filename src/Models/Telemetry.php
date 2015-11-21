<?php namespace DreamFactory\Enterprise\Database\Models;

use Carbon\Carbon;

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

    //******************************************************************************
    //* Methods
    //******************************************************************************

    /**
     * @param string $providerId The provider id
     * @param array  $data       The data gathered
     * @param string $date       The date of the gather
     *
     * @return static
     */
    public static function storeTelemetry($providerId, array $data, $date = null)
    {
        return static::create([
            'provider_id_text' => $providerId,
            'data_text'        => $data,
            'gather_date'      => $date ?: Carbon::now(),
        ]);
    }
}
