<?php namespace DreamFactory\Enterprise\Database\Traits;

use DreamFactory\Enterprise\Database\Models\EnterpriseModel;

/**
 * Checks the nickname field on models before saving
 */
trait CheckNickname
{
    //******************************************************************************
    //* Methods
    //******************************************************************************

    /**
     * Boot the trait
     */
    public static function bootCheckNickname()
    {
        /** @noinspection PhpUndefinedMethodInspection */
        static::creating(/**
         * @var EnterpriseModel $model
         */
            function ($model){
                if (isset($model->nickname_text) && empty($model->nickname_text)) {
                    $model->nickname_text = trim($model->first_name_text . ' ' . $model->last_name_text, '- ');
                }
            });

        /** @noinspection PhpUndefinedMethodInspection */
        static::updating(/**
         * @var EnterpriseModel $model
         */
            function ($model){
                if (isset($model->nickname_text) && empty($model->nickname_text)) {
                    $model->nickname_text = trim($model->first_name_text . ' ' . $model->last_name_text, '- ');
                }
            });
    }

}
