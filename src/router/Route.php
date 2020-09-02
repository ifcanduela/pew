<?php declare(strict_types=1);

namespace pew\router;

use ArrayAccess;
use BadMethodCallException;
use Exception;
use InvalidArgumentException;

/**
 * The Route class specifies the properties of a routable URL.
 */
class Route implements ArrayAccess
{
    /** @var string */
    protected $path;

    /** @var mixed */
    protected $handler;

    /** @var array */
    protected $methods = ["*"];

    /** @var array */
    protected $defaults = [];

    /** @var array */
    protected $params = [];

    /** @var array */
    protected $before = [];

    /** @var array */
    protected $after = [];

    /** @var string */
    protected $handlerNamespace = "";

    /** @var string */
    protected $name = "";

    /**
     * Create a Route from an array.
     *
     * @param array $data
     * @return Route
     * @throws Exception
     */
    public static function fromArray(array $data)
    {
        $methods = ["*"];

        if (isset($data["methods"])) {
            $methods = preg_split('/\W+/', strtoupper($data["methods"]));
        }

        $path = $data["path"] ?? $data["from"];

        $route = new Route();
        $route->setPath($path);
        $route->setMethods(...$methods);

        if (isset($data["handler"]) || isset($data["to"])) {
            $route->setHandler($data["handler"] ?? $data["to"]);
        }

        if (isset($data["defaults"])) {
            $route->setDefaults($data["defaults"]);
        }

        if (isset($data["before"])) {
            $route->setBefore($data["before"]);
        }

        if (isset($data["after"])) {
            $route->setAfter($data["after"]);
        }

        if (isset($data["namespace"])) {
            $route->setNamespace($data["namespace"]);
        }

        if (isset($data["name"])) {
            $route->setName($data["name"]);
        }

        return $route;
    }

    /**
     * Create a route for a path.
     *
     * @param string $path
     * @return Route
     */
    public static function from(string $path)
    {
        $r = new static;
        $r->setPath($path);

        return $r;
    }

    /**
     * Create a route group.
     *
     * @param Route[] $routes
     * @return Group
     */
    public static function group(array $routes = [])
    {
        return new Group($routes);
    }

    /**
     * Build a GET route.
     *
     * @param  string $path
     * @return Route
     */
    public static function get(string $path): Route
    {
        return static::from($path)->methods("get");
    }

    /**
     * Build a POST route.
     *
     * @param  string $path
     * @return Route
     */
    public static function post(string $path): Route
    {
        return static::from($path)->methods("post");
    }

    /**
     * Build a PUT route.
     *
     * @param  string $path
     * @return Route
     */
    public static function put(string $path): Route
    {
        return static::from($path)->methods("put");
    }

    /**
     * Build a DELETE route.
     *
     * @param  string $path
     * @return Route
     */
    public static function delete(string $path): Route
    {
        return static::from($path)->methods("delete");
    }

    /**
     * Get the path matched by the route.
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Set the route path.
     *
     * @param string $path
     * @return void
     */
    public function setPath(string $path)
    {
        $this->path = $path;
    }

    /**
     * Get the handler associated to the route.
     *
     * @return mixed
     */
    public function getHandler()
    {
        return $this->handler;
    }

    /**
     * Set the route handler.
     *
     * @param string|callable $handler
     * @return void
     * @throws Exception
     */
    public function setHandler($handler)
    {
        if (!$handler) {
            throw new Exception("Route handler cannot be empty");
        }

        $this->handler = $handler;
    }

    /**
     * Set the route handler.
     *
     * This method is an alias for setHandler()
     *
     * @param string|callable $handler
     * @return void
     * @throws Exception
     */
    public function setTo($handler)
    {
        $this->setHandler($handler);
    }

    /**
     * Get the namespace for the handler class.
     *
     * @return string
     */
    public function getNamespace()
    {
        return $this->handlerNamespace;
    }

    /**
     * Set a namespace for the handler.
     *
     * @param string $namespace
     * @return void
     */
    public function setNamespace(string $namespace)
    {
        $this->handlerNamespace = $namespace;
    }

