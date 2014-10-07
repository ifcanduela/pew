<?php

namespace pew\libs;

/**
 * The Env class gathers information about the execution environment.
 *
 * @package pew\libs
 * @author ifcanduela <ifcanduela@gmail.com>
 */
class Env
{
    public $cwd;
    public $method;
    public $headers;
    public $scheme;
    public $host;
    public $port;
    public $path;
    public $script;
    
    public $segments;

    public $get;
    public $post;
    public $files;
    public $cookie;
    public $input;

    public $local;

    const POST = 'POST';
    const GET = 'GET';

    public function __construct($get = null, $post = null, $files = null, $cookie = null, $server = null)
    {
        $this->cwd = getcwd();
        
        if (isSet($_SERVER['SERVER_NAME'])) {
            $this->local = in_array($_SERVER['REMOTE_ADDR'], ['localhost', '127.0.0.1', '::1']);

            $this->method = isSet($_REQUEST['_method']) ? strtoupper($_REQUEST['_method']) : strtoupper($_SERVER['REQUEST_METHOD']);
            $this->scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
            $this->host = $_SERVER['SERVER_NAME'];
            $this->port = $_SERVER['SERVER_PORT'];
            
            $this->path = str_replace(basename($_SERVER['SCRIPT_NAME']), '', $_SERVER['SCRIPT_NAME']);
        } else {
            $this->local = true;
            
            $this->method = null;
            $this->scheme = null;
            $this->host = null;
            $this->port = null;

            $this->path = dirname($_SERVER['SCRIPT_NAME']);
        }

        $this->script = basename($_SERVER['SCRIPT_NAME']);

        if (function_exists('getAllHeaders')) {
            $this->headers = getAllHeaders();
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
        $this->input = $this->process_input_stream();
    }

    /**
     * Checks if the PATH_INFO server variable is available.
     * 
     * @return string A segment string like /segment1/segment2/segment3, or false
     */
    protected function get_segments_from_path_info()
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
    protected function get_script_name()
    {
        if (!isSet($_SERVER['REQUEST_URI'])) {
            return $_SERVER['SCRIPT_NAME'];
        }

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
    protected function extract_segments_from_script_name($request_script_name)
    {
        $dirname = dirname($_SERVER['SCRIPT_NAME']);
        
        if ($dirname !== '/') {
            $request_script_name = ltrim(str_replace($dirname, '', $request_script_name), '\\/');
        }

        $segments = str_replace(basename($_SERVER['SCRIPT_NAME']), '', $request_script_name);

        return '/' . trim($segments, '/');    
    }

    /**
     * Read the php://input stream.
     * 
     * @return array An associative array of keys and values
     */
    protected function process_input_stream()
    {
        $input_stream = file_get_contents('php://input');

        if ($input_stream) {
            parse_str($input_stream, $input);
            return $input;
        }

        return [];
    }
}
