<?php

namespace pew\router;

use function FastRoute\simpleDispatcher;
use FastRoute\RouteCollector;
use FastRoute\Dispatcher;
use Stringy\Stringy;

/**
 * The Router class wraps the nikic\FastRoute library for slightly taylor-made functionality.
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
        $this->dispatcher = simpleDispatcher(function($r) use ($routeData) {
            foreach ($routeData as $data) {
                if (isset($data['resource'])) {
                    $controller = $data['resource'];
                    $slug = Stringy::create($data['resource'])->slugify();

                    $r->addRoute(['GET', 'POST'], "/{$slug}/{id}/edit", [
                            'controller' => "{$controller}@edit",
                        ]);
                    $r->addRoute(['GET', 'POST'], "/{$slug}/{id}/delete", [
                            'controller' => "{$controller}@delete",
                        ]);
                    $r->addRoute(['GET', 'POST'], "/{$slug}/add", [
                            'controller' => "{$controller}@add",
                        ]);
                    $r->addRoute(['GET'], "/{$slug}/{id}", [
                            'controller' => "{$controller}@view",
                        ]);
                    $r->addRoute(['GET'], "/{$slug}", [
                            'controller' => "{$controller}@index",
                        ]);
                } else {
                    $methods = '*';

                    if (isset($data['methods'])) {
                        $methods = preg_split('/\W+/', strtoupper($data['methods']));
                    }

                    $path = '/' . ltrim($data['path'], '/');

                    $r->addRoute($methods, $path, $data);
                }
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

        return new Route($matchedRoute);
    }
}
