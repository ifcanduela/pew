<?php declare(strict_types=1);

namespace pew\router;

class RouteBuilder
{
    /** @var Group|null */
    private static $group = null;

    /** @var array */
    private static $routes = [];

    /**
     * Get all routes.
     *
     * This will reset the route list.
     *
     * @return Route[]
     */
    public static function collect(): array
    {
        $routes = static::$routes;
        static::$routes = [];

        return $routes;
    }

    /**
     * Create a GET/POST route.
     *
     * @param string $path
     * @param array $methods
     * @return Route
     */
    public static function from(string $path, array $methods = []): Route
    {
        $methods = count($methods) ? $methods : ["GET", "POST"];
        $route = Route::from($path)->methods(...$methods);

        if (static::$group) {
            static::$group->add($route);
        } else {
            static::$routes[] = $route;
        }

        return $route;
    }

    /**
     * Create a GET route.
     *
     * @param string $path
     * @return Route
     */
    public static function get(string $path): Route
    {
        return static::from($path, ["GET"]);
    }

    /**
     * Create a POST route.
     *
     * @param string $path
     * @return Route
     */
    public static function post(string $path): Route
    {
        return static::from($path, ["POST"]);
    }

    /**
     * Create a PUT route.
     *
     * @param string $path
     * @return Route
     */
    public static function put(string $path): Route
    {
        return static::from($path, ["PUT"]);
    }

    /**
     * Create a DELETE route.
     *
     * @param string $path
     * @return Route
     */
    public static function delete(string $path): Route
    {
        return static::from($path, ["DELETE"]);
    }

    /**
     * Create a routing group.
     *
     * @param callable|null $callback
     * @return Group
     */
    public static function group(callable $callback = null): Group
    {
        $g = new Group();

        static::$group = $g;

        if ($callback) {
            $callback($g);
        }

        static::$group = null;
        static::$routes[] = $g;

        return $g;
    }
}
