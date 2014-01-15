<?php

namespace pew;

use pew\Pew;
use pew\libs\Str;
use pew\libs\Request;
use \pew\controllers\Error;

class ControllerException extends \RuntimeException {}
class ControllerActionMissingException extends ControllerException {}

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
     * Layout to use to render the controller's views.
     *
     * @var string
     */
    public $layout;
    
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
     * String prefixed to action names in this controller.
     * 
     * @var string
     */
    protected $action_prefix = '';
    
    /**
     * Session instance.
     *
     * @var Session
     */
    public $session = null;
    
    /**
     * Base name of the class, slugified.
     *
     * @var string
     */
    public $url_slug = '';

    /**
     * Set base objects and properties.
     * 
     * @param pew\View $view View to use
     */
    public function __construct($view = false)
    {
        $this->pew = Pew::instance();
        $this->request = $this->pew['request'];

        if ($view) {
            $this->view = $view;
        } else {
            $this->view = $this->pew->view();
        }

        # Setup the layout if it´s set
        if ($this->layout) {
            $this->view->layout($this->layout);
        }
                
        # Make sure $model is read through the __get magic method the first time
        unset($this->model);
        unset($this->auth);
        unset($this->session);
        
        # Controller folder name in the /views/ folder.
        $fqcn = new Str(get_class($this));
        $class_base_name = $fqcn->substring(1 + $fqcn->last_of('\\'));
        $this->url_slug = Str::underscores($class_base_name);

        # Global action prefix override
        if (!$this->action_prefix) {
            $this->action_prefix = $this->pew['action_prefix'];
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
    public function __call($action, array $parameters = [])
    {
        if (!method_exists($this, $this->action_prefix . $action)) {
            # If the $action method does not exist, show an error page
            throw new ControllerActionMissingException("Action {$this->action_prefix}{$action} for controller ". get_class($this) . " not found");
        }

        # Set default template before calling the action
        $this->view->template($this->url_slug . '/' . $action);
        $this->view->title(Str::title_case($action));

        # Everything's clear pink
        $view_data = call_user_func_array([$this, $this->action_prefix . $action], $parameters);

        if ($view_data === false) {
            $this->view->render = false;
        } elseif (empty($view_data)) {
            $view_data = [];
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
        } elseif (isSet($this->pew[$property])) {
            return $this->pew[$property];
        } 

        $class_name = Str::camel_case($property);

        if ($obj = $this->pew->library($class_name)) {
            $this->pew[$property] = $obj;
            return $obj;
        }
        
        throw new \RuntimeException("Property Controller::\$$property does not exist");
    }

    public function __invoke()
    {
        $args = func_get_args();
        $action = array_shigt($args);

        return $this->__call($action, $args);
    }
}
