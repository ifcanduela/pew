<?php declare(strict_types=1);

namespace pew\di;

use ArrayAccess;
use Closure;
use InvalidArgumentException;
use ReflectionClass;
use ReflectionException;
use ReflectionFunction;
use ReflectionFunctionAbstract;
use ReflectionMethod;
use ReflectionNamedType;
use RuntimeException;

class Injector
{
    /** @var array */
    protected array $containers = [];

    /**
     * Create an injector.
     *
     * @param Container|array ...$containers One or more arrays or array-like objects.
     */
    public function __construct(...$containers)
    {
        $this->containers = array_reverse($containers);
    }

    /**
     * Add a value container to the end of the list.
     *
     * @param array|ArrayAccess $container An array or array-like object
     * @return self
     */
    public function appendContainer($container): self
    {
        array_push($this->containers, $container);

        return $this;
    }

    /**
     * Add a value container to the beginning of the list.
     *
     * @param array|ArrayAccess $container An array or array-like object
     * @return self
     */
    public function prependContainer($container): self
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
     * @param bool $autoResolve
     * @return array List of arguments
     * @throws KeyNotFoundException When an argument cannot be found
     * @throws ReflectionException
     */
    public function getInjections(ReflectionFunctionAbstract $method, bool $autoResolve = false): array
    {
        $injections = [];
        $parameters = $method->getParameters();

        foreach ($parameters as $param) {
            $found = false;
            $injection = null;
            $classExists = false;
            $typeName = "";
            $paramType = $param->getType();
            $paramName = $param->getName();

            # First try: class typehint
            if ($paramType instanceof ReflectionNamedType) {
                $typeName = $paramType->getName();
                $classExists = class_exists($typeName);
                $interfaceExists = interface_exists($typeName);

                if ($classExists || $interfaceExists) {
                    try {
                        $injection = $this->findKey($typeName);
                        $found = true;
                    } catch (KeyNotFoundException $e) {
                    }

                    if (!$found && $autoResolve && $classExists) {
                        try {
                            $injection = $this->createInstance($typeName, $autoResolve);
                            $found = true;
                        } catch (KeyNotFoundException $e) {
                        }
                    }
                }
            }

            # Second try: argument name
            if (!$found) {
                try {
                    $injection = $this->findKey($paramName);
                    $found = true;
                } catch (KeyNotFoundException $e) {
                }
            }

            # Third try: argument default value
            if (!$found && $param->isDefaultValueAvailable()) {
                $injection = $param->getDefaultValue();
                $found = true;
            }

            # Fourth try: auto-resolve class name
            if (!$found && $classExists && $typeName) {
                try {
                    $injection = $this->createInstance($typeName);
                    $found = true;
                } catch (KeyNotFoundException $e) {
                }
            }

            if (!$found) {
                if ($paramType instanceof ReflectionNamedType) {
                    $paramName =  "\${$paramName} ({$paramType->getName()})";
                }

                throw new KeyNotFoundException("Could not find a definition for `{$paramName}` in `{$method->getName()}`");
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
            if (isset($c[$key])) {
                return $c[$key];
            }

            $isArrayLike = $c instanceof ArrayAccess;

            if ($isArrayLike) {
                if ($c->offsetExists($key)) {
                    return $c->offsetGet($key);
                }
            } elseif (array_key_exists($key, $c)) {
                return $c[$key];
            }
        }

        throw new KeyNotFoundException("Key not found: `{$key}`");
    }

    /**
     * Create an instance of the class.
     *
     * @param string $className A fully-qualified class name
     * @param bool $autoResolve
     * @return object A new object of the class
     * @throws KeyNotFoundException
     * @throws ReflectionException
     */
    public function createInstance(string $className, bool $autoResolve = false): object
    {
        $class = new ReflectionClass($className);
        $constructor = $class->getConstructor();

        if ($constructor) {
            $injections = $this->getInjections($constructor, $autoResolve);

            return $class->newInstanceArgs($injections);
        }

        return $class->newInstance();
    }

    /**
     * Invokes a method in an object.
     *
     * @param object|string $object An object on which to invoke the method, or a class name
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
            throw new InvalidArgumentException("Invalid argument supplied to `{$method}`: \$object must be an object.");
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
            $callable = Closure::bind($callable, $boundObject, $boundObject);
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
        if (is_string($callable) || $callable instanceof Closure) {
            return $this->callFunction($callable);
        }

        return $this->callMethod(...$callable);
    }

    /**
     * Attempt to instantiate an object of a class.
     *
     * @param string $className
     * @return object
     * @throws KeyNotFoundException
     * @throws ReflectionException
     */
    public function autoResolve(string $className): object
    {
        if (class_exists($className)) {
            return $this->createInstance($className, true);
        }

        throw new RuntimeException("Cannot auto-resolve `{$className}`: class not found");
    }
}
