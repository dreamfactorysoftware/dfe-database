<?php
namespace DreamFactory\Enterprise\Database\Traits;

use DreamFactory\Enterprise\Common\Contracts\InstanceAware;
use DreamFactory\Enterprise\Database\Exceptions\InstanceNotFoundException;
use DreamFactory\Enterprise\Database\Models\Instance;

/**
 * A trait for validating instances
 */
trait InstanceValidation
{
    //*************************************************************************
    //* Methods
    //*************************************************************************

    /**
     * @param string|Instance $instanceId
     *
     * @return \DreamFactory\Enterprise\Database\Models\Instance
     * @throws \DreamFactory\Enterprise\Database\Exceptions\InstanceNotFoundException
     */
    protected function _validateInstance($instanceId)
    {
        if ($instanceId instanceof Instance) {
            return $instanceId;
        }

        if ($instanceId instanceOf InstanceAware) {
            return $instanceId->getInstance();
        }

        if (!is_string($instanceId)) {
            throw new InstanceNotFoundException($instanceId);
        }

        try {
            $instanceId = Instance::sanitizeName($instanceId);

            return Instance::byNameOrId($instanceId)->firstOrFail();
        } catch (\Exception $_ex) {
            throw new InstanceNotFoundException($instanceId);
        }
    }

}