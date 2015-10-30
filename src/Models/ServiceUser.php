<?php namespace DreamFactory\Enterprise\Database\Models;

use DreamFactory\Enterprise\Database\Enums\OwnerTypes;
use DreamFactory\Enterprise\Database\Traits\CheckNickname;
use DreamFactory\Enterprise\Database\Traits\KeyMaster;
use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;

/**
 * service_user_t
 *
 * @property string first_name_text
 * @property string last_name_text
 * @property string nickname_text
 * @property string email_addr_text
 * @property string password_text
 * @property string active_ind
 * @property int    owner_id
 * @property int    owner_type_nbr
 * @property mixed  last_login_date
 * @property string last_login_ip_text
 * @property string remember_token
 */
class ServiceUser extends EnterpriseModel implements AuthenticatableContract, CanResetPasswordContract
{
    //******************************************************************************
    //* Traits
    //******************************************************************************

    use Authenticatable, CheckNickname, KeyMaster;

    //******************************************************************************
    //* Members
    //******************************************************************************

    /**
     * @type string The table name
     */
    protected $table = 'service_user_t';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['first_name_text', 'last_name_text', 'email_addr_text', 'password_text'];
    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = ['password_text', 'remember_token'];

    //******************************************************************************
    //* Methods
    //******************************************************************************

    /** @inheritdoc */
    public static function boot()
    {
        parent::boot();

        //  Ensure user is active upon creation
        static::creating(function (ServiceUser $model){
            $model->active_ind = true;
        });

        static::created(function (ServiceUser $model){
            AppKey::createKeyForEntity($model, OwnerTypes::SERVICE_USER);
        });
    }

    /** @inheritdoc */
    public function owner()
    {
        return $this->belongsTo(static::MODEL_NAMESPACE . 'ServiceUser', 'id', 'owner_id');
    }

    /** @inheritdoc */
    /** @noinspection PhpMissingParentCallCommonInspection */
    public function getMorphClass()
    {
        return $this->owner_type_nbr ?: OwnerTypes::SERVICE_USER;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     */
    public function roles()
    {
        return $this->hasManyThrough(static::MODEL_NAMESPACE . 'Role',
            static::MODEL_NAMESPACE . 'UserRole',
            'user_id',
            'role_id');
    }

    /**
     * @param int $roleId
     *
     * @return bool True if user has role $roleId
     */
    public function hasRole($roleId)
    {
        return 0 != UserRole::whereRaw('user_id = :user_id AND role_id = :role_id',
            [':user_id' => $this->id, ':role_id' => $roleId])->count();
    }

    /**
     * @param int|string|Role $roleId
     *
     * @return bool True if server removed from role
     */
    public function removeRole($roleId)
    {
        $_role = ($roleId instanceof Role) ? $roleId : $this->_getRole($roleId);

        if ($this->hasRole($_role->id)) {
            return 1 == UserRole::whereRaw('role_id = :role_id AND user_id = :user_id',
                [':role_id' => $_role->id, ':user_id' => $this->id])->delete();
        }

        return false;
    }

    /**
     * @param int|string $roleId
     *
     * @return bool
     */
    public function addRole($roleId)
    {
        //  This will fail if $roleId is bogus
        $this->removeRole($_role = $this->_getRole($roleId));

        return 1 == UserRole::insert(['role_id' => $_role->id, 'user_id' => $this->id]);
    }

    /**
     * @param int|string $roleId
     *
     * @return Role
     */
    protected function _getRole($roleId)
    {
        if (null === ($_role = Role::find($roleId))) {
            throw new \InvalidArgumentException('The role id "' . $roleId . '" is invalid.');
        }

        return $_role;
    }

    /** @inheritdoc */
    public function getAuthPassword()
    {
        return $this->password_text;
    }

    /** @inheritdoc */
    public function getEmailForPasswordReset()
    {
        return $this->email_addr_text;
    }
}
