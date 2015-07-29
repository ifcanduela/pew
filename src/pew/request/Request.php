<?php

namespace pew\request;

use pew\libs\Env;
use pew\router\Route;

/**
 * A shell class that centralizes information about the current request.
 *
 * @package pew\libs
 * @author ifcanduela <ifcanduela@gmail.com>
 */
class Request
{
    const HTML = 'html';
    const XML = 'xml';
    const JSON = 'json';

    /** @var Route */
    protected $route;

    /** @var Env */
    protected $env;

    /** @var string */
    protected $response_type;

    /** @var string */
    protected $path;

    /**
     * Create a new Request object.
     * 
     * @param Env $env
     * @param Route $route
     */
    public function __construct(Env $env = null, Route $route = null)
    {
        $this->route = $route;
        $this->env = $env ?: new Env;
    }

    public function has_route()
    {
        return $this->route !== null;
    }

    /**
     * Check if the request was made from the same computer.
     * 
     * @return boolean
     */
    public function is_localhost()
    {
        return $this->env->local;
    }

    /**
     * Get the request headers.
     * 
     * @return array
     */
    public function headers()
    {
        return $this->env->headers;
    }

    /**
     * Get the URL scheme.
     *
     * This is either http or https.
     * 
     * @return string
     */
    public function scheme()
    {
        return $this->env->scheme;
    }

    /**
     * Get the server name.
     * 
     * @return string
     */
    public function hostname()
    {
        return $this->env->host;
    }

    /**
     * Get the port number of the request.
     * 
     * @return int
     */
    public function port()
    {
        return is_null($this->env->port) ? null : (int) $this->env->port;
    }

    /**
     * Get the path part of the URL.
     * 
     * @return string
     */
    public function path()
    {
        if (!$this->path) {
            $this->path = preg_replace('/(\.html|\.json|\.xml)$/', '', $this->env->path);
        }

        return $this->path;
    }

    /**
     * Get the name of the current script.
     *
     * Normally this is the location of the index.php file.
     * 
     * @return string
     */
    public function script_name()
    {
        return $this->env->script;
    }

    /**
     * Get the HTTP method of the request.
     * 
     * @return string An HTTP verb
     */
    public function method()
    {
        return $this->env->method;
    }

    /**
     * Check if the request was made via POST method.
     * 
     * @return boolean
     */
    public function is_post()
    {
        return $this->method() === Env::POST;
    }

    /**
     * Check if the request was made via GET method.
     * 
     * @return boolean
     */
    public function is_get()
    {
        return $this->method() === Env::GET;
    }

    /**
     * Get one or all the values in an array.
     * 
     * @param string $key Key name
     * @param mixed $default Value to return in case none is present
     * @return array One or all entries in the array
     */
    public function data($source, $key = null, $default = null)
    {
        if (!isSet($this->env->$source)) {
            return $default;
        }

        $data = $this->env->$source;

        if (is_null($key)) {
            return $data;
        }

        return isSet($data[$key]) ? $data[$key] : $default;
    }

    /**
     * Get one or all the values sent via POST.
     * 
     * @param string $key POST key name
     * @param mixed $default Value to return in case none is present
     * @return array One or all entries in the $_POST array
     */
    public function post($key = null, $default = null)
    {
        return $this->data('post', $key, $default);
    }

    /**
     * Get one or all the values sent via GET.
     * 
     * @param string $key GET key name
     * @param mixed $default Value to return in case none is present
     * @return array One or all entries in the $_GET array
     */
    public function get($key = null, $default = null)
    {
        return $this->data('get', $key, $default);
    }

    /**
     * Get one or all the values of the cookie.
     * 
     * @param string $key Cookie key name
     * @param mixed $default Value to return in case none is present
     * @return array One or all entries in the $_COOKIE array
     */
    public function cookie($key = null, $default = null)
    {
        return $this->data('cookie', $key, $default);
    }

