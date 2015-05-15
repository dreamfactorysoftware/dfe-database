<?php
namespace DreamFactory\Enterprise\Database\Models;

use DreamFactory\Enterprise\Database\ModelsModel;

/**
 * role_t
 *
 * @property string role_name_text
 * @property string description_text
 * @property bool   active_ind
 * @property string home_view_text
 */
class Role extends DeployModel
{
    //******************************************************************************
    //* Members
    //******************************************************************************

    /**
     * @type string The table name
     */
    protected $table = 'role_t';
}