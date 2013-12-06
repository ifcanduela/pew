<?php

namespace pew\libs;

/**
 * Router class.
 * 
 * This class takes a string of segments and tries to fit it into any of a list
 * of pre-configured route patterns.
 *
 * @package pew/libs
 * @author ifernandez <ifcanduela@gmail.com>
 */
class Router
{
    const HTML = 'html';
    const XML  = 'xml';
    const JSON = 'json';

    const CONTROLLER_SEGMENT = 0;
    const ACTION_SEGMENT = 1;
    const PARAMETER_SEGMENT = 2;

    private $default_controller = '';
    private $default_action     = '';

    private $response_types = [
        ':' => self::JSON,
        '@' => self::XML,
    ];

    private $segments = [];

    private $token_prefix = '!';
    private $sequence_prefix = '*';

    private $routes = [];

    private $uri;

    public function __construct(array $routes = [])
    {
        foreach ($routes as $key => $route) {
            $this->add($route);
        }
    }

    /**
     * Adds a route to the routing arrays.
     * 
     * @param array $route Array with methods, origin and destination
     */
    public function add(array $route)
    {
        if (count($route) < 2) {
            throw new \Exception("Malformed route found.");
        }

        $route[0] = '/'. trim($route[0], '/');
        $route[1] = '/'. trim($route[1], '/');

        if (isSet($route[2])) {
            $methods = preg_split('/\W+/', strtoupper($route[2]));
        } else {
            $methods = ['GET'];
        }

        foreach ($methods as $method) {
            if (!isSet($this->routes[$method])) {
                $this->routes[$method] = [];
            }

            $this->routes[$method][] = $route;
        }
    }

    /**
     * Set or get the default controller.
     *
     * @param string $controller
     * @return string The controller name
     */
    public function default_controller($controller = null)
    {
        if (!is_null($controller)) {
            if (is_string($controller)) {
                $this->default_controller = $controller;
            } else {
                throw new \InvalidArgumentException(__CLASS__ . '::' . __FUNCTION__ . '  expects a string');
            }
        }

        return $this->default_controller;
    }

    /**
     * Set or get the default action.
     *
     * @param  string $action
     * @return string The action name
     */
    public function default_action($action = null)
    {
        if (!is_null($action)) {
            if (is_string($action)) {
                $this->default_action = $action;
            } else {
                throw new \InvalidArgumentException(__CLASS__ . '::' . __FUNCTION__ . '  expects a string');
            }
        }

        return $this->default_action;
    }

    /**
     * Set or get the named token prefix.
     *
     * @param  string $prefix
     * @return string The current prefix
     */
    public function token_prefix($prefix = null)
    {
        if (!is_null($prefix)) {
            $this->token_prefix = $prefix;
        }

        return $this->token_prefix;
    }

    /**
     * Set or get the seuqential token prefix.
     *
     * @param  string $prefix
     * @return string The current prefix
     */
    public function sequence_prefix($prefix = null)
    {
        if (!is_null($prefix)) {
            $this->sequence_prefix = $prefix;
        }

        return $this->sequence_prefix;
    }

    /**
     * Get the controller.
     * 
     * @return string The controller name
     */
    public function controller()
    {
        return isSet($this->segments[self::CONTROLLER_SEGMENT]) 
             ? $this->segments[self::CONTROLLER_SEGMENT] 
             : $this->default_controller;
    }

    /**
     * Get the action.
     * 
     * @return string The action name
     */
    public function action()
    {
        $action = isSet($this->segments[self::ACTION_SEGMENT]) 
             ? $this->segments[self::ACTION_SEGMENT] 
             : $this->default_action;
        
        if (isSet($action{0}) && !ctype_alpha($action{0})) {
            $action = substr($action, 1);
        }

        return $action;
    }

    /**
     * Get a parameter.
     *
     * If NULL, all parameters are returned.
     *
     * @param int $n A parameter position or
     * @return array Parameters
     */
    public function parameters($n = null)
    {
        $parameters = array_slice($this->segments, 2);
        
        if (is_numeric($n)) {
            return isSet($parameters[$n]) ? $parameters[$n] : null;
        } else {
            return $parameters;
        }
    }

