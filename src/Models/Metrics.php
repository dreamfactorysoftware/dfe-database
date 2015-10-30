<?php namespace DreamFactory\Enterprise\Database\Models;

/**
 * metrics_t
 *
 * @property array   metrics_data_text
 * @property boolean sent_ind
 */
class Metrics extends EnterpriseModel
{
    //******************************************************************************
    //* Members
    //******************************************************************************

    /** @inheritdoc */
    protected $table = 'metrics_t';
    /** @inheritdoc */
    protected $casts = ['metrics_data_text' => 'array',];
}
