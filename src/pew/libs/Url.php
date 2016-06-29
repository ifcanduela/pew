<?php

namespace pew\libs;

use pew\request\Request;

class Url
{
    public $request;
    public $routes = [];
    public $namedRoutes = [];

    public function __construct(Request $request, array $routes = [])
    {
        $this->request = $request;
        $this->routes = $routes;
    }

    /**
     * Get the base URL.
     *
     * @return string
     */
    public function base()
    {
        return $this->to();
    }

    /**
     * Get a URL to a path.
     *
     * @param string $path
     * @return string
     */
    public function to(string ...$path)
    {
        $path = rtrim('/' . join('/', $path), '/');
        $path = preg_replace('~\/+~', '/', $path);

        return $this->request->getSchemeAndHttpHost() . $path;
    }

    /**
     * obtain a URL to a named route.
     *
     * @param string $routeName
     * @param array $params
     * @return string
     */
    public function toRoute(string $routeName, array $params = [])
    {
        # arrange all the named routes
        if (!$this->namedRoutes) {
            foreach ($this->routes as $route) {
                if (isset($route['name']) && $route['name']) {
                    $this->namedRoutes[$route['name']] = $route;
                }
            }
        }

        if (isset($this->namedRoutes[$routeName])) {
            $route = $this->namedRoutes[$routeName];
            $path = $route['path'];

            # merge the defaults with the passed params
            if (isset($route['defaults'])) {
                $params = array_merge($route['defaults'], $params);
            }

            # replace all available params
            foreach ($params as $key => $value) {
                $path = str_replace('{' . $key . '}', $value, $path);
            }

            # clear all remaining optional parameters
            while (strpos($path, '}]') !== false) {
                // try to remove [\{var}]
                $path = preg_replace('~(\[\/\{[^}]+\}\])~', '', $path);
            }

            # check if there are any remaining mandatory parameters
            if (strpos($path, '{')) {
                throw new \Exception("Not all required placeholders could be filled for {$route['path']}");
            }

            # clean the optional parameter markers
            $path = str_replace(['[', ']'], '', $path);

            return $this->to($path);
        }

        throw new \RuntimeException("Route name not found: {$routeName}");
    }
}
