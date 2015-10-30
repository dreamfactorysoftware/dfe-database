<?php namespace DreamFactory\Enterprise\Database\Exceptions;

use Exception;

/**
 * Base instance exception
 */
class InstanceException extends \RuntimeException
{
    //******************************************************************************
    //* Members
    //******************************************************************************

    /**
     * @type string The instance in question
     */
    protected $_instanceId;

    //******************************************************************************
    //* Methods
    //******************************************************************************

    /**
     * @param string    $instanceId
     * @param string    $message
     * @param int       $code
     * @param Exception $previous
     */
    public function __construct($instanceId, $message = null, $code = 0, Exception $previous = null)
    {
        $this->_instanceId = $instanceId;

        parent::__construct($message, $code, $previous);
    }

    /**
     * @return string
     */
    public function getInstanceId()
    {
        return $this->_instanceId;
    }

}
