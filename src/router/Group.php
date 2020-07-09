<?php declare(strict_types=1);

namespace pew\router;

use Exception;

/**
 * The Group class represents a grouping of routes with common properties.
 */
class Group extends Route
{
    /** @var Route[] */
    protected $routes = [];

    /** @var string */
    protected $prefix = "";

    /**
     * Build a route group.
     *
     * @param Route[] $routes
     */
    public function __construct(array $routes = [])
    {
        $this->routes = $routes;
    }

    /**
     * Set the routes in the group.
     *
     * @param Route[] $routes
     * @return self
     */
    public function routes(array $routes)
    {
        $this->routes += $routes;

        return $this;
    }

    /**
     * Get the routes in the group.
     *
     * @return Route[]
     * @throws Exception
     */
    public function getRoutes()
    {
        $processedRoutes = [];

        foreach ($this->routes as $route) {
            if (is_array($route)) {
                $route = Route::fromArray($route);
            }

            $routes = ($route instanceof static)
                    ? $route->getRoutes()
                    : [$route];

            foreach ($routes as $r) {
                $r = $this->mergeRoute($r);
                $processedRoutes[] = $r;
            }
        }

        return $processedRoutes;
    }

    /**
     * Set a prefix for all routes in the group.
     *
     * @param string $prefix
     * @return self
     */
    public function prefix(string $prefix)
    {
        $this->prefix = $prefix;

        return $this;
    }

    /**
     * Merge group properties into a route.
     *
     * @param Route $route
     * @return Route
     */
    protected function mergeRoute(Route $route)
    {
        if ($this->prefix) {
            $route->path(preg_replace("~/+~", "/", "{$this->prefix}{$route->path}"));
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

        if ($this->methods && $route->methods === ["*"]) {
            $route->methods = $this->methods;
        }

        return $route;
    }
}
