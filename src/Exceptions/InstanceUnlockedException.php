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

/**
 * Thrown when an instance is unlocked
 */
class InstanceUnlockedException extends ConsoleException
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
            $message ?: 'dsp-name "<error>' . $id . '</error>" is currently unlocked. Please <comment>lock</comment> and try again.',
            $code,
            $previous
        );
    }

}