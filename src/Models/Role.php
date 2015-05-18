<?php
namespace DreamFactory\Enterprise\Database\Models;

/**
 * role_t
 *
 * @property string role_name_text
 * @property string description_text
 * @property bool   active_ind
 * @property string home_view_text
 */
class Role extends EnterpriseModel
{
    //******************************************************************************
    //* Members
    //******************************************************************************

    /**
     * @type string The table name
     */
    protected $table = 'role_t';
}