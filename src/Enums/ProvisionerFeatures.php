<?php namespace DreamFactory\Enterprise\Database\Enums;

use DreamFactory\Library\Utility\Enums\FactoryEnum;

/**
 * Provisioner features
 */
class ProvisionerFeatures extends FactoryEnum
{
    //*************************************************************************
    //* Constants
    //*************************************************************************

    /**
     * @var int
     */
    const HOSTED_ENVIRONMENT = 0x0001;
    /**
     * @var int
     */
    const DEDICATED_HARDWARE = 0x0002;
    /**
     * @var int
     */
    const BLOCK_STORAGE = 0x0004;
    /**
     * @var int
     */
    const SQL_DATABASE = 0x0008;
    /**
     * @var int
     */
    const FILE_STORAGE = 0x0010;
    /**
     * @var int
     */
    const SMTP_MAIL = 0x0020;
    /**
     * @var int
     */
    const DNS = 0x0040;
    /**
     * @var int
     */
    const START_STOP = 0x0080;
    /**
     * @var int
     */
    const TERMINATION = 0x0100;

}
