<?php

namespace pew\controller;

use pew\Pew;
use pew\libs\Str;
use pew\libs\Request;

use ReflectionClass;
use RuntimeException;

use pew\controller\exception\ActionMissingException;

/**
 * The basic controller class, with some common methods and fields.
 *
 * @package pew
 * @author ifcanduela <ifcanduela@gmail.com>
 */
class Controller
{
    /**
     * String prefixed to action names in this controller.
     *
     * @var string
     */
    protected $action_prefix = '';

    /**
     * Gets the URL slug corresponding to the controller name.
     *
     * @return string
     */
    public function slug()
    {
        $short_name = (new ReflectionClass($this))->getShortName();
        $slug = Str::underscores($short_name, true);

        return $slug->slug();
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
            $this->model = Pew::instance()->model($this->slug());
            return $this->model;
        } elseif (isSet($this->pew[$property])) {
            return $this->pew[$property];
        }

        $class_name = Str::camel_case($property);

        if ($obj = $this->pew->library($class_name)) {
            $this->pew[$property] = $obj;
            return $obj;
        }

        throw new RuntimeException("Property Controller::\$$property does not exist");
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
        $action = str_replace('-', '_', $request->action());
        $args = $request->args();

        if (!method_exists($this, $this->action_prefix . $action)) {
            # If the $action method does not exist, show an error page
            throw new ActionMissingException("Action {$this->action_prefix}{$action} for controller ". get_class($this) . " not found");
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

    /**
     * Prepares the controller before calling the action.
     *
     * @param Request $request
     * @return null
     */
    public function before_action(Request $request)
    {

    }

    /**
     * Modifies view data returned from the action.
     *
     * @param array $view_data
     * @return array
     */
    public function after_action(array $view_data) {
        return $view_data;
    }
}
