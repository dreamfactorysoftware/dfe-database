<?php namespace DreamFactory\Library\Fabric\Database\Enums;

use DreamFactory\Library\Utility\Enums\FactoryEnum;

/**
 * The types of entities that can own things
 */
class OwnerTypes extends FactoryEnum
{
    //******************************************************************************
    //* Constants
    //******************************************************************************

    /**
     * @type int users
     */
    const USER = 0;
    /**
     * @type int applications
     */
    const APPLICATION = 1;
    /**
     * @type int services
     */
    const SERVICE = 2;
    /**
     * @type int instances
     */
    const INSTANCE = 3;
    /**
     * @type int servers
     */
    const SERVER = 4;
    /**
     * @type int clusters
     */
    const CLUSTER = 5;
    /**
     * @type int console
     */
    const CONSOLE = 1000;
    /**
     * @type int dashboard
     */
    const DASHBOARD = 1001;
}
