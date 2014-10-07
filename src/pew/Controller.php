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
     * Gets the URL slug corresponding to the controller name.
     * 
     * @return string
     */
    public function slug()
    {
        return Str::underscores(basename(get_class($this)), true)->slug();
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
            $this->model = $this->pew->model($this->slug());
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

    /**
     * Calls the  appropriate method of the controller.
     * 
     * This function can be overwritten to modify the behavior or the 
     * purpose of the parameters, for an example see the example Pages 
     * controller.
     *
     * @param Request $request The current HTTP request
     * @return array An associative array to pass to the view
     */
    public function __invoke(Request $request)
    {
        $action = $request->action();
        $args = $request->args();

        if (!method_exists($this, $this->action_prefix . $action)) {
            # If the $action method does not exist, show an error page
            throw new ControllerActionMissingException("Action {$this->action_prefix}{$action} for controller ". get_class($this) . " not found");
        }

        # Everything's clear pink
        $view_data = call_user_func_array([$this, $this->action_prefix . $action], $args);

        if (empty($view_data)) {
            $view_data = [];
        } elseif (!is_array($view_data)) {
            $view_data = compact('view_data');
        }

        return $view_data;
    }
}
