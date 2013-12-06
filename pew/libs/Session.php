<?php

namespace pew\libs;

/**
 * Session management class.
 * 
 * @package pew/libs
 * @author ifcanduela <ifcanduela@gmail.com>
 */
class Session
{
    /**
     * Array index for the flash messages.
     * 
     * @var string
     */
    const FLASH_DATA = '__flash__';

    /**
     * Array index for the generated CSRF token.
     *
     * @var string
     */
    const CSRF_TOKEN = '__CSRF_TOKEN__';

    /**
     * Session sub-index for the current instance.
     *
     * @var string
     */
    private $group = '';

    /**
     * Static session flash data.
     *
     * @var string
     */
    private static $flash_data = array();

    /**
     * Session identifier.
     * 
     * @var string
     */
    private $session_id;

    /**
     * Setups a session management class instance
     *
     * @param string $group
     */
    public function __construct($group = null)
    {
        if (!$group) {
            $group = basename(getcwd());
        }

        $this->group($group);
        $this->open();
    }

    /**
     * Starts the session.
     * 
     * @return bool True if the session is started, false otherwise
     */
    protected function open()
    {
        if (session_status() === PHP_SESSION_DISABLED) {
            throw new \RuntimeException("Native session handling is disabled");
        }
        
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $this->session_id = session_id();

        if (!array_key_exists($this->group(), $_SESSION)) {
            $_SESSION[$this->group()] = array();
            $_SESSION[$this->group()][self::FLASH_DATA] = array();
        }

        $this->setup_flash_data();

        return !empty($this->session_id);
    }

    /**
     * Checks if the session is started.
     * 
     * @return boolean True if a session is started, false otherwise
     */
    public function is_open()
    {
        return session_status() === PHP_SESSION_ACTIVE;
    }

    /**
     * Closes the current session if there is one.
     * 
     * @return bool True if the session was open beforehand, false otherwise
     */
    public function close()
    {
        if (!empty($this->session_id)) {
            session_destroy();
            $this->session_id = false;
            return true;
        }

        return false;
    }

    /**
     * Configures the group name in the session array.
     * 
     * @param string $group Session group
     * @return string Session group
     */
    protected function group($group = null)
    {
        if (!is_null($group)) {
            $this->group = $group;
        }

        return $this->group;
    }

    /**
     * Copy, then delete the flash data from the session.
     * 
     * @return int Amount of flash items
     */
    protected function setup_flash_data()
    {
        if (!empty($_SESSION[$this->group()][self::FLASH_DATA])) {
            self::$flash_data = $_SESSION[$this->group()][self::FLASH_DATA];
            $_SESSION[$this->group()][self::FLASH_DATA] = array();
        }

        return count(self::$flash_data);
    }

    /**
     * Get the current flash messages.
     *
     * @return array Associative array with current messages
     */
    public function flash_data()
    {
        return self::$flash_data;
    }

    /**
     * Check if a key exists in the flash array.
     * 
     * If no key is passed, it return true if there is any key set.
     * 
     * @param string $key Key to check
     * @return boolean True if the key exists, false otherwise.
     */
    public function has_flash($key = null)
    {
        if (is_null($key)) {
            return 0 !== count(self::$flash_data);
        }

        return array_key_exists($key, self::$flash_data);
    }

    /**
     * Sets a flash key for the next request.
     * 
     * @param string $key Flash key to set
     * @param mixed $message Data to set the key to
     */
    public function set_flash($key, $message)
    {
        $_SESSION[$this->group()][self::FLASH_DATA][$key] = $message;
    }

    /**
     * Retrieve a key from the flash array.
     * 
     * @param string $key Key to retrieve
     * @return mixed Data corresponding to the key, null if it does not exist
     */
    public function get_flash($key)
    {
        if ($this->has_flash($key)) {
            return self::$flash_data[$key];
        }

        return null;
    }

    /**
     * Get or set a flash value.
     * 
     * @param string $key Flash key
     * @param mixed $value Flash value
     * @return mixed Flash value
     */
    public function flash($key, $value = null)
    {
        if (!is_null($value)) {
            $this->set_flash($key, $value);
        } elseif ($this->has_flash($key)) {
            return $this->get_flash($key);
        }

        return null;
    }

    /**
     * Set a key in the session array.
     * 
     * @param string|int $key Key to set
     * @param mixed $value Value to set
     */
    public function set($key, $value)
    {
        $_SESSION[$this->group()][$key] = $value;
    }

    /**
     * Retrieves a value from the session array.
     * 
     * @param string|int $key Key to retrieve
     * @param mixed $default Default value to return
     * @return mixed Value of the key, default if it does not exist
     */
    public function get($key = null, $default = null)
    {
        if (is_null($key)) {
            $return = $_SESSION[$this->group()];
            unset($return[self::FLASH_DATA]);
            return $return;
        } else if ($this->exists($key)) {
            return $_SESSION[$this->group()][$key];
        }

        return $default;
    }

    /**
     * Check if a key is set in the session array.
     * 
     * @param string|int $key Key to check
     * @return bool True if the key is set, false otherwise
     */
    public function exists($key)
    {
        return array_key_exists($key, $_SESSION[$this->group()]);
    }

    /**
     * Removes a key from the session array.
     * 
     * @param string|int $key Key to remove
     * @return bool True if the key existed, false otherwise
     */
    public function delete($key)
    {
        $return = $this->exists($key);
        
        unset($_SESSION[$this->group()][$key]);

        return $return;
    }

    /**
     * Get a random security token.
     * 
     * @return string A security token.
     */
    public function get_token()
    {
        $token = md5(uniqid());
        $this->set(self::CSRF_TOKEN, $token);

        return $token;
    }

    /**
     * Check a token string agains a previously-set token.
     * 
     * @param string $token Token to check
     * @return boolean True if the token is valid, false otherwise
     */
    public function check_token($token)
    {
        if ($this->exists(self::CSRF_TOKEN)) {
            return $token === $this->get(self::CSRF_TOKEN);
        }

        return false;
    }

    public function __set($key, $value)
    {
        return $this->set($key, $value);
    }

    public function __get($key)
    {
        return $this->get($key);
    }

    public function __unset($key)
    {
        return $this->delete($key);
    }

    public function __isset($key)
    {
        return $this->exists($key);
    }
}
