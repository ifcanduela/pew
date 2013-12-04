<?php

namespace pew;

use pew\Pew;
use pew\libs\Request;
use pew\libs\Router;
use pew\libs\Session;

/**
 * The basic controller class, with some common methods and fields.
 * 
 * @package pew
 * @author ifcanduela <ifcanduela@gmail.com>
 */
class Controller
{
    /**
     * The Pew instance.
     * 
     * @var \pew\Pew
     */
    protected $pew;

    /**
     * Data submitted by the browser agent via POST method.
     *
     * If no POST data is submitted for the current request, $post will be set
     * to false.
     *
     * @var array
     */
    protected $post = array();

    /**
     * Data submitted within the URL string in key:value pairs.
     *
     * If no GET data is submitted for the current request, $get will be set to
     * false.
     *
     * @var array
     */
    protected $get = array();
    
    /**
     * Additional function libraries made available to the controller.
     *
     * $libs is an indexed array that holds the Class names of the 
     * libraries and an associative array that holds the library instances
     * 
     * @var array
     */
    public $libs = array();
    
    /**
     * The view file to use to render the action result.
     * 
     * Views will be found in app/views/{$controller}/{$view}.php
     *
     * @var View
     */
    public $view = null;
    
    /**
     * Whether to render a view after the action completes.
     *
     * This can be used to render JSON output printed within the action without
     * having to create an additional view file.
     *
     * @var bool
     */
    public $render = true;
    
    /**
     * The text resulting from processing the view
     *
     * @var string
     * @ccess protected
    */
    public $output = '';
    
    /**
     * Error flag for error pages.
     *
     * @var int
     */
    protected $error = '';
    
    /**
     * Wheter to require user authentication to complete the action.
     *
     * This way of requiring authentication will be replaced in the future.
     *
     * @var bool
     */
    public $require_auth = false;
    
    /**
     * Whether to instance a database controller or not.
     *
     * @var bool
     */
    protected $use_db = true;
    
    /**
     * Database access object instance.
     *
     * @var Model
     */
    public $model = null;
    
    /**
     * The request information. 
     * 
     * @var Request
     */
    public $request = null;

    /**
     * The route information. 
     * 
     * @var Router
     */
    public $route = null;

    /**
     * String prefixed to action names in this controller.
     * 
     * @var string
     */
    protected $action_prefix = '';
    
    /**
     * Auth instance.
     *
     * @var Auth
     */
    public $auth = null;

    /**
     * Session instance.
     *
     * @var Session
     */
    public $session = null;
    
    /**
     * Stores the controller's parameters.
     *
     * @var array
     */
    public $parameters = array();

    /**
     * Base name of the class, slugified.
     *
     * @var string
     */
    protected $url_slug = '';

    /**
     * The constructor instantiates the database and populates the instance
     * parameters.
     * 
     * @param pew\libs\Request $request The request information
     * @return void
     */
    public function __construct(Request $request = null, $view = false)
    {
        $this->pew = Pew::instance();

        # Assign Request, Route and View objects
        $this->request = $request ?: $this->pew->request();
        $this->route = $this->pew->router();

        if ($view) {
            $this->view = $view;
        } else {
            $this->view = $this->pew->view();
        }
        
        # Make sure $model is read through the __get magic method the first time
        unset($this->model);
        unset($this->auth);
        unset($this->session);
        
        # Controller file name in the /views/ folder.
        $this->url_slug = to_underscores(slugify(basename(get_class($this))));

        # Global action prefix override
        if (!$this->action_prefix && $this->pew['action_prefix']) {
            $this->action_prefix = $this->pew['action_prefix'];
        }
        
        # Function libraries
        # @todo Move this to the __get function
        if (is_array($this->libs)) {
            foreach ($this->libs as $p => $library_class_name) {
                $lib = $this->pew->library($library_class_name);
                
                if ($lib === false) {
                    throw new \RuntimeException("Library $library_class_name cound not be found.");
                }

                $this->libs[$p] = $lib;
            }
        }
        
        $parameters = $this->request->segments();
        
        # Manage the received URL parameters
        if (is_array($parameters)) {
            # Copy the parameters to the controller property.
            $this->parameters = $parameters;
            
            # Simplify access to POST data
            if (isset($parameters['form']) && $parameters['form']) {
                $this->post = $parameters['form'];
            } else {
                $this->post = false;
            }

            $this->get = array();

            # Simplify access to named parameters as GET data 
            if (isset($parameters['named']) && count($parameters['named']) !== 0) {
                $this->get += $parameters['named'];
            }
        }
    }
    
    /**
     * Main decision-maker of the framework, calling the appropriate method 
     * of the controller.
     * 
     * This function can be overwritten to modify the behavior or the 
     * function of the parameters, for an example see the example Pages 
     * controller.
     *
     * @param string $action The unprefixed action name
     * @param array $parameters Arguments for the action method
     * @return array An associative array to pass to the view
     */
    public function _action($action, array $parameters = [])
    {
        if (!method_exists($this, $this->action_prefix . $action)) {
            # If the $action method does not exist, show an error page
            $error = new \pew\controllers\Error($this->pew->request(), \pew\controllers\Error::ACTION_MISSING);
        }

        # Set default template before calling the action
        $this->view->template($this->url_slug . '/' . $action);
        $this->view->title(ucwords(str_replace('_', ' ', $action)));

        # Everything's clear pink
        $view_data = call_user_func_array(array($this, $this->action_prefix . $action), $parameters);

        if ($view_data === false) {
            $this->view->render = false;
        } elseif (!is_array($view_data)) {
            $view_data = compact('view_data');
        }

        return $view_data;
    }
    
    /**
     * Initialize the model and library objects when first accessed.
     *
     * @param string $property Controller property to read
     * @return object An object of the appropriate class
     */
    public function __get($property)
    {
        if ($property === 'model') {
            $this->model = $this->pew->model($this->url_slug);
            return $this->model;
        } elseif ($property === 'session') {
            $this->session = $this->pew->session();
            return $this->session;
        } elseif ($property === 'auth') {
            $this->auth = $this->pew->auth();
            return $this->auth;
        } elseif ($property === 'request') {
            $this->request = $this->pew->request();
            return $this->request;
        } elseif (array_key_exists($property, $this->libs)) {
            return $this->libs[$property];
        }
        
        throw new \RuntimeException("Property Controller::\$$property does not exist");
    }

    public function __invoke()
    {
        return call_user_func_array([$this, '_action'], func_get_args());
    }
}
