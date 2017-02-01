<?php
/**
 * User Model. Mostly used for authorization / authentication.
 *
 * @author
 *   Alexandre Perrin <alexandre.perrin@netoxygen.ch>
 */
require_once(APPDIR . '/models/base_model.class.php');


class User extends BaseModel {
    const ROOT_EMAIL   = 'support@netoxygen.ch';

    public static $table  = 'users';

    public function db_infos() {
        return [
            'id'              => ['protected' => true, 'type' => 'uuidv4'],
            'created_at'      => ['protected' => true, 'type' => 'datetime'],
            'updated_at'      => ['protected' => true, 'type' => 'datetime'],
            'created_by'      => ['protected' => true, 'type' => 'uuidv4'],
            'updated_by'      => ['protected' => true, 'type' => 'uuidv4'],
            'role'            => ['protected' => true], // see roles() (or 'anonymous' but not likely in db)
            'passwd'          => ['protected' => true],
            'is_active'       => ['protected' => true, 'type' => 'boolean', 'default' => true],
            'email'           => [],
            'gender'          => ['default' => '?'], // 'M' (Male), 'F' (Female) or '?'
            'fullname'        => [],
            'description'     => [],
        ];
    }

    /* the current user */
    protected static $current_user = null;

    /**
     * possibles values for the 'role' field.
     *
     * @param $role
     *   If null then an array of possibles values is returned. Otherwise this
     *   function return the value associated to the key $role (or null).
     *
     * @return
     *   An array, a string, or null depending on the $role argument.
     *
     * NOTE:
     *   'anonymous' is a valid value for an object living in memory. It is
     *   used for non-auth users. It is ommited here because it is not a valid
     *   value for a db entry.
     */
    public static function roles($role = null)
    {
        $data = [
            // 'db value' => 'desc'
            'admin'     => t('user.role.admin'),
            'user'      => t('user.role.user'),
        ];

        if (is_null($role)) {
            $result = $data;
        } else {
            $result = (array_key_exists($role, $data) ? $data[$role] : null);
        }
        return $result;
    }

    /*
     * scopes
     */

    public static function scope_email($q, $email) {
        return $q->where('email = :scope_email', array(
            ':scope_email' => $email
        ));
    }

    public static function scope_root($q) {
        return $q->first()->email(static::ROOT_EMAIL);
    }

    public static function scope_active($q)
    {
        return $q->where('is_active');
    }

    public static function scope_inactive($q)
    {
        return $q->where('NOT is_active');
    }


    /**
     * User password hash function.
     *
     * This function use Bcrypt, see http://en.wikipedia.org/wiki/Bcrypt.
     *
     * @param $password
     *   The password to hash.
     *
     * @return
     *   A printable crypt() scheme.
     *   see http://en.wikipedia.org/wiki/Crypt_(C)#Blowfish-based_scheme
     */
    public static function password_crypt($password) {
        $cost  = AppConfig::get('security.bcrypt_cost', 14); // default is 14, better safe than sorry.
        return password_hash($password, PASSWORD_BCRYPT, array('cost' => $cost));
    }

    /**
     * User password verification function.
     *
     * @param $password
     *   The cleartext password to match.
     *
     * @param $hash
     *   The crypt() scheme to be matched.
     *
     * @return
     *   True if the password match, false otherwise.
     */
    public static function password_verify($password, $hash) {
        return password_verify($password, $hash);
    }

    /**
     * Authenticate a user.
     *
     * @param $email
     *   The user's email
     *
     * @param $password
     *   The cleartext password used for authentication.
     *
     * @return
     *   NULL if the authentication failed, a User object on success.
     */
    public static function authenticate($email, $password)
    {
        /*
         * We call password_verify() in any case to avoid leaking a timing
         * attack on authentication. We use the root user because it is
         * expected that its password "cost" is the same as the other users
         * (and it's the only one the system knows about).
         */
        $user        = static::first()->active()->email($email)->select();
        $root        = static::first()->root()->select();
        $user_ok     = ($user ? true : false);
        $passwd      = ($user_ok ? $user->passwd : $root->passwd);
        $password_ok = static::password_verify($password, $passwd);

        /*
         * this is not a typo, we use a binary AND, because logical AND should
         * not be time const.
         */
        $success = ($user_ok & $password_ok);

        // log the attempt
        $ip_infos = "IP={$_SERVER['REMOTE_ADDR']}";
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR']))
            $ip_infos .= ", X-Forwarded-For={$_SERVER['HTTP_X_FORWARDED_FOR']}";
        No2_Logger::info(
            ($success ? 'Successfull' : 'Failed') . " login for $email ($ip_infos)"
        );

        return ($success ? $user : null);
    }

