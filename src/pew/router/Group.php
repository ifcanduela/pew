<?php

namespace pew\router;

/**
 * The Route class is a Value Object representing a matched route.
 */
class Group extends Route
{
    protected $routes = [];
    protected $prefix;

    /**
     *
     * @param  Route[] $routes
     * @return static
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
                $route->path(preg_replace('~/+~', '/', "{$this->prefix}/{$route->path}"));
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

    public function prefix(string $prefix)
    {
        $this->prefix = $prefix;

        return $this;
    }
}
