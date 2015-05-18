<?php namespace DreamFactory\Enterprise\Database\Exceptions;

/**
 * Thrown when an instance is not activated
 */
class InstanceNotActivatedException extends InstanceException
{
    //******************************************************************************
    //* Methods
    //******************************************************************************

    /**
     * @param string     $instanceId
     * @param null       $message
     * @param int        $code
     * @param \Exception $previous
     */
    public function __construct( $instanceId, $message = null, $code = 403, \Exception $previous = null )
    {
        parent::__construct(
            $instanceId,
            $message ?: 'Instance "' . $instanceId . '" not activated.',
            $code,
            $previous
        );
    }

}