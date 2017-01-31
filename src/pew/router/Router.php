<?php

namespace pew\router;

use function FastRoute\simpleDispatcher;
use FastRoute\Dispatcher;

/**
 * The Router class wraps the nikic\FastRoute library for slightly
 * taylor-made functionality.
 */
class Router
{
    /**
     * @var Dispatcher
     */
    protected $dispatcher;

    /**
     * Initialize a Router.
     *
     * Routes require a 'path' key and may have optional 'methods' and 'defaults' keys.
     *
     * @param array $routeData Array of routes
     */
    public function __construct(array $routeData)
    {
        $this->dispatcher = simpleDispatcher(function ($r) use ($routeData) {
            foreach ($routeData as $data) {
                if (is_array($data)) {
                    $data = Route::fromArray($data);
                }

                $r->addRoute($data->getMethods(), $data->getPath(), $data);
            }
        });
    }

    /**
     * Add a route definition.
     *
     * @param string $from Pathinfo pattern
     * @param mixed $handler Route result
     * @param string|array methods
     * @return Router
     */
    public function addRoute(string $from, $handler, $methods)
    {

        return $this;
    }

    /**
     * Get a route matching the provided request information.
     *
     * @param string $pathInfo
     * @param string $httpMethod
     * @return Route
     */
    public function route(string $pathInfo, string $httpMethod): Route
    {
        $matchedRoute = $this->dispatcher->dispatch($httpMethod, $pathInfo);

        if ($matchedRoute[0] === Dispatcher::NOT_FOUND) {
            throw new \RuntimeException("Route not found");
        }

        if ($matchedRoute[0] === Dispatcher::METHOD_NOT_ALLOWED) {
            throw new \RuntimeException("Method not allowed");
        }

        $route = $matchedRoute[1];
        $route->setParams($matchedRoute[2]);

        return $route;
    }
}
