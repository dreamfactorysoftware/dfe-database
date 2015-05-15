<?php
namespace DreamFactory\Enterprise\Database\Models;

use DreamFactory\Enterprise\Database\Enums\OwnerTypes;

class BaseEnterpriseModel extends BaseModel
{
    //******************************************************************************
    //* Members
    //******************************************************************************

    /**
     * @type string Our connection
     */
    protected $connection = 'dfe-local';
    /**
     * @type int The type of entity which can own this entity
     */
    protected static $_assignmentOwnerType = false;

    /**
     * @return int
     */
    public static function getAssignmentOwnerType()
    {
        return static::$_assignmentOwnerType;
    }

    /**
     * @param int $assignmentOwnerType
     */
    public static function setAssignmentOwnerType( $assignmentOwnerType )
    {
        if ( !OwnerTypes::contains( $assignmentOwnerType ) )
        {
            throw new \InvalidArgumentException( 'The owner type "' . $assignmentOwnerType . '" is invalid.' );
        }

        static::$_assignmentOwnerType = $assignmentOwnerType;
    }

}