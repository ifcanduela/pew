<?php

namespace pew\router;

/**
 * The Route class is a Value Object representing a matched route.
 */
class Group extends Route
{
    /** @var array */
    protected $routes = [];

    /** @var string */
    protected $prefix;

    /**
     *
     * @param  Route[] $routes
     * @return self
     */
    public function routes(array $routes)
    {
        $this->routes = $routes;

        return $this;
    }

    /**
     * Get the routes in the group
     * @return Route[]
     */
    public function getRoutes()
    {
        $processedRoutes = [];

        foreach ($this->routes as $route) {
            if (is_array($route)) {
                $route = Route::fromArray($route);
            }

            if ($this->prefix) {
                $route->path(preg_replace('~/+~', '/', "{$this->prefix}{$route->path}"));
            }

            if ($this->before) {
                $route->before = array_unique(array_merge($this->before, $route->before));
            }

            if ($this->after) {
                $route->after = array_unique(array_merge($this->after, $route->after));
            }

            if ($this->handler) {
                $route->handler = $this->handler;
            }

            if ($this->methods && $route->methods === ['*']) {
                $route->methods = $this->methods;
            }

            $processedRoutes[] = $route;
        }

        return $processedRoutes;
    }

    /**
     * Set a prefix for all routes in the group.
     *
     * @param  string $prefix
     * @return self
     */
    public function prefix(string $prefix)
    {
        $this->prefix = $prefix;

        return $this;
    }
}
