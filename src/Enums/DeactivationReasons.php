<?php namespace DreamFactory\Enterprise\Database\Enums;

use DreamFactory\Library\Utility\Enums\FactoryEnum;

/**
 * Various reasons an instance can be auto-deactivated
 */
class DeactivationReasons extends FactoryEnum
{
    //******************************************************************************
    //* Constants
    //******************************************************************************

    /**
     * @type int The instance is currently active or in an unknown state
     */
    const CURRENTLY_ACTIVE = 0;
    /**
     * @type int The instance was never activated
     */
    const NEVER_ACTIVATED = 1;
    /**
     * @type int The instance received an abuse complaint
     */
    const ABUSE_COMPLAINT = 2;
    /**
     * @type int The instance was activated but has gone unused for a period of time
     */
    const NON_USE = 3;
    /**
     * @type int The instance had an expiration date
     */
    const EXPIRED = 4;
    /**
     * @type int The instance was not provisioned properly
     */
    const INCOMPLETE_PROVISION = 5;
}
