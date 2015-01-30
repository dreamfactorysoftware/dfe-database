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
 * User
 *
 * @property int    drupal_id
 * @property string api_token_text
 * @property string first_name_text
 * @property string last_name_text
 * @property string display_name_text
 * @property string email_addr_text
 * @property string password_text
 * @property string drupal_password_text
 * @property int    owner_id
 * @property int    owner_type_nbr
 * @property string company_name_text
 * @property string title_text
 * @property string city_text
 * @property string state_province_text
 * @property string country_text
 * @property string postal_code_text
 * @property string phone_text
 * @property string fax_text
 * @property bool   opt_in_ind
 * @property bool   agree_ind
 * @property string valid_email_hash_text
 * @property int    valid_email_hash_expire_time
 * @property mixed  valid_email_date
 * @property string recover_hash_text
 * @property int    recover_hash_expire_time
 * @property mixed  last_login_date
 * @property string last_login_ip_text
 * @property bool   admin_ind
 * @property string storage_id_text
 * @property bool   activate_ind
 */
class User extends DeployModel
{
    //******************************************************************************
    //* Members
    //******************************************************************************

    /**
     * @type string The table name
     */
    protected $table = 'user_t';

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