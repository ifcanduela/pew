<?php

namespace pew\router;

use function FastRoute\simpleDispatcher;
use FastRoute\Dispatcher;
use FastRoute\RouteCollector;

class RouteNotFound extends \RuntimeException
{
}

class InvalidHttpMethod extends \RuntimeException
{
}

/**
 * The Router class wraps the `nikic\FastRoute` library for slightly
 * taylor-made functionality.
 */
class Router
{
    /** @var Dispatcher */
    protected $dispatcher;

    /** @var array[] */
    public $routes = [];

    /**
     * Initialize a Router.
     *
     * Routes require a 'path' key and may have optional 'methods' and 'defaults' keys.
     *
     * @param array $routeData Array of routes
     */
    public function __construct(array $routeData)
    {
        $routes = $this->processRouteData($routeData);

        $this->dispatcher = simpleDispatcher(
            function (RouteCollector $r) use ($routes) {
                foreach ($routes as $data) {
                    $r->addRoute($data->getMethods(), $data->getPath(), $data);
                }
            }
        );
    }

    /**
     * Transform framework routes into Route objects.
     *
     * @param array $routeData
     * @return Route[]
     * @throws \Exception
     */
    protected function processRouteData(array $routeData)
    {
        foreach ($routeData as $data) {
            if (is_array($data)) {
                $data = Route::fromArray($data);
            }

            if ($data instanceof Group) {
                foreach ($data->getRoutes() as $route) {
                    $this->routes[] = $route;
                }
            } else {
                $this->routes[] = $data;
            }
        }

        return $this->routes;
    }

    /**
     * Get a route matching the provided request information.
     *
     * @param string $pathInfo
     * @param string $httpMethod
     * @return Route
     * @throws \RuntimeException
     * @throws \Exception
     */
    public function route(string $pathInfo, string $httpMethod)
    {
        # Find the matched route
        $matchedRoute = $this->dispatcher->dispatch($httpMethod, $pathInfo);

        if ($matchedRoute[0] === Dispatcher::NOT_FOUND) {
            throw new RouteNotFound("Route not found");
        }

        if ($matchedRoute[0] === Dispatcher::METHOD_NOT_ALLOWED) {
            throw new InvalidHttpMethod("Method not allowed");
        }

        /** @var Route $route */
        $route = clone $matchedRoute[1];
        $route->setParams($matchedRoute[2]);

        # If the handler is a string, replace placeholders with path params
        if (is_string($route->getHandler())) {
            $replacements = [];

            foreach ($route->getParams() as $key => $value) {
                $replacements["{{$key}}"] = $value;
            }

            $handler = strtr($route->getHandler(), $replacements);
            $route->setHandler($handler);
        }

        return $route;
    }
}
