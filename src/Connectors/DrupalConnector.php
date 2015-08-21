<?php namespace DreamFactory\Enterprise\Database\Connectors;

use DreamFactory\Enterprise\Common\Services\BaseService;
use DreamFactory\Enterprise\Database\Models\User;
use DreamFactory\Library\Utility\Enums\DateTimeIntervals;

class DrupalConnector extends BaseService
{
    //*************************************************************************
    //* Constants
    //*************************************************************************

    /** @var string */
    const BASE64_ALLOWED_CHARACTERS = './0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
    /** @var int */
    const MIN_HASH_COUNT = 7;
    /** @var int */
    const MAX_HASH_COUNT = 30;
    /** @var int */
    const HASH_LENGTH = 55;

    //*************************************************************************
    //* Members
    //*************************************************************************

    /**
     * @type User
     */
    protected $user = null;
    /**
     * @var bool
     */
    private $hashingEnabled = true;

    //*************************************************************************
    //* Methods
    //*************************************************************************

    /**
     * @param User $user
     * @param bool $hashPasswords
     */
    public function __construct(User $user, $hashPasswords = true)
    {
        parent::__construct();

        $this->user = $user;
        $this->hashingEnabled = $hashPasswords;
    }

    /**
     * Prepare the user record for saving...
     *
     * @param string $newPassword
     */
    public function scrub($newPassword = null)
    {
        if (null !== $newPassword) {
            $this->user->password_text =
                $this->hashingEnabled ? $this->generatePasswordHash($newPassword) : $newPassword;
        }

        if (empty($this->user->nickname_text)) {
            $this->user->nickname_text = trim($this->user->first_name_text . ' ' . $this->user->last_name_text);
        }
    }

    /**
     * @param string $password
     *
     * @return string
     */
    public function generatePasswordHash($password)
    {
        return hash('sha512', $password);
    }

    /**
     * @return string
     */
    public function generateConfirmationHash()
    {
        return $this->generateHash('valid_email', '*');
    }

    /**
     * @return string
     */
    public function generateRecoverHash()
    {
        return $this->generateHash('recover', '|');
    }

    /**
     * @param string $type      Either "valid_email" or "recover"
     * @param string $delimiter Internal implosion delimiter
     *
     * @return string
     */
    protected function generateHash($type, $delimiter = '*')
    {
        return [
            $type . '_hash_text'   => sha1(implode($delimiter, $this->user->toArray()) . microtime(true)),
            $type . '_expire_time' => time() + DateTimeIntervals::SECONDS_PER_DAY,
        ];
    }

    /**
     * Check whether a plain text password matches a stored hashed password.
     *
     * @param string $password A plain-text password
     *
     * @return bool
     */
    public function authenticateWithDrupal($password = null)
    {
        if (substr($this->user->drupal_password_text, 0, 2) == 'U$') {
            // This may be an updated password from user_update_7000(). Such hashes
            // have 'U' added as the first character and need an extra md5().
            $_storedHash = substr($this->user->drupal_password_text, 1);
            $password = md5($password);
        } else {
            $_storedHash = $this->user->drupal_password_text;
        }

        switch (substr($_storedHash, 0, 3)) {
            case '$S$':
                // A normal Drupal 7 password using sha512.
                $_hash = $this->drupalHash('sha512', $password, $_storedHash);
                break;

            case '$H$':
                // phpBB3 uses "$H$" for the same thing as "$P$".
            case '$P$':
                // A phpass password generated using md5.  This is an
                // imported password or from an earlier Drupal version.
                $_hash = $this->drupalHash('md5', $password, $_storedHash);
                break;
            default:
                return false;
        }

        return ($_hash && $_storedHash == $_hash);
    }

