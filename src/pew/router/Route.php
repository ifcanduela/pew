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
    protected $defaults = [];

    /** @var array */
    protected $params = [];

    /** @var array */
    protected $conditions = [];

    /**
     * @param array $routeInfo
     */
    public function __construct(array $routeInfo)
    {
        $this->handler = $routeInfo[1]['controller'];
        $this->defaults = $routeInfo[1]['defaults'] ?? [];
        $this->params = $routeInfo[2];
        $this->conditions = $routeInfo[1]['conditions'] ?? [];
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

    public function getConditions()
    {
        return $this->conditions ?? [];
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
        return $this->params[$key] ?? $this->defaults[$key] ?? $default;
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
}
