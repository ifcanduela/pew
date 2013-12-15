<?php

namespace pew\libs;

/**
 * The Env class gathers information about the execution environment.
 *
 * @package pew/libs
 * @author ifcanduela <ifcanduela@gmail.com>
 */
class Env
{
    public $method;
    public $headers;
    public $scheme;
    public $host;
    public $port;
    public $path;
    public $script;
    
    public $segments;
    public $segments_array;

    public $get;
    public $post;
    public $files;
    public $cookie;

    private $local;

    public function __construct()
    {
        $this->init();
    }

    public function init()
    {
        if (isSet($_SERVER['HTTP_HOST'])) {
            $this->init_http();
        } else {
            $this->init_cli();
        }
    }

    protected function init_cli()
    {
        
    }

    protected function init_http()
    {
        $this->method = isSet($_POST['_method']) ? strtoupper($_POST['_method']) : strtoupper($_SERVER['REQUEST_METHOD']);
        $this->scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
        $this->host = $_SERVER['SERVER_NAME'];
        $this->port = $_SERVER['SERVER_PORT'];
        $this->path = str_replace(basename($_SERVER['SCRIPT_NAME']), '', $_SERVER['SCRIPT_NAME']);
        $this->script = basename($_SERVER['SCRIPT_NAME']);

        if (function_exists('getAllHeaders')) {
            $this->headers  = getAllHeaders();
        }

        $segments = $this->get_segments_from_path_info();

        if (false === $segments) {
            $request_script_name = $this->get_script_name();
            $segments = $this->extract_segments_from_script_name($request_script_name);
        }

        $this->segments = $segments;
        
        $this->get = $_GET;
        $this->post = $_POST;
        $this->files = $_FILES;
        $this->cookie = $_COOKIE;

        $this->local = in_array($_SERVER['REMOTE_ADDR'], ['localhost', '127.0.0.1', '::1']);
    }

    /**
     * Checks if the PATH_INFO server variable is available.
     * 
     * @return string A segment string like /segment1/segment2/segment3, or false
     */
    public function get_segments_from_path_info()
    {
        if (isSet($_SERVER['PATH_INFO'])) {
            return $_SERVER['PATH_INFO'];
        }

        return false;
    }

    /**
     * Gets the current script name, discarding the query string.
     * 
     * @return string URL to the script name, as provided by the server.
     */
    public function get_script_name()
    {
        $question_mark_position = strpos($_SERVER['REQUEST_URI'], '?');

        if (false !== $question_mark_position) {
            return substr($_SERVER['REQUEST_URI'], 0, $question_mark_position);
        }

        return $_SERVER['REQUEST_URI'];
    }

    /**
     * Get the segments information form the script name.
     * 
     * @param string $request_script_name URL to the current script
     * @return string A segments string like /segment1/segment2/segment3
     */
    public function extract_segments_from_script_name($request_script_name)
    {
        $script_relative = str_replace(dirname($_SERVER['SCRIPT_NAME']), '', $request_script_name);
        $segments = str_replace(basename($_SERVER['SCRIPT_NAME']), '', $script_relative);
        return '/' . trim($segments, '/');    
    }

    /**
     * Helper function to get an element from an array.
     * 
     * @param array $array Source array
     * @param mixed $key A key from the array
     * @param mixed $default Value to return if the key does not exist
     * @return mixed
     */
    protected function fetch(array $array, $key, $default)
    {
        if (array_key_exists($key, $array)) {
            return $array[$key];
        }

        return $default;
    }

    /**
     * Get a value from the $_GET array.
     * 
     * @param string $key A key from the array
     * @param mixed $default Value to return if the key does not exist
     * @return mixed
     */
    public function get($key = null, $default = null)
    {
        if (is_null($key)) {
            return $this->get;
        } else {
            return $this->fetch($this->get, $key, $default);
        }
    }

    /**
     * Get a value from the $_POST array.
     * 
     * @param string $key A key from the array
     * @param mixed $default Value to return if the key does not exist
     * @return mixed
     */
    public function post($key = null, $default = null)
    {
        if (is_null($key)) {
            return $this->post;
        } else {
            return $this->fetch($this->post, $key, $default);
        }
    }

    /**
     * Get the $_FILES array.
     * 
     * @param string $key A key from the array
     * @param mixed $default Value to return if the key does not exist
     * @return mixed
     */
    public function files($key = null, $default = null)
    {
        if (is_null($key)) {
            return $this->files;
        } else {
            return $this->fetch($this->files, $key, $default);
        }
    }

    /**
     * Gets the segment string or one of the segments.
     * 
     * @param  int $segment
     * @return string|null
     */
    public function segments($segment = null)
    {
        if (!isSet($this->segments_array)) {
            $this->segments_array = array_filter(explode('/', trim($this->segments, '/')));
        }

        if (is_numeric($segment)) {
            return array_key_exists($segment, $this->segments_array) ? $this->segments_array[$segment] : null;
        } else {
            return $this->segments;
        }
    }
}