    /**
     * Get the route name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set a name for the route.
     *
     * @param string $name
     * @return void
     */
    public function setName(string $name)
    {
        $this->name = $name;
    }

    /**
     * Get the applicable methods for the route.
     *
     * @return array
     */
    public function getMethods()
    {
        return $this->methods;
    }

    /**
     * Set the route methods.
     *
     * @param string ...$methods
     * @return void
     */
    public function setMethods(string ...$methods)
    {
        $this->methods = array_unique(array_map("strtoupper", $methods));
    }

    /**
     * Get the default values for path placeholders.
     *
     * @return array
     */
    public function getDefaults()
    {
        return $this->defaults;
    }

    /**
     * Set the route placeholder default values.
     *
     * @param array $defaults
     * @return void
     */
    public function setDefaults(array $defaults)
    {
        $this->defaults = $defaults;
    }

    /**
     * Set a default value for a route placeholder.
     *
     * @param string $placeholderName
     * @param mixed $value
     * @return void
     */
    public function setDefault($placeholderName, $value = null)
    {
        $this->defaults[$placeholderName] = $value;
    }

    /**
     * Get the 'before' middleware class list.
     *
     * @return string[]
     */
    public function getBefore()
    {
        return $this->before;
    }

    /**
     * Set the 'before' middleware class list.
     *
     * @param string[] $before
     * @return void
     */
    public function setBefore(array $before)
    {
        $this->before = $before;
    }

    /**
     * Get the 'after' middleware class list.
     *
     * @return string[]
     */
    public function getAfter()
    {
        return $this->after;
    }

    /**
     * Set the 'after' middleware class list.
     *
     * @param string[] $after
     * @return void
     */
    public function setAfter(array $after)
    {
        $this->after = $after;
    }

    /**
     * Get the value of a route parameter.
     *
     * @param mixed $key
     * @param mixed $default
     * @return mixed
     */
    public function getParam($key, $default = null)
    {
        $value = $this->params[$key] ?? $this->defaults[$key] ?? $default;

        return $key === "rest" ? explode("/", $value) : $value;
    }

    /**
     * Get all the parameters in the route.
     *
     * @return array
     */
    public function getParams()
    {
        return array_merge($this->defaults, $this->params);
    }

    /**
     * Set the route params.
     *
     * @param array $params
     * @return void
     */
    public function setParams(array $params)
    {
        $this->params = $params;
    }

    /**
     * Check if a route placeholder param exists.
     *
     * @param mixed $key
     * @return bool
     */
    public function checkParam($key)
    {
        return isset($this->params[$key]) || isset($this->defaults[$key]);
    }

    /**
     * Check if a route parameter exists.
     *
     * @param string $key
     * @return bool
     */
    public function offsetExists($key)
    {
        return $this->checkParam($key);
    }

    /**
     * Get a route parameter by key.
     *
     * @param string $key
     * @return mixed
     */
    public function offsetGet($key)
    {
        return $this->getParam($key);
    }

    /**
     * Set a route parameter value by key.
     *
     * Route parameters are read-only and cannot be set.
     *
     * @param string $key
     * @param mixed $value
     * @return void
     * @throws BadMethodCallException
     */
    public function offsetSet($key, $value)
    {
        throw new BadMethodCallException("Route is read-only: cannot set value `{$key}`");
    }

    /**
     * Unset a route parameter.
     *
     * Route parameters are read-only and cannot be unset.
     *
     * @param string $key
     * @return void
     * @throws BadMethodCallException
     */
    public function offsetUnset($key)
    {
        throw new BadMethodCallException("Route is read-only: cannot unset value `{$key}`");
    }

    /**
     * Provides a fluent interface for defining routes.
     *
     * @param string $method
     * @param array  $arguments
     * @return self
     */
    public function __call(string $method, array $arguments)
    {
        $methodName = "set" . ucfirst($method);

        if (method_exists($this, $methodName)) {
            $this->$methodName(...$arguments);
            return $this;
        }

        throw new InvalidArgumentException("Method `{$method}` does not exist");
    }
}
