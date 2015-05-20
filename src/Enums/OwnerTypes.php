<?php namespace DreamFactory\Enterprise\Database\Enums;

use DreamFactory\Enterprise\Common\Traits\StaticComponentLookup;
use DreamFactory\Enterprise\Database\Models\BaseEnterpriseModel;
use DreamFactory\Enterprise\Database\Models\Cluster;
use DreamFactory\Enterprise\Database\Models\Instance;
use DreamFactory\Enterprise\Database\Models\Server;
use DreamFactory\Enterprise\Database\Models\User;
use DreamFactory\Library\Utility\Enums\FactoryEnum;
use DreamFactory\Library\Utility\IfSet;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * The types of entities that can own things
 *
 * The numbering scheme is significant. Anything constant with a value under 1000 has an associated table. For the most part, the table is named the
 * same as the constant with "_t" appended. Constants with values of 1000 and up represent logical entities, or entities of which the console is not
 * aware.
 */
class OwnerTypes extends FactoryEnum
{
    //******************************************************************************
    //* Traits
    //******************************************************************************

    use StaticComponentLookup;

    //******************************************************************************
    //* Constants
    //******************************************************************************

    /**
     * @type int users
     */
    const USER = 0;
    /**
     * @type int instances
     */
    const INSTANCE = 1;
    /**
     * @type int servers
     */
    const SERVER = 2;
    /**
     * @type int servers
     */
    const MOUNT = 3;
    /**
     * @type int clusters
     */
    const CLUSTER = 4;
    /**
     * @type int users
     */
    const SERVICE_USER = 5;
    /**
     * @type int console
     */
    const CONSOLE = 1000;
    /**
     * @type int dashboard
     */
    const DASHBOARD = 1001;
    /**
     * @type int applications
     */
    const APPLICATION = 1002;
    /**
     * @type int services
     */
    const SERVICE = 1003;
    /**
     * @type int services
     */
    const TESTING = 9999;

    //******************************************************************************
    //* Private Constants
    //******************************************************************************

    /**
     * @type string My default namespace
     */
    const _DEFAULT_NAMESPACE_ = 'DreamFactory\\Enterprise\\Database\\Models';

    //******************************************************************************
    //* Methods
    //******************************************************************************

    /**
     * @param int        $ownerId
     * @param int|string $ownerType String types will be converted to numeric equivalent
     *
     * @return BaseEnterpriseModel|Cluster|User|Instance|Server
     */
    public static function getOwner( $ownerId, &$ownerType )
    {
        $_message = 'The owner id "' . $ownerType . ':' . $ownerId . '" could not be found.';

        if ( !is_numeric( $ownerType ) )
        {
            try
            {
                $ownerType = OwnerTypes::defines( strtoupper( $ownerType ), true );
            }
            catch ( \InvalidArgumentException $_ex )
            {
                //  Force a FAIL
                $ownerId = $ownerType = -1;
            }
        }

        //  Owner types >= 1000 are reserved for logical, non-physical, entities such as the console or dashboard
        if ( $ownerType >= static::CONSOLE )
        {
            $_owner = new \stdClass();
            $_owner->id = $ownerId;
            $_owner->type = $ownerType;

            return $_owner;
        }

        //  And the rest have models
        //  @todo make more dynamic so new constants don't require new lookup switch cases
        switch ( $ownerType )
        {
            case static::USER:
                return static::_lookupUser( $ownerId );

            case static::SERVICE_USER:
                return static::_lookupServiceUser( $ownerId );

            case static::MOUNT:
                return static::_lookupMount( $ownerId );

            case static::INSTANCE:
                return static::_lookupInstance( $ownerId );

            case static::SERVER:
                return static::_lookupServer( $ownerId );

            case static::CLUSTER:
                return static::_lookupCluster( $ownerId );
        }

        throw new ModelNotFoundException( $_message );
    }

    /**
     * @param int  $type      The owner type
     * @param bool $returnAll If true, the entire owner array is returned
     *
     * @return array|bool The array of info for all owners, a single owner, or FALSE if no ownership info
     */
    public static function getOwnerInfo( $type, $returnAll = true )
    {
        static $_result = [];

        if ( !isset( $_result[$type] ) || empty( $_result[$type] ) )
        {
            $_result[$type] = [];

            switch ( $type )
            {
                case static::USER:
                    $_result[$type][static::USER] = static::_buildOwnerMapping( 'User', 'owner_id' );
                    break;

                case static::SERVICE_USER:
                    $_result[$type][static::SERVICE_USER] = static::_buildOwnerMapping( 'ServiceUser', 'owner_id' );
                    break;

                case static::MOUNT:
                    $_result[$type][static::SERVER] = static::_buildOwnerMapping( 'Server', 'mount_id' );
                    break;

                case static::INSTANCE:
                    $_result[$type][static::SERVER] = static::_buildOwnerMapping( 'Server', 'server_id', 'InstanceServer', 'instance_id' );
                    $_result[$type][static::USER] = static::_buildOwnerMapping( 'User', 'user_id' );
                    break;

                case static::SERVER:
                    $_result[$type][static::CLUSTER] = static::_buildOwnerMapping( 'Cluster', 'server_id', 'ClusterServer', 'cluster_id' );
                    break;

                case static::CLUSTER:
                    $_result[$type][static::USER] = static::_buildOwnerMapping( 'User', 'user_id' );
                    break;
            }
        }

        return $returnAll ? $_result : IfSet::get( $_result, $type, false );
    }

    /**
     * Retrieve mapping info for an owner type.
     *
     * ** Classes that are not fully qualified are prefixed with this library's namespace by default. **
     *
     * @param string $class      The name of the owner's class
     * @param string $classKey   The owner id column
     * @param bool   $assoc      The associative entity model class
     * @param bool   $foreignKey The foreign key within the associative entity for the mapping
     *
     * @return array
     */
    protected static function _buildOwnerMapping( $class, $classKey, $assoc = false, $foreignKey = false )
    {
        $_class =
            ( false === strpos( $class, '\\' ) && class_exists( static::_DEFAULT_NAMESPACE_ . '\\' . $class, false ) )
                ? static::_DEFAULT_NAMESPACE_ . '\\' . $class
                : $class;

        $_assoc =
            ( false === strpos( $assoc, '\\' ) && class_exists( static::_DEFAULT_NAMESPACE_ . '\\' . $assoc, false ) )
                ? static::_DEFAULT_NAMESPACE_ . '\\' . $assoc
                : $assoc;

        return [
            'associative-entity' => $_assoc,
            'foreign-key'        => $foreignKey,
            'owner-class'        => $_class,
            'owner-class-key'    => $classKey,
        ];
    }
}