    /**
     * Hash a password using a secure stretched hash.
     *
     * By using a salt and repeated hashing the password is "stretched". Its
     * security is increased because it becomes much more computationally costly
     * for an attacker to try to break the hash by brute-force computation of the
     * hashes of a large number of plain-text words or strings to find a match.
     *
     * @param string $hashType The string name of a hashing algorithm usable by hash(), like 'sha256'.
     * @param string $password The plain-text password to hash.
     * @param string $setting  An existing hash or the output of _password_generate_salt().
     *                         Must be at least 12 characters (the settings and salt).
     *
     * @return bool|string A string containing the hashed password (and salt) or FALSE on failure.
     */
    protected function drupalHash($hashType, $password, $setting)
    {
        // The first 12 characters of an existing hash are its setting string.
        $setting = substr($setting, 0, 12);

        if ($setting[0] != '$' || $setting[2] != '$') {
            return false;
        }

        $_count = strpos(static::BASE64_ALLOWED_CHARACTERS, $setting[3]);

        // Hashes may be imported from elsewhere, so we allow != DRUPAL_HASH_COUNT
        if ($_count < static::MIN_HASH_COUNT || $_count > static::MAX_HASH_COUNT) {
            return false;
        }

        $_salt = substr($setting, 4, 8);

        // Hashes must have an 8 character salt.
        if (strlen($_salt) != 8) {
            return false;
        }

        // Convert the base 2 logarithm into an integer.
        $_count = 1 << $_count;

        // We rely on the hash() function being available in PHP 5.2+.
        $_hash = hash($hashType, $_salt . $password, true);

        do {
            $_hash = hash($hashType, $_hash . $password, true);
        } while (--$_count);

        $_len = strlen($_hash);

        $_output = $setting . $this->drupalPasswordBase64Encode($_hash, $_len);

        // _password_base64_encode() of a 16 byte MD5 will always be 22 characters.
        // _password_base64_encode() of a 64 byte sha512 will always be 86 characters.
        $_expected = 12 + ceil((8 * $_len) / 6);

        return (strlen($_output) == $_expected) ? substr($_output, 0, static::HASH_LENGTH) : false;
    }

    /**
     * Encode bytes into printable base 64 using the *nix standard from crypt().
     *
     * @param $input
     *   The string containing bytes to encode.
     * @param $count
     *   The number of characters (bytes) to encode.
     *
     * @return string Encoded string
     */
    protected function drupalPasswordBase64Encode($input, $count)
    {
        $_output = '';
        $_index = 0;
        $_charSet = static::BASE64_ALLOWED_CHARACTERS;

        do {
            $_value = ord($input[$_index++]);
            $_output .= $_charSet[$_value & 0x3f];

            if ($_index < $count) {
                $_value |= ord($input[$_index]) << 8;
            }

            $_output .= $_charSet[($_value >> 6) & 0x3f];

            if ($_index++ >= $count) {
                break;
            }

            if ($_index < $count) {
                $_value |= ord($input[$_index]) << 16;
            }

            $_output .= $_charSet[($_value >> 12) & 0x3f];

            if ($_index++ >= $count) {
                break;
            }

            $_output .= $_charSet[($_value >> 18) & 0x3f];
        } while ($_index < $count);

        return $_output;
    }

    /**
     * @return \stdClass
     */
    public function asDrupalObject()
    {
        $_object = new \stdClass();
        $_object->uid = $this->user->drupal_id;
        $_object->mail = $this->user->email_addr_text;
        $_object->pass = $this->user->drupal_password_text;
        $_object->name = $this->user->nickname_text;
        $_object->created = $this->user->create_date;
        $_object->field_first_name = $this->user->first_name_text;
        $_object->field_last_name = $this->user->last_name_text;
        $_object->field_company_name = $this->user->company_name_text;
        $_object->field_city = $this->user->city_text;
        $_object->field_state_province = $this->user->state_province_text;
        $_object->field_zip_postal_code = $this->user->postal_code_text;
        $_object->field_country = $this->user->country_text;
        $_object->field_phone_number = $this->user->phone_text;
        $_object->field_title = $this->user->title_text;
        $_object->token = $this->user->api_token_text;

        return $_object;
    }
}