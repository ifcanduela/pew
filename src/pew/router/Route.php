<?php

namespace pew\router;

/**
 * The Route class is a Value Object representing a matched route.
 */
class Route implements \ArrayAccess
{
    /** @var string */
    protected $path;

    /** @var mixed */
    protected $handler;

    /** @var array */
    protected $methods = ['*'];

    /** @var array */
    protected $defaults = [];

    /** @var array */
    protected $params = [];

    /** @var array */
    protected $conditions = [];

    /** @var array */
    protected $filters = [];

    /** @ver array */
    protected $before = [];

    /** @ver array */
    protected $after = [];

    /**
     * Create an empty route object.
     */
    public function __construct()
    {
    }

    /**
     * Create a Route from an array.
     *
     * @param array $data
     * @return Route
     */
    public static function fromArray(array $data): self
    {
        $methods = ['*'];

        if (isset($data['methods'])) {
            $methods = preg_split('/\W+/', strtoupper($data['methods']));
        }

        $path = '/' . ltrim($data['path'], '/');

        $route = new Route();
        $route
            ->path($path)
            ->methods(...$methods)
            ->handler($data['handler']);

        if (isset($data['defaults'])) {
            $route->defaults($data['defaults']);
        }

        if (isset($data['filters'])) {
            $route->filters(...$data['filters']);
        }

        if (isset($data['before'])) {
            $route->before($data['before']);
        }

        if (isset($data['after'])) {
            $route->after($data['after']);
        }

        return $route;
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
     * Get the handler associated to the route.
     *
     * @return mixed
     */
    public function getHandler()
    {
        return $this->handler;
    }

    /**
     * Get the route conditions.
     *
     * @return array
     */
    public function getConditions()
    {
        return $this->conditions ?? [];
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
     * Get the default values for path placeholders.
     *
     * @return array
     */
    public function getDefaults()
    {
        return $this->defaults;
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

        return $key === 'rest' ? explode('/', $value) : $value;
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
    public function checkParam($key): bool
    {
        return isset($this->params[$key]) || isset($this->defaults[$key]);
    }

    public function offsetExists($key)
    {
        return $this->checkParam($key);
    }

    public function offsetGet($key)
    {
        return $this->getParam($key);
    }

    public function offsetSet($key, $value)
    {
        throw new \BadMethodCallException("Route is read-only: cannot set value {$key}");
    }

    public function offsetUnset($key)
    {
        throw new \BadMethodCallException("Route is read-only: cannot unset value {$key}");
    }

    /**
     * Set the route path.
     *
     * @param string $path
     * @return Route
     */
    public function path(string $path): self
    {
        $this->path = $path;

        return $this;
    }

    /**
     * Set the route handler.
     *
     * @param string|callable $handler
     * @return Route
     * @throws \Exception
     */
    public function handler($handler): self
    {
        if (!$handler) {
            throw new \Exception('Route handler cannot be empty');
        }

        $this->handler = $handler;

        return $this;
    }

    /**
     * Set the route methods.
     *
     * @param string|string[] ...$methods
     * @return Route
     */
    public function methods(string ...$methods): self
    {
        $this->methods = array_map('strtoupper', $methods);

        return $this;
    }

    /**
     * Set the route filters.
     *
     * @param string|string[] ...$filters
     * @return Route
     */
    public function filters(string ...$filters): self
    {
        $this->filters = $filters;

        return $this;
    }

    /**
     * Set the route placeholder default values.
     *
     * @param array $defaults
     * @return Route
     */
    public function defaults(array $defaults): self
    {
        $this->defaults = $defaults;

        return $this;
    }

    /**
     * Set the 'before' middleware class list.
     *
     * @param string[] $before
     * @return Route
     */
    public function before(array $before): self
    {
        $this->before = $before;

        return $this;
    }

    /**
     * Set the 'after' middleware class list.
     *
     * @param string[] $after
     * @return Route
     */
    public function after(array $after): self
    {
        $this->after = $after;

        return $this;
    }


    /**
     * Set a default value for a route placeholder.
     *
     * @param string $placeholderName
     * @param mixed $value
     * @return Route
     */
    public function default($placeholderName, $value = null): self
    {
        $this->defaults[$placeholderName] = $value;

        return $this;
    }

    /**
     * Create a route for a path.
     *
     * @param string $path
     * @return Route
     */
    public static function from(string $path): self
    {
        $r = new static;
        $r->path('/' . ltrim($path, '/'));

        return $r;
    }
}
