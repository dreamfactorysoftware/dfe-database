<?php namespace DreamFactory\Library\Fabric\Database\Enums;

use DreamFactory\Library\Utility\Enums\FactoryEnum;

/**
 * Provisioning constants
 */
class ProvisionerFeatures extends FactoryEnum
{
    //*************************************************************************
    //* Constants
    //*************************************************************************

    /**
     * @var string
     */
    const VIRTUAL_MACHINES = 'vm';
    /**
     * @var string
     */
    const BLOB_STORAGE = 'blob';
    /**
     * @var string
     */
    const SQL_STORAGE = 'sql';
    /**
     * @var string
     */
    const BLOCK_STORAGE = 'block';
    /**
     * @var string
     */
    const SMTP = 'smtp';
    /**
     * @var string
     */
    const DNS = 'dns';
}
