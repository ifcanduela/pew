<?php 

namespace pew\libs;

/**
 * User login data and procedures.
 * 
 * @package pew
 * @author ifcanduela <ifcanduela@gmail.com>
 */
class Auth
{
    /**
     * The database table in which user info is stored.
     * 
     * @access private    
     */
    private $table = 'users';
    
    /**
     * The main field names in the $table.
     * 
     * @access public
     */
    public $fields = array(
        'uuid'  => 'id',            # primary key in the users $table
        'username' => 'username',   # display name in the users $table
        'password' => 'password',   # password field in the users $table
    );
    
    /**
     * Specifies if current session is authenticated; should tie into the
     * active $Session.
     * 
     * @access public
     */
    public $auth = false;
    
    /**
     * Stores current user_id; only available if $auth is true.
     * 
     * @access public
     */
    private $uuid = null;
    
    /**
     * Session data.
     *
     * @var Session
     * @access public
     */
    private $session = null;
    
    /**
     * Database handle.
     *
     * @var \pew\libs\Database
     * @access public
     */
    private $db = null;
    
    /**
     * Builds an instance of the Auth class
     * 
     * @param \pew\libs\Database $db Database provides
     * @param Session $session Session access provider
     */
    public function __construct(Database $db, Session $session)
    {
        $this->session = $session;
        $this->db = $db;

        $this->auth = $this->session->read('auth', false);
        
        if ($this->session->exists('uuid')) {
            $this->uuid = $this->session->read('uuid');
        }
    }
    
    /**
     * Checks if the session is authenticated.
     *
     * @return bool wheter the session has been previously authenticated
     * @access public
     */
    public function gate()
    {
        return ($this->session->read('auth') == true && $this->session->read('uuid'));
    }
    
    /**
     * Checks if the information provided can be used to start a session.
     *
     * Use the Auth::authenticate() method in the login() action of the users
     * controller (for example), to check whether they can start a session or
     * not.
     *
     * Auth::authenticate() can receive the parameters['form'] property from the
     * controller, thus having access to the fields the user filled in the
     * login form.
     * 
     * @param array $userdata the information the users enter to login
     * @return bool true on login success, false otherwise
     * @access public
     */
    public function authenticate($userdata)
    {
        if (!is_object($this->db)) {
            $this->db = \pew\Pew::instance()->database();
        }

        # find information about the user in the database
        $user = $this->db->where(array($this->fields['username'] => $userdata[$this->fields['username']]))->single($this->table);
        $pass = $this->password($userdata, $user);

        # register the number of login attempts
        $login_attempts = $this->session->read('login_attempts', 0);

        if (is_array($user) && ($user['password'] === $pass)) {
            # if the credentials are correct, set the auth and user_id properties
            $this->auth = true;
            $this->uuid = $user[$this->fields['uuid']];
            $this->session->write('login_attempts', 0);
        } else {
            # if not, return false
            $this->auth = $this->uuid = false;

            # check if the retries should be delayed
            if (defined('LOGIN_DELAY')) {
                if (LOGIN_DELAY > 0) {
                    sleep(LOGIN_DELAY);
                }
            }

            $this->session->write('login_attempts', $login_attempts + 1);
        }
        
        $this->session->write('uuid', $this->uuid);
        $this->session->write('auth', (bool) $this->auth);
        
        return $this->auth;
    }
    
    /**
     * Revoke the authentication status, effectively logging the user out.
     *
     * Use this method in the logout() action of the controller that manages
     * user sessions.
     *
     * @return void
     * @access public
     */
    public function revoke()
    {
        $this->auth = false;
        $this->uuid = false;
        
        $this->session->delete('auth');
        $this->session->delete('uuid');
    }
    
    /**
     * Retrieves the authenticated user info.
     * 
     * @return object the logged-in user information, as an object, or false
     * @access public
     */
    public function user()
    {
        if (!is_object($this->db)) {
            $this->db = \pew\Pew::instance()->database();
        }
        
        if ($this->auth && $this->uuid) {
            # if the session has been authenticated, return user information
            $user = $this->db->where(array($this->fields['uuid'] => $this->uuid))->single($this->table);
            return $user;
        } else {
            # if not, return false
            return false;
        }
    }
    
    /**
     * Hashes a password using a default algorithm, or a custom_hash function
     * defined elsewhere by the user.
     * 
     * @param array $user_info The user-provided fields from the form
     * @param array $dbdata The existing user information from the database
     * @access public
     */
    public function password($userdata, $dbdata = null)
    {
        if (function_exists('custom_hash')) {
            # if the custom_hash function has been defined, use it
            return custom_hash($userdata, $dbdata);
        } else {
            # if not, use PHP's crypt function

            $salt = null;
            # Check if the user data from the db is available

            if (isset($dbdata)) {
               $salt = $dbdata[$this->fields['password']];
            }

            return crypt($userdata[$this->fields['password']], $salt);
        }
    }

    /**
     * Manages the URL to return to after login.
     * 
     * @param string $referrer URL string
     * @return string URL string
     */
    public function referrer($referrer = null)
    {
        if (!is_null($referrer)) {
            $this->session->referrer = $referrer;
        }

        return $this->session->referrer;
    }
}
