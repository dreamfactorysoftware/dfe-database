<?php namespace DreamFactory\Enterprise\Database\Enums;

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
    protected static $tags = [
        self::AMAZON_EC2      => 'amazon',
        self::DFE_CLUSTER     => 'dreamfactory',
        self::MICROSOFT_AZURE => 'azure',
        self::RACKSPACE_CLOUD => 'rackspace',
        self::OPENSTACK       => 'openstack',
        self::LOCAL           => 'local',
    ];

    //******************************************************************************
    //* Methods
    //******************************************************************************

    /**
     * Given a guest location id or name (i.e. "dreamfactory"), return the associated $tag (string).
     *
     * @param int  $constant      The constant value
     * @param bool $bidirectional If true, converts numeric constant to string and vice versa
     *
     * @return string
     */
    public static function resolve($constant, $bidirectional = false)
    {
        if (is_numeric($constant) && isset(static::$tags[$constant])) {
            return static::$tags[$constant];
        }

        if ($bidirectional) {
            //  String, not id
            if (is_string($constant) && !is_numeric($constant)) {
                //  If we have a matching tag, return the value
                if (in_array($constant, array_values(static::$tags))) {
                    return array_get(array_flip(static::$tags), $constant);
                }

                //  Otherwise check the constants
                if (false !== ($_value = static::contains($constant, true))) {
                    return $_value;
                }
            }
        } else if (!is_numeric($constant) && is_string($constant)) {
            return $constant;
        }

        throw new \InvalidArgumentException('The $constant "' . $constant . '" is invalid.');
    }
}
