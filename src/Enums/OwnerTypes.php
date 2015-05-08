<?php namespace DreamFactory\Library\Fabric\Database\Enums;

use DreamFactory\Enterprise\Common\Traits\StaticComponentLookup;
use DreamFactory\Library\Utility\Enums\FactoryEnum;
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

    //******************************************************************************
    //* Methods
    //******************************************************************************

    /**
     * @param int        $ownerId
     * @param int|string $ownerType String types will be converted to numeric equivalent
     *
     * @return \DreamFactory\Library\Fabric\Database\Models\Deploy\Cluster|\DreamFactory\Library\Fabric\Database\Models\Deploy\Instance|\DreamFactory\Library\Fabric\Database\Models\Deploy\Server|\DreamFactory\Library\Fabric\Database\Models\Deploy\User
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

        if ( $ownerType >= 1000 )
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

}
