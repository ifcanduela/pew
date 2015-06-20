<?php

namespace pew\router;

/**
 * Object representation of an application route.
 * 
 * @package pew/router
 * @author ifcanduela <ifcanduela@gmail.com>
 */
class Route
{
    /**
     * @var string
     */
    protected $from;

    /**
     * @var string|callable
     */
    protected $to;

    /**
     * @var string
     */
    protected $destination;
    
    /**
     * @var array
     */
    protected $methods = [
        'GET',
        'POST',
    ];
    
    /**
     * @var array
     */
    protected $with = [];
    
    /**
     * @var array
     */
    protected $matches = [];

    /**
     * Build a route.
     * 
     * @param string $from
     * @param string|callable $to
     */
    public function __construct($from, $to)
    {
        $this->from = $from === '/' ? $from : trim($from, '/');
        $this->to = $to;
    }

    /**
     * Add a default value for an optional placeholder.
     * 
     * @param string $param
     * @param mixed $value
     * @return Route
     */
    public function with($param, $value)
    {
        $this->with[$param] = $value;

        return $this;
    }

    /**
     * Specify the HTTP verbs for the route.
     * 
     * @param array|string $methods
     * @return Route
     */
    public function via($methods)
    {
        foreach (func_get_args() as $arg) {
            if (is_string($arg)) {
                $arg = preg_split('/\W+/', $methods);
            }

            $this->methods = array_unique(
                array_merge(
                    $this->methods, 
                    array_map('strtoupper', $methods)
                )
            );
        }

        return $this;
    }

    /**
     * Resolve the destination of the route.
     * 
     * @return string|callable
     */
    public function to()
    {
        if ($this->destination) {
            return $this->destination;
        }

        if (is_callable($this->to)) {
            return $this->to;
        }

        $to = $this->to;

        preg_match_all('`\{([^}]+)\}`', $to, $matches, PREG_SET_ORDER);

        if ($matches) {
            $replacements = array_merge($this->with, $this->matches);

            foreach ($matches as $match) {
                list($search, $key) = $match;

                if (array_key_exists($key, $replacements)) {
                    $to = str_replace($search, $this->matches[$key], $to);
                }
            }
        }

        return $this->destination = $to;
    }

    /**
     * Get the matched placeholders in the URI.
     * 
     * @return array
     */
    public function args()
    {
        $args = $this->matches;
        array_shift($args);
        $filtered_args = [];

        foreach ($args as $k => $v) {
            if (!is_numeric($k)) {
                $filtered_args[$k] = $v;
            }
        };

        return array_merge($this->with, $filtered_args);
    }

    public function splat()
    {
        return array_key_exists('__splat__', $this->matches) ? explode('/', trim($this->matches['__splat__'], '/')) : [];
    }

    /**
     * Test a URI against the route.
     * 
     * @param $uri
     * @param boolean|string $method
     * @return boolean
     */
    public function match($uri, $method = false)
    {
        $this->destination = null;

        if ($method && !in_array(strtoupper($method), $this->methods, true)) {
            return false;
        }

        $uri = $uri === '/' ? $uri : trim($uri, '/');
        $regex = $this->compile();

        return (bool) preg_match($regex, $uri, $this->matches);
    }

    /**
     * Get the controller part of the destination.
     * 
     * @return string
     */
    public function controller()
    {
        if ($this->is_callable()) {
            throw new \RuntimeException("Cannot retrieve controller for callable route.");
        }

        list($controller, $_) = explode('/', $this->to());

        return $controller;
    }

    /**
     * Get the action part of the destination.
     * 
     * @return string
     */
    public function action()
    {
        if ($this->is_callable()) {
            throw new \RuntimeException("Cannot retrieve action for callable route.");
        }

        list($_, $action) = explode('/', $this->to());

        return $action;
    }

    public function get_callable()
    {
        if (!$this->is_callable()) {
            throw new \RuntimeException("Cannot retrieve callable for controller/action route.");
        }

        return $this->to;
    }

    /**
     * Check if the destination is a closure or a controller/action pair.
     * 
     * @return boolean
     */
    public function is_callable()
    {
        return is_callable($this->to);
    }

    /**
     * Transform the route into a regular expression.
     * 
     * @return string
     */
    protected function compile()
    {
        $with = $this->with;
        $from = $this->from;
        $strict_end = '$';

        if (substr($from, -1) === '*') {
            $from = preg_replace('~\/?\*$~', '', $from);
            $strict_end =  '(?<__splat__>.*)';
        }

        $replacements = preg_replace_callback('`(/?)\{([^}]+)\}`', function ($matches) use ($with) {
            $forward_slash = $matches[1];
            $name = $matches[2];
            $regex = '[^/]+';
            
            if (false !== strpos($name, ':')) {
                list($name, $regex) = explode(':', $matches[2], 2);
            }

            $capture = $forward_slash . '(?<' . $name . '>' . $regex . ')';

            if (array_key_exists($matches[2], $with)) {
                $capture = '(' . $capture . ')?';
            }

            return $capture;
        }, $from);
        
        return '`^' . $replacements . $strict_end . '`';
    }

    /**
     * Create a new route object.
     * 
     * @param string $from
     * @param string|callback $to
     * @return Route
     */
    public static function create($from, $to)
    {
        return new self($from, $to);
    }
}