    /**
     * Get one or all the files submitted in a form.
     * 
     * @param string $key File input name
     * @param mixed $default Value to return in case none is present
     * @return array One or all entries in the $_FILES array
     */
    public function files($key = null, $default = null)
    {
        return $this->data('files', $key, $default);
    }

    /**
     * Get one or all the values in the input stream.
     * 
     * @param string $key Input key name
     * @param mixed $default Value to return in case none is present
     * @return array One or all entries in the input stream
     */
    public function input($key = null, $default = null)
    {
        return $this->data('input', $key, $default);
    }

    /**
     * Get the site-relative URI.
     *
     * This method includes the possible format extension (.json, .html, .xml).
     * 
     * @return string
     */
    public function uri()
    {
        return $this->env->path;
    }

    /**
     * Get the resolved URI.
     * 
     * @return string
     */
    public function destination()
    {
        return $this->route->to();
    }

    /**
     * Get the controller slug.
     * 
     * @return string The controller slug
     */
    public function controller()
    {
        return $this->has_route() ? $this->route->controller() : false;
    }

    /**
     * Get the action slug.
     * 
     * @return string The action slug
     */
    public function action()
    {
        return $this->has_route() ? $this->route->action() : false;
    }

    /**
     * Get the action arguments.
     * 
     * @return string[] An array of action arguments
     */
    public function args()
    {
        return $this->route->args();
    }

    /**
     * Get one of the action arguments.
     * 
     * @param integer $index Zero-indexed argument
     * @return string Argument value
     */
    public function arg($index)
    {
        $args = $this->route->args();
        
        if (array_key_exists($index, $args)) {
            return $args[$index];
        }

        return null;
    }

    /**
     * Check if a key has been submitted.
     * 
     * @return boolean
     */
    public function has_key($key)
    {
        return array_key_exists($key, $_REQUEST);
    }

    /**
     * Get a value from the query string or the request body by key.
     * 
     * @param string $key
     * @return string
     */
    public function get_key($key)
    {
        return $_REQUEST[$key];
    }

    /**
     * Check if the destination is a closure or a controller/action pair.
     * 
     * @return boolean
     */
    public function is_callable()
    {
        return $this->route && $this->route->is_callable();
    }

    /**
     * Get the response type for the current request.
     *
     * Response type may be one of the following constants:
     * 
     *  - \pew\libs\Request::HTML
     *  - \pew\libs\Request::JSON
     *  - \pew\libs\Request::XML
     * 
     * @return string Response type
     */
    public function response_type()
    {
        if ($this->response_type) {
            return $this->response_type;
        }

        if (isSet($_POST['_format']) && in_array($_POST['_format'], ['hmtl', 'json', 'xml'])) {
            $this->response_type = $_POST['_format'];
            return $this->response_type;
        }

        $this->response_type = static::HTML;

        if (preg_match('/\.json$/', $this->env->path)) {
            $this->response_type = static::JSON;
        } elseif (preg_match('/\.xml$/', $this->env->path)) {
            $this->response_type = static::XML;
        }

        return $this->response_type;
    }

    /**
     * Check if the response type is HTML.
     *
     * Response stype is HTML by default.
     * 
     * @return boolean TRUE if response type is HTML
     */
    public function is_html()
    {
        return $this->response_type() === static::HTML;
    }

    /**
     * Check if the response type is JSON.
     *
     * Response stype is JSON when the action segment begins with a
     * ':' character or when a field named _response_type is submitted 
     * via POST with a value of 'json' (case-insensitive).
     * 
     * @return boolean TRUE if response type is JSON
     */
    public function is_json()
    {
        return $this->response_type() === static::JSON;
    }

    /**
     * Check if the response type is XML.
     *
     * Response stype is XML when the action segment begins with an 
     * '@' character or when a field named _response_type is submitted 
     * via POST with a value of 'xml' (case-insensitive).
     * 
     * @return boolean TRUE if response type is XML
     */
    public function is_xml()
    {
        return $this->response_type() === static::XML;
    }
}