    /**
     * get the current user if the client is logged in.
     *
     * This function always return a valid User object. Either one matching a
     * database row when the client is logged in, or a "fresh" object with the role
     * `anonymous'.
     *
     * @return
     *   a User object.
     */
    public static function current() {
        if (is_null(static::$current_user)) {
            if (session_active() && array_key_exists('uid', $_SESSION))
                static::$current_user = static::find($_SESSION['uid']);
            else
                static::$current_user = new static(array('role' => 'anonymous'));
        }
        return static::$current_user;
    }

    /**
     * save the current user id in the session.
     *
     * @param $user
     *   a User object. The client is considered logged in as
     *   <code>$user</code> when this function return. if NULL, the current
     *   user is "logged out", meaning the session user id is unset.
     */
    public static function current_is($user) {
        if (is_null($user)) {
            static::$current_user = NULL;
            if (session_active())
                unset($_SESSION['uid']);
        } else if ($user instanceof static) {
            static::$current_user = $user;
            if (session_active())
                $_SESSION['uid'] = $user->id;
        } else {
            No2_Logger::err(get_called_class() . 'current_is: ' .
                'Wrong user type: ' . get_class($user));
        }
    }

    /**
     * classic validate() function.
     */
    public function validate()
    {
        if (empty($this->fullname))
            $this->error_add('fullname', t('validations.empty'));

        if (!in_array($this->gender, array('?', 'M', 'F')))
            $this->error_add('gender', t('validations.invalid'));

        if (is_null(static::roles($this->role)))
            $this->error_add('role', t('validations.invalid'));

        if (!preg_match('/^[^@]+@[^@]+\.[^@]+$/', $this->email))
            $this->error_add('email', t('validations.invalid'));
        $same_email_query = static::first()->where('email = :email', array(':email' => $this->email));
        if (!$this->is_new_record())
            $same_email_query = $same_email_query->where('id <> :id', array(':id' => $this->id));
        if ($same_email_query->count() > 0)
            $this->error_add('email', t('validations.already_taken'));
    }

    /**
     * change the password of this user.
     *
     * Use this method to change the user's password, given the cleartext
     * password. The cleartext password can be "forgotten" by the system after
     * this call (although save() should be called after, to ensure a commit to
     * the db and allowing the user to authenticate with the newly set
     * password).
     *
     * @param $password
     *   The new password.
     */
    public function update_password($password) {
        $this->passwd = static::password_crypt($password);
    }

    /**
     * check if the user is an admin.
     *
     * @return
     *   true if the user is admin, false otherwise.
     */
    public function is_admin() {
        return ($this->role === 'admin');
    }

    /**
     * check if the user is anonymous (not authenticated).
     *
     * @return
     *   false if the user is authenticated, true otherwise.
     */
    public function is_anonymous() {
        return ($this->role === 'anonymous');
    }

    /**
     * The ability of this user.
     *
     * @see can(), Ability
     */
    protected $ability = null;

    /**
     * authorization method. check if this user is able to perform an action.
     *
     * This method is a simple shortcut to the Ability class.
     *
     * FIXME: use a splat instead of $arguments
     *
     * @param $action
     *   The action to check for authorization
     *
     * @param $arguments
     *   Optional arguments. Depending on the action.
     *
     * @return
     *   true if the user is authorized to perform this action, false otherwise.
     */
    public function can($action, $arguments = null)
    {
        if (is_null($this->ability)) {
            require_once(APPDIR . '/models/ability.class.php');
            $this->ability = new Ability($this);
        }

        return $this->ability->authorize($action, $arguments);
    }

    /**
     * check if the user is the root user.
     *
     * @return
     *   true if the user is the root user, false otherwise.
     */
    public function is_root()
    {
        $self = static::find($this->id);
        return ($self && strcmp($self->email, static::ROOT_EMAIL) === 0);
    }

    /**
     * check if the user has a special semantic for the system.
     *
     * This method is mostly designed to be used by the Ability framework. A
     * special user cannot change its role and cannot be destroyed.
     *
     * At the moment only root is considered "special" by the sytem.
     *
     * @return
     *   true if the user is special, false otherwise.
     */
    public function is_special()
    {
        return $this->is_root();
    }

    /**
     * overrided to avoid destroy'ing a special user.
     *
     * @see is_special.
     */
    public function destroy() {
        if ($this->is_special())
            throw new Exception("Can't destroy a special user.");
        return parent::destroy();
    }

    /**
     * public getter for a description of this user's abilities.
     */
    public function abilities()
    {
        $this->can('login'); // just to ensure to init $this->ability
        return $this->ability->desc();
    }
}