    /**
     * Returns a response type based on the action prefix.
     * 
     * @return string One of the configured response types.
     */
    public function response_type()
    {
        $action = @$this->segments[self::ACTION_SEGMENT];

        if (!ctype_alpha($action{0})) {
            switch ($action{0}) {
                case ':':
                    return self::JSON;
                case '@':
                    return self::XML;
                default:
                    return @$this->response_types[$action{0}];
            }
        }

        return self::HTML;
    }

    /**
     * Get the current URI.
     * 
     * @return string The URI
     */
    public function uri()
    {
        return $this->uri;
    }

    /**
     * Extracts controller, action and parameters from a URI.
     * 
     * @param string $uri Uri from the browser
     * @param string $request_method HTTP request verb
     * @return Router The Router object
     */
    public function route($uri, $request_method = 'GET')
    {
        $uri = '/' . trim($uri, '/');
        $this->uri = $uri;

        $request_method = strtoupper($request_method);

        if (array_key_exists($request_method, $this->routes)) {
            foreach ($this->routes[$request_method] as $route) {
                if ($matches = $this->match($route, $uri)) {
                    $segments = $this->build($uri, $route);
                    break;
                }
            }
        }

        if (!isSet($segments)) {
            $this->segments = $this->build($uri, array());
        } else {
            $this->segments = $segments;
        }

        return $this;
    }

    /**
     * Check if a URI matches a route.
     * 
     * @param array $route A route to match
     * @param string $segments URI to match
     * @return array|bool Matches or false
     */
    protected function match(&$route, $segments)
    {
        $pattern = $this->regex($route[0]);

        if (preg_match("~^$pattern\$~", $segments, $matches)) {
            $route['pattern'] = "~^$pattern$~";

            foreach ($matches as $k => $v) {
                if (is_numeric($k)) {
                    unset($matches[$k]);
                }
            }

            $route['matches'] = $matches;

            return $route;
        }

        return false;
    }

    public function regex($pattern)
    {
        $suffix = '';
        $pattern = trim($pattern, '/');
        $pattern_segments = explode('/', $pattern);

        # check if last pattern is '/*'
        if ($pattern_segments[count($pattern_segments) - 1] === '*') {
            # remove it
            array_pop($pattern_segments);
            // @todo Check if the pattern can be improved
            $suffix = '/?(?P<sequential>.*)';
        }

        foreach ($pattern_segments as $position => $placeholder) {
            $token = $placeholder;

            if ($placeholder) {
                switch ($placeholder{0}) {
                    case '#':
                        $token = preg_replace('~#([^/]+)~', '(?P<$1>\d+)', $placeholder);
                        break;
                    case ':':
                        $token = preg_replace('~:([^/]+)~', '(?P<$1>[^\/]+)', $placeholder);
                        break;
                    default:
                        break;
                }
            }

            $pattern_segments[$position] = $token;
        }

        $regex = join('/', $pattern_segments);

        return '/'.  $regex . $suffix;
    }

    /**
     * Builds a path with controller/action/parameters elements
     * 
     * @param string $segments URI segments
     * @param array $route Transformation route
     * @param array $matches Transformation values
     * @return array The transformed URI elements
     */
    protected function build($segments, $route = null)
    {
        if ($route) {
            $segments = trim($route[1], '/');
            $matches = $route['matches'];

            if (isSet($matches['sequential'])) {
                $matches['*'] = explode('/', $matches['sequential']);
                array_unshift($matches['*'], join('/', $matches['*']));
                $matches['*'][''] = $matches['*'][0];
            }

            $destination_segments = explode('/', $segments);

            foreach ($destination_segments as $key => $value) {
                
                switch ($value{0}) {
                    case $this->token_prefix:
                        $name = substr($value, 1);
                        if (isSet($matches[$name]) || $name) {
                            $destination_segments[$key] = $matches[$name];
                        }
                        break;
                    case $this->sequence_prefix:
                        $destination_segments[$key] = $matches['*'][substr($value, 1)];
                        break;
                    default:
                        break;
                }
            }

            $segments = join('/', $destination_segments);
        }
    
        $segments = array_values(array_filter(explode('/', $segments)));

        return $segments;
    }
}
