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
class Registry implements \ArrayAccess
{
    protected $factories = [];

    protected $data = [];

    public function import(array $values)
    {
        foreach ($values as $k => $v) {
            $this->data[$k] = $v;
        }
    }

    public function export()
    {
        return $this->data;
    }

    protected function check_path($path, $collection)
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

    public function __isset($key)
    {
        return $this->offsetExists($key);
    }

    public function offsetExists($offset)
    {
        try {
            $this->get_path($offset, 'data');
        } catch (\Exception $e) {
            return false;
        }

        return true;
    }

    public function __get($key)
    {
        return $this->offsetGet($key);
    }

    public function offsetGet($offset)
    {
        try {
            $return = $this->get_path($offset, 'data');
        } catch (\Exception $e) {
            $return = $this->build($offset);

            if ($return) {
                $this->set_path($offset, $return, 'data');
            }
        }

        return $return;
    }

    public function __set($key, $value)
    {
        return $this->offsetSet($key, $value);
    }

    public function offsetSet($offset, $value)
    {
        return $this->set_path($offset, $value, 'data');
    }

    public function __unset($key)
    {
        return $this->offsetUnset($key);
    }

    public function offsetUnset($offset)
    {
        return $this->unset_path($offset, 'data');
    }

    public function register($key, callable $factory)
    {
        $this->set_path($key, $factory, 'factories');
    }

    public function unregister($key)
    {
        $this->unset_path($key, 'factories');
    }

    public function registered($key)
    {
        return $this->check_path($key, 'factories');
    }

    public function build($key)
    {
        $factory = $this->get_path($key, 'factories');

        return $factory($this);
    }
}
