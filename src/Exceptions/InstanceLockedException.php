<?php
namespace DreamFactory\Library\Fabric\Database\Exceptions;

use DreamFactory\Library\Console\Exceptions\ConsoleException;

/**
 * Thrown when an instance is locked
 */
class InstanceLockedException extends ConsoleException
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
            $message ?: 'dsp-name "<error>' . $id . '</error>" is currently locked. Please <comment>unlock</comment> and try again.',
            $code,
            $previous
        );
    }

}