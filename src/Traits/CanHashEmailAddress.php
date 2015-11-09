<?php namespace DreamFactory\Enterprise\Database\Traits;

use DreamFactory\Enterprise\Common\Enums\EnterpriseDefaults;

/**
 * Checks the nickname field on models before saving
 */
trait CanHashEmailAddress
{
    //******************************************************************************
    //* Methods
    //******************************************************************************

    /**
     * Returns the email address hashed with sha1 by default
     *
     * @param string $algorithm
     *
     * @return string
     */
    public function getHashedEmail($algorithm = EnterpriseDefaults::DEFAULT_SIGNATURE_METHOD)
    {
        /** @noinspection PhpUndefinedMethodInspection */
        return hash($algorithm, $this->getEmailForPasswordReset());
    }
}
