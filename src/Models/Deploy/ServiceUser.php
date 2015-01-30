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
namespace DreamFactory\Library\Fabric\Database\Models\Deploy;

use DreamFactory\Library\Fabric\Database\Models\DeployModel;

/**
 * service_user_t
 *
 * @property string first_name_text
 * @property string last_name_text
 * @property string display_name_text
 * @property string email_addr_text
 * @property string password_text
 * @property int    owner_id
 * @property int    owner_type_nbr
 * @property mixed  last_login_date
 * @property string last_login_ip_text
 */
class ServiceUser extends DeployModel
{
    //******************************************************************************
    //* Members
    //******************************************************************************

    /**
     * @type string The table name
     */
    protected $table = 'service_user_t';

    //******************************************************************************
    //* Methods
    //******************************************************************************

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function servers()
    {
        return $this->hasMany( __NAMESPACE__ . '\\Server', 'user_id' );
    }
}