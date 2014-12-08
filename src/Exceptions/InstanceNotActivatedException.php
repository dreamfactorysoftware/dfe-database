<?php
/**
 * This file is part of the DreamFactory Fabric(tm) Tools Library
 *
 * Copyright 2014 DreamFactory Software, Inc. All Rights Reserved.
 *
 * Proprietary code, DO NOT DISTRIBUTE!
 *
 * @email   <support@dreamfactory.com>
 * @license proprietary
 */
namespace DreamFactory\Library\Fabric\Database\Exceptions;

use DreamFactory\Library\Console\Exceptions\ConsoleException;

/**
 * Thrown when an instance is not activated
 */
class InstanceNotActivatedException extends ConsoleException
{
    //******************************************************************************
    //* Methods
    //******************************************************************************

    /**
     * @param string    $id
     * @param string    $message
     * @param int       $code
     * @param Exception $previous
     */
    public function __construct( $id, $message = null, $code = 404, \Exception $previous = null )
    {
        parent::__construct(
            $message ?: 'The DSP has not yet been activated. Your state change could not be completed.',
            $code,
            $previous
        );
    }

}