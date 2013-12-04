<?php

namespace pew\libs;

/**
 * Simple registry class to store key/value pairs.
 *
 * Can be instantiated with the new keyword or as a singleton through
 * the instance() method.
 *
 * @package pew/libs
 * @author ifcanduela <ifcanduela@gmail.com>
 */
class Registry implements \Countable, \ArrayAccess
{
    /**
     * @var Registry Singleton instance of the registry
     */
    protected static $instance = null;

    /** 
     * @var array Key/value list
     */
    protected $items = [];

    /**
     * Constructor.
     *
     * Set to public to allow instantiation of multiple registries.
     */
    public function __construct(array $items = [])
    {
        $this->items = $items;
    }

    /**
     * Retrieves a singleton instance of the class.
     * 
     * @return Registry Singleton registry
     * @static
     */
    public static function instance()
    {
        if (!isset(static::$instance)) {
            static::$instance = new static;
        }

        return static::$instance;
    }

    /**
     * Import a set of keys and values into the current registry.
     * 
     * @param array $data Associative array with keys and values to import.
     */
    public function import(array $data)
    {
        foreach ($data as $key => $value) {
            $this->offsetSet($key, $value);
        }
    }

    /**
     * Export all keys and values in the current registry.
     * 
     * @return array An associative array
     */
    public function export()
    {
        return $this->items;
    }

    /**
     * Retrieve all the keys stored in the registry.
     * 
     * @return array Array of stored keys
     */
    public function keys()
    {
        return array_keys($this->items);
    }

    /**
     * Count the number of stored items.
     *
     * Countable implementation.
     * 
     * @return int Number of stored items
     */
    public function count()
    {
        return count($this->items);
    }

    /**
     * Check if an offset exists.
     *
     * ArrayAccess implementation.
     * 
     * @return bool True if the offset exists, false otherwise
     */
    public function offsetExists($offset)
    {
        return array_key_exists($offset, $this->items);
    }

    /**
     * Get the value at an offset.
     *
     * ArrayAccess implementation.
     * 
     * @return mixed The value at the offset.
     * @throws \InvalidArgumentException When the offset does not exist
     */
    public function offsetGet($offset)
    {
        if (!array_key_exists($offset, $this->items)) {
            throw new \InvalidArgumentException("The key {$offset} is not defined");
        }

        $value = $this->items[$offset];

        $is_callable = is_object($value) && method_exists($value, '__invoke');

        return $is_callable ? $value($this) : $value;
    }

    /**
     * Set the value for an offset.
     *
     * ArrayAccess implementation.
     * 
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        $this->items[$offset] = $value;
    }

    /**
     * Remove an offset.
     *
     * ArrayAccess implementation.
     *
     * @param string $offset The offset to remove
     */
    public function offsetUnset($offset)
    {
        unset($this->items[$offset]);
    }

    /**
     * Set a value in the registry.
     * 
     * @param string $key Key for the value
     * @param mixed Value to store
     */
    public function __set($key, $value)
    {
        return $this->offsetSet($key, $value);
    }

    /**
     * Get a stored value from the registry.
     * 
     * @param mixed $key Key for the value
     * @return mixed Stored value
     */
    public function __get($key)
    {
        return $this->offsetGet($key);
    }

    /**
     * Check if a key is in use.
     * 
     * @param mixed $key Key to check
     * @return bool True if the key has been set, false otherwise.
     */
    public function __isset($key)
    {
        return $this->offsetExists($key);
    }

    /**
     * Remove a stored value from the registry.
     * 
     * @param mixed $key Key to delete
     */
    public function __unset($key)
    {
        $this->offsetUnset($key);
    }
}
