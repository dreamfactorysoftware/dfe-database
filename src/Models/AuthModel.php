<?php
namespace DreamFactory\Library\Fabric\Database\Models;

/**
 * @property int   id
 * @property mixed lmod_date
 * @property mixed create_date
 */
class AuthModel extends BaseModel
{
    //******************************************************************************
    //* Members
    //******************************************************************************

    /**
     * @type string Our connection
     */
    protected $connection = 'fabric-auth';

}