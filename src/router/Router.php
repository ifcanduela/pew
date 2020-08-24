<?php declare(strict_types=1);

namespace pew\router;

use Exception;
use pew\router\exception\InvalidHttpMethod;
use pew\router\exception\RouteNotFound;
use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use FastRoute\RouteParser\Std as StdRouteParser;
use RuntimeException;
use function FastRoute\simpleDispatcher;

/**
 * The Router class wraps the `nikic\FastRoute` library for slightly
 * taylor-made functionality.
 */
class Router
{
    /** @var Dispatcher */
    protected $dispatcher;

    /** @var Route[] */
    public $routes = [];

    /**
     * Initialize a Router.
     *
     * Routes require a "path" key and may have optional "methods" and "defaults" keys.
     *
     * @param array $routeData Array of routes
     * @throws Exception
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
     * @throws Exception
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
     * @throws RuntimeException
     * @throws Exception
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

    /**
     * Create a URL from a route name and a list of parameters.
     *
     * @param string $routeName
     * @param array $routeParams
     * @return string A URL, path only
     */
    public function createUrlFromRoute(string $routeName, array $routeParams = []): string
    {
        $route = array_find_value($this->routes, function ($r) use ($routeName) {
            return $r->getName() == $routeName;
        });

        if (!$route) {
            throw new \LogicException("Named route not found: `{$routeName}`");
        }

        $routeParser = new StdRouteParser();
        $routes = $routeParser->parse($route->getPath());

        foreach ($routes as $route) {
            $url = "";
            $paramIndex = 0;

            foreach ($route as $segment) {
                if (is_string($segment)) {
                    $url .= $segment;
                    continue;
                }

                if ($paramIndex === count($routeParams)) {
                    throw new \LogicException("Not enough parameters given");
                }

                $url .= $routeParams[$paramIndex++];
            }

            if ($paramIndex === count($routeParams)) {
                return $url;
            }
        }

        throw new \LogicException("Too many parameters given");
    }

    /**
     * Check if a path matches the given named route.
     *
     * @param string $routeName
     * @param string $path
     * @param string $method
     * @return bool
     */
    public function isRoute(string $routeName, string $path , string $method): bool
    {
        try {
            $route = $this->route(rtrim($path, "/"), strtoupper($method));

            return $route->getName() === $routeName;
        } catch (Exception $e) {
        }

        return false;
    }
}
