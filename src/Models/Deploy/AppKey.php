<?php
namespace DreamFactory\Library\Fabric\Database\Models\Deploy;

use DreamFactory\Library\Fabric\Database\Models\DeployModel;
use Illuminate\Database\Eloquent\Builder;

/**
 * app_key_t
 *
 * @property string client_id
 * @property string client_secret
 * @property int    owner_id
 * @property int    owner_type_nbr
 */
class AppKey extends DeployModel
{
    //******************************************************************************
    //* Members
    //******************************************************************************

    /**
     * @type string The table name
     */
    protected $table = 'app_key_t';
}