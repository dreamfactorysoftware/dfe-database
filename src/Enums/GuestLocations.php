<?php namespace DreamFactory\Library\Fabric\Database\Enums;

use DreamFactory\Library\Utility\Enums\FactoryEnum;

/**
 * Where DFE instances may reside. These values correspond to dfe-deploy:vendor_t.id
 */
class GuestLocations extends FactoryEnum
{
    //*************************************************************************
    //* Constants
    //*************************************************************************

    /**
     * @type int Amazon EC2
     */
    const AMAZON_EC2 = 1;
    /**
     * @type int DreamFactory Enterprise(tm) cluster
     */
    const DFE_CLUSTER = 2;
    /**
     * @type int Microsoft Azure
     */
    const MICROSOFT_AZURE = 3;
    /**
     * @type int Rackspace cloud
     */
    const RACKSPACE_CLOUD = 4;
    /**
     * @type int Generic OpenStack
     */
    const OPENSTACK = 5;
    /**
     * @type int A local installation
     */
    const LOCAL = 1000;

    //******************************************************************************
    //* Members
    //******************************************************************************

    /**
     * @type array
     */
    protected static $_tags = [
        self::AMAZON_EC2      => 'amazon',
        self::DFE_CLUSTER     => 'rave',
        self::MICROSOFT_AZURE => 'azure',
        self::RACKSPACE_CLOUD => 'rackspace',
        self::OPENSTACK       => 'openstack',
        self::LOCAL           => 'local',
    ];

    //******************************************************************************
    //* Methods
    //******************************************************************************

    /**
     * @param int $constant
     *
     * @return mixed
     */
    public static function resolve( $constant )
    {
        if ( is_numeric( $constant ) && isset( static::$_tags[$constant] ) )
        {
            return static::$_tags[$constant];
        }

        if ( !is_numeric( $constant ) && is_string( $constant ) )
        {
            return $constant;
        }

        throw new \InvalidArgumentException( 'The $constant "' . $constant . '" is invalid.' );
    }
}
