<?php namespace DreamFactory\Enterprise\Database\Traits;

use DreamFactory\Enterprise\Database\Enums\OwnerTypes;
use DreamFactory\Enterprise\Database\Models\EnterpriseModel;
use DreamFactory\Library\Utility\IfSet;

trait AssignableEntity
{
    //******************************************************************************
    //* Members
    //******************************************************************************

    /**
     * @type array
     */
    private static $_assignableEntityOwnerMap = false;
    /**
     * @type int
     */
    private static $_assignableEntityOwnerType = null;

    //******************************************************************************
    //* Methods
    //******************************************************************************

    /**
     * Boot the trait
     */
    public static function boot()
    {
        if ( !( get_called_class() instanceof EnterpriseModel ) )
        {
            throw new \RuntimeException( 'This trait may only be used by the "EnterpriseModel" class and its descendants.' );
        }

        /** @noinspection PhpUndefinedMethodInspection */
        null !== static::$_assignableEntityOwnerType &&
        ( static::$_assignableEntityOwnerMap = OwnerTypes::getOwnerInfo( static::$_assignableEntityOwnerType ) );
    }

    /**
     * @param int $toId
     * @param int $toType
     *
     * @return bool
     */
    public function assignEntity( $toId, $toType = null )
    {
        $_map = static::_getAssignmentOwnerInfo( $toType ?: static::$_assignableEntityOwnerType );

        if ( false === ( $_assocClass = IfSet::get( $_map, 'associative-entity' ) ) )
        {
            return false;
        }

        /** @type EnterpriseModel $_assoc */
        $_assoc = new $_assocClass();

        /** @noinspection PhpUndefinedFieldInspection */

        return 1 == $_assoc->insert( [$_map['foreign-key'] => $toId, $_map['owner-class-key'] => $this->id] );
    }

    /**
     * @param int|string|EnterpriseModel $fromId
     * @param int                        $fromType
     *
     * @return bool True if removed from servitude
     */
    public function unassignEntity( $fromId, $fromType = null )
    {
        $_map = static::_getAssignmentOwnerInfo( $fromType ?: static::$_assignableEntityOwnerType );

        if ( false === ( $_assocClass = IfSet::get( $_map, 'associative-entity' ) ) )
        {
            return false;
        }

        /** @type EnterpriseModel $_assoc */
        $_assoc = new $_assocClass();
        /** @noinspection PhpUndefinedFieldInspection */
        $_count = $_assoc->where( $_map['foreign-key'], $fromId )->where( $_map['owner-class-key'], $this->id )->delete();
        \Log::debug( '[AssignableEntity] deleted ' . $_count . ' row(s) from "' . $_assoc->getTable() . '"' );

        /** @noinspection PhpUndefinedFieldInspection */

        return 0 > $_count;
    }

    /**
     * @return string
     */
    public function getAssignableEntityOwnerType()
    {
        return static::$_assignableEntityOwnerType;
    }

    /**
     * @param string $assignableEntityOwnerType
     *
     * @return AssignableEntity
     */
    public static function setAssignableEntityOwnerType( $assignableEntityOwnerType )
    {
        if ( !OwnerTypes::contains( $assignableEntityOwnerType ) )
        {
            throw new \InvalidArgumentException( 'The owner type "' . $assignableEntityOwnerType . '" is invalid.' );
        }

        static::$_assignableEntityOwnerType = $assignableEntityOwnerType;
    }

    /**
     * @param int $ownerType
     *
     * @return array
     */
    protected static function _getAssignmentOwnerInfo( $ownerType )
    {
        if ( null !== ( $_info = IfSet::get( static::$_assignableEntityOwnerMap, $ownerType ) ) )
        {
            static::$_assignableEntityOwnerMap[$ownerType] = $_info = OwnerTypes::getOwnerInfo( $ownerType, false );
        }

        return $_info;
    }
}
