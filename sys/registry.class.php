<?php

/**
 * Simple registry class to store key/value pairs.
 *
 * Can be instantiated with the new keyword or as s singleton through
 * the instance() method.
 *
 * @package sys
 * @author ifcanduela <ifcanduela@gmail.com>
 */
class Registry
{
    /**
     * @var Registry
     * @access  protected
     */
    protected static $instance = null;

    /** 
     * @var array
     * @access protected
     */
    protected $items = array();

    /**
     * Constructor.
     *
     * Set to public to allow instantiation of multiple registries.
     */
    public function __construct()
    {
        
    }

    /**
     * Retrieves a singleton instance of the class.
     * 
     * @return Registry Singleton registry
     * @static
     */
    public static function instance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new self;
        }

        return self::$instance;
    }

    /**
     * Sets a value in the registry.
     * 
     * @param mixed $key Key for the value
     * @param mixed Value to store
     */
    public function __set($key, $value)
    {
        $this->items[$key] = $value;
    }

    /**
     * Gets a stored value from the registry.
     * 
     * @param mixed $key Key for the value
     * @return mixed Value stored or NULL
     */
    public function __get($key)
    {
        if (array_key_exists($key, $this->items)) {
            return $this->items[$key];
        }

        return null;
    }

    /**
     * Checks if a key is in use.
     * 
     * @param mixed $key Key to check
     * @return boolean True if the key has been set, false otherwise.
     */
    public function __isset($key)
    {
        return array_key_exists($key, $this->items);
    }

    public function import(array $data)
    {
        foreach ($data as $key => $value) {
            $this->items[$key] = $value;
        }
    }
}