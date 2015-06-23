<?php namespace DreamFactory\Enterprise\Database\Models;

use DreamFactory\Enterprise\Database\Traits\AssociativeEntity;

/**
 * Base class for associative entity classes
 *
 * @property int $id
 */
class AssociativeEntityOwner extends EnterpriseModel
{
    //******************************************************************************
    //* Traits
    //******************************************************************************

    use AssociativeEntity;
}