<?php

namespace pew\router;

/**
 * Router class.
 *
 * This class takes a string of segments and tries to fit it into any of a list
 * of pre-configured route patterns.
 *
 * @package pew\router
 * @author ifcanduela <ifcanduela@gmail.com>
 */
class Router
{
    /**
     * @var array
     */
    protected $routes = [];
    
    /**
     * @var Route
     */
    protected $matched_route = false;

    /**
     * Build a router instance.
     * 
     * @param array $routes [description]
     */
    public function __construct($routes)
    {
        $this->routes = $routes;
    }

    /**
     * Resolve a URL to a route.
     * 
     * @param string $uri
     * @param boolean|string $method
     * @return Route
     */
    public function resolve($uri, $method = false)
    {
        foreach ($this->routes as $route) {
            if ($route->match($uri, $method)) {
                return $this->matched_route = $route;
            }
        }

        return null;
    }

    /**
     * Add a new route.
     * 
     * @param Route $route
     */
    public function add(Route $route)
    {
        $this->routes[] = $route;
    }

    /**
     * Get the latest matched route.
     * 
     * @return Route|boolean
     */
    public function route()
    {
        return $this->matched_route;
    }
}
