<?php

namespace pew\libs;

/**
 * Simple registry class to store key/value pairs.
 *
 * Use the register(), unregister() and build() methods to manage factories.
 *
 * If a key is not set but a factory has been registered, the factory will be called and its
 * result will be stored as the value of the key, providing singleton-like behavior.
 *
 * Property access can use array syntax (['key']), object syntax (->key) or methods 
 * (offsetGet/offsetSet). Array and method modes can use path-like strings (sys.request.basepath) 
 * for nested keys.
 *
 * @package pew\libs
 * @author ifcanduela <ifcanduela@gmail.com>
 */
class Registry implements \ArrayAccess
{
    /**
     * Storage for the factory closures.
     * 
     * @var array
     */
    protected $factories = [];

    /**
     * Storage for key/value pairs.
     * 
     * @var array
     */
    protected $data = [];

    /**
     * Imports keys and values from an associative array into the current registry.
     * 
     * @param array $values
     */
    public function import(array $values)
    {
        foreach ($values as $k => $v) {
            $this->data[$k] = $v;
        }
    }

    /**
     * Exports the current contents of the array.
     *
     * This method does not export factories.
     * 
     * @return array
     */
    public function export()
    {
        return $this->data;
    }

    /**
     * Checks if a key (in path format) has been set.
     * 
     * @param string $path
     * @param string $collection
     * @return bool True if the offset exists, false otherwise
     */
    protected function check_path($path, $collection = 'data')
    {
        $offsets = explode('.', $path);
        $data = $this->$collection;

        while ($k = array_shift($offsets)) {
            if (!array_key_exists($k, $data)) {
                return false;
            }

            $data = $data[$k];
        }

        return true;
    }

    /**
     * Returns the value of a key (in path format).
     * 
     * @param $path
     * @param string $collection
     * @return mixed Value, if exists
     * @throws RuntimeException If the key does not exist.
     */
    protected function get_path($path, $collection = 'data')
    {
        $offsets = explode('.', $path);
        $data = $this->$collection;

        while ($k = array_shift($offsets)) {
            if (!array_key_exists($k, $data)) {
                throw new \RuntimeException("Key does not exist: {$path}");
            }

            $data = $data[$k];
        }

        return $data;
    }

    /**
     * Assigns a value to a key (in path format).
     * 
     * @param string $path
     * @param mixed $value
     * @param string $collection
     */
    protected function set_path($path, $value, $collection = 'data')
    {
        $offsets = explode('.', $path);
        $data =& $this->$collection;

        while ($k = array_shift($offsets)) {
            if (!array_key_exists($k, $data)) {
                $data[$k] = [];
            }

            $data =& $data[$k];
        }

        $data = $value;
    }

    /**
     * Removes a key (in path format).
     * 
     * @param string $path
     * @param string $collection
     */
    public function unset_path($path, $collection = 'data')
    {
        $offsets = explode('.', $path);
        $data =& $this->$collection;

        while ($k = array_shift($offsets)) {
            if (!array_key_exists($k, $data)) {
                return;
            }

            $data =& $data[$k];
        }

        unset($data);
    }

    /**
     * Checks if a key is present in the registry.
     * 
     * @param string $key
     * @return bool True if the key exists, false otherwise
     */
    public function __isset($key)
    {
        return $this->offsetExists($key);
    }

    /**
     * Checks if a key is present in the registry.
     * 
     * @param string $key
     * @return bool True if the key exists, false otherwise
     */
    public function offsetExists($offset)
    {
        return $this->check_path($offset);
    }

    /**
     * Retrieves a value from the registry.
     * 
     * @param string $key
     * @return mixed
     */
    public function __get($key)
    {
        return $this->offsetGet($key);
    }

    /**
     * Retrieves a value from the registry.
     * 
     * @param string $key
     * @param mixed $default Value to return id the key is not found
     * @return mixed
     */
    public function offsetGet($offset, $default = null)
    {
        if ($this->check_path($offset, 'data')) {
            return $this->get_path($offset, 'data');
        } elseif ($this->check_path($offset, 'factories')) {
            $return = $this->build($offset);
            $this->set_path($offset, $return, 'data');
            return $return;
        }

        return $default;
    }

    /**
     * Adds or updates a key in the registry.
     * 
     * @param string $key
     * @param mixed $value
     */
    public function __set($key, $value)
    {
        $this->offsetSet($key, $value);
    }

    /**
     * Adds or updates a key in the registry.
     * 
     * @param string $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value)
    {
        $this->set_path($offset, $value, 'data');
    }

    /**
     * Removes a key from the registry.
     * 
     * @param string $key
     */
    public function __unset($key)
    {
        $this->offsetUnset($key);
    }

    /**
     * Removes a key from the registry.
     * 
     * @param string $offset
     */
    public function offsetUnset($offset)
    {
        $this->unset_path($offset, 'data');
    }

    /**
     * Adds a factory closure to the registry.
     * 
     * @param string $key
     * @param  callable $factory
     */
    public function register($key, callable $factory)
    {
        $this->set_path($key, $factory, 'factories');
    }

    /**
     * Removes a factory closure from the registry.
     * 
     * @param string $key
     */
    public function unregister($key)
    {
        $this->unset_path($key, 'factories');
    }

    /**
     * Checks if a factory closure has been registered.
     * 
     * @param string $key
     * @return bool True if the key is registered, false otherwise
     */
    public function registered($key)
    {
        return $this->check_path($key, 'factories');
    }

    /**
     * Calls a registered factory.
     * 
     * @param string $key
     * @return mixed
     */
    public function build($key)
    {
        if ($this->check_path($key, 'factories')) {
            $factory = $this->get_path($key, 'factories');

            return $factory($this);
        }

        throw new \RuntimeException("Unregistered factory: {$path}");
    }
}
