<?php

namespace pew\lib;

use ReflectionClass;
use ReflectionException;
use ReflectionFunction;
use ReflectionFunctionAbstract;
use ReflectionMethod;

class KeyNotFoundException extends \Exception {}

class Injector
{
    /** @var array */
    protected $containers = [];

    /**
     * Create an injector.
     *
     * @param array|array[] $containers One or more arrays or array-like objects.
     */
    public function __construct(...$containers)
    {
        $this->containers = array_reverse($containers);
    }

    /**
     * Add a value container to the end of the list.
     *
     * @param array $container An array or array-like object
     * @return self
     */
    public function appendContainer($container)
    {
        array_push($this->containers, $container);

        return $this;
    }

    /**
     * Add a value container to the beginning of the list.
     *
     * @param array $container An array or array-like object
     * @return self
     */
    public function prependContainer($container)
    {
        array_unshift($this->containers, $container);

        return $this;
    }

    /**
     * Get the list of injectable arguments for a function or method.
     *
     * The returned array can be used with `call_user_func_array()` or
     * `invokeArgs()`.
     *
     * @param ReflectionFunctionAbstract $method
     * @return array List of arguments
     * @throws KeyNotFoundException When an argument cannot be found
     * @throws ReflectionException
     */
    public function getInjections(ReflectionFunctionAbstract $method)
    {
        $injections = [];
        $parameters = $method->getParameters();

        foreach ($parameters as $param) {
            $found = false;
            $injection = null;

            # first try: typehint
            if ($paramType = $param->getType()) {
                try {
                    $injection = $this->findKey($paramType->getName());
                    $found = true;
                } catch (KeyNotFoundException $e) {
                }
            }

            # second try: argument name
            if (!$found) {
                try {
                    $injection = $this->findKey($param->getName());
                    $found = true;
                } catch (KeyNotFoundException $e) {
                }
            }

            # third try: argument default value
            if (!$found && $param->isDefaultValueAvailable()) {
                $injection = $param->getDefaultValue();
                $found = true;
            }

            if (!$found) {
                $paramName = $param->getName() . " (" . $param->getType() . ")";
                throw new KeyNotFoundException("Could not find a definition for {$paramName}.");
            }

            $injections[] = $injection;
        }

        return $injections;
    }

    /**
     * Find a key in any of the value containers.
     *
     * @param string $key
     * @return mixed The value of the key
     * @throws KeyNotFoundException When the key is not found
     */
    protected function findKey(string $key)
    {
        foreach ($this->containers as $c) {
            if (isset($c[$key]) || array_key_exists($key, $c)) {
                return $c[$key];
            }
        }

        throw new KeyNotFoundException("Key not found: {$key}");
    }

    /**
     * Create an instance of the class.
     *
     * @param string $className A fully-qualified class name
     * @return object A new object of the class
     * @throws KeyNotFoundException
     * @throws ReflectionException
     */
    public function createInstance(string $className)
    {
        $class = new ReflectionClass($className);
        $constructor = $class->getConstructor();

        if ($constructor) {
            $injections = $this->getInjections($constructor);
            return $class->newInstanceArgs($injections);
        }

        return $class->newInstance();
    }

    /**
     * Invokes a method in an object.
     *
     * @param object $object An object on which to invoke the method
     * @param string $methodName Method name
     * @return mixed Result of calling the method on the object
     * @throws KeyNotFoundException
     * @throws ReflectionException
     */
    public function callMethod($object, string $methodName)
    {
        if (is_string($object)) {
            $object = $this->createInstance($object);
        }

        if (!is_object($object)) {
            $method = __METHOD__;
            throw new \InvalidArgumentException("Invalid argument supplied to {$method}: \$object must be an object.");
        }

        $method = new ReflectionMethod($object, $methodName);
        $injections = $this->getInjections($method);

        return $method->invokeArgs($object, $injections);
    }

    /**
     * Invokes a function.
     *
     * @param callable $callable An object on which to invoke the method
     * @param object $boundObject Optional object to bind the closure to
     * @return mixed Result of calling the method on the object
     * @throws KeyNotFoundException
     * @throws ReflectionException
     */
    public function callFunction(callable $callable, $boundObject = null)
    {
        $function = new ReflectionFunction($callable);
        $injections = $this->getInjections($function);

        if (is_object($boundObject)) {
            $callable = \Closure::bind($callable, $boundObject, $boundObject);
        }

        return $callable(...$injections);
    }

    /**
     * Invoke any type of callable.
     *
     * @param callable $callable
     * @return mixed
     * @throws KeyNotFoundException
     * @throws ReflectionException
     */
    public function call(callable $callable)
    {
        if (is_string($callable) || $callable instanceof \Closure) {
            return $this->callFunction($callable);
        }

        return $this->callMethod(...$callable);
    }
}
