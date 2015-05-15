<?php namespace DreamFactory\Enterprise\Database\Enums;

use DreamFactory\Library\Utility\Enums\FactoryEnum;

/**
 * The types of file system mounts
 */
class MountTypes extends FactoryEnum
{
    //******************************************************************************
    //* Constants
    //******************************************************************************

    /**
     * @type int locally mounted
     */
    const LOCAL = 0;
    /**
     * @type int ssh/sftp mount
     */
    const SFTP = 1;
    /**
     * @type int Amazon S3 mount
     */
    const S3 = 2;
}
