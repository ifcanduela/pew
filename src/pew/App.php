<?php

namespace pew;

use \pew\Pew;
use \pew\libs\Env;
use \pew\libs\Router;
use \pew\libs\Request;
use \pew\ControllerActionMissingException;
/**
 * The App class is a simple interface between the front controller and the
 * rest of the controllers.
 * 
 * @package pew
 * @author ifcanduela <ifcanduela@gmail.com>
 */
class App
{
    protected $pew;

    public function __construct($app_folder = 'app', $config = 'config')
    {
        $this->pew = Pew::instance();

        # merge user config with Pew config
        $this->setup("/{$app_folder}/config/{$config}.php");
        
        # add application namespace and path
        $app_folder_name = trim(basename($app_folder));
        $this->pew['app_namespace'] = '\\' . $app_folder_name;
        $this->pew['app_folder'] = realpath($app_folder);
        $this->pew['app_config'] = $config;

        $this->pew['app'] = $this;

        # user bootstrap
        $this->bootstrap();
    }

    /**
     * Import the application configuration.
     * 
     * @param string $filename The file name, relative to the base path
     * @return array
     */
    protected function setup($filename)
    {
        $config_filename = getcwd() . '/' . trim($filename, '/\\');
        
        if (file_exists($config_filename)) {
            # load {$app}/config/{$config}.php
            $app_config = require $config_filename;

            if (!is_array($app_config)) {
                throw new \RuntimeException("Configuration file {$config_filename} does not return an array");
            }

            $this->pew->import($app_config);
        }
    }

    /**
     * Load the user bootstrap file.
     */
    protected function bootstrap()
    {
        # load app/config/bootstrap.php
        if (file_exists($this->pew['app_folder'] . '/config/bootstrap.php')) {
            require $this->pew['app_folder'] . '/config/bootstrap.php';
        }
    }

    /**
     * Application entry point, manages controllers, actions and views.
     *
     * This function is responsible of creating an instance of the appropriate
     * Controller class and calling its action() method, which will handle
     * the controller call.
     */
    public function run()
    {
        # fetch the request object
        $request = $this->pew['request'];
        
        # instantiate and configure the view
        $view = $this->pew['view'];
        $view->template($request->controller() . '/' . $request->action());
        $view->layout($this->pew['default_layout']);
        
        # instantiate the controller
        $controller = $this->pew->controller($request->controller());
        
        $skip_action = false;
        $view_data = [];
        
        # check controller instantiation
        if (!is_object($controller)) {
            if ($view->exists()) {
                $view->title($request->action());
                $skip_action = true;
            } else {
                if ($this->pew->debug) {
                    throw new \Exception('Action ' . $request->action() . ' for controller ' . $request->controller() . ' not found');
                } else {
                    $error = new controllers\Error;
                    $view_data = $error->show_404('Page not found', 'The page <code>' . here() . ' does not exist</code>');
                }
            }
        } else {
            # call the before_action method if it's defined
            if (method_exists($controller, 'before_action')) {
                $controller->before_action();
            }

            # call the action method and let the controller decide what to do
            try {
                if (!$skip_action) {
                    $view_data = $controller->__call($request->action(), $request->args());
                }
            } catch (ControllerActionMissingException $e) {
                if ($this->pew->debug) {
                    throw $e;
                } else {
                    $error = new controllers\Error;
                    $view_data = $error->show_404('Page not found', 'The page <code>' . here() . ' does not exist</code>');
                }
            }

            # call the after_action method if it's defined
            if (method_exists($controller, 'after_action')) {
                $controller->after_action();
            }
        }

        # render the view, if not prevented
        if ($view->render) {
            switch ($this->get_response_type($request)) {
                case 'json':
                    $page = json_encode($view_data);
                    header('Content-type: application/json');
                    break;
                case 'xml':
                    throw new \Exception('XML rendering is not yet implemented.');
                    break;
                default:
                    $page = $view->render($view_data);
                    break;
            }

            echo $page;
        }
    }

    /**
     * Get response type for the current request.
     * 
     * @param \pew\libs\Request $request
     * @return string One of 'html', 'json'or 'xml'
     */
    public function get_response_type($request)
    {
        $response_type = 'html';

        if ($this->pew['autodetect_ajax'] && $this->pew['request_is_ajax']) {
            $response_type = 'json';
        } else {
            $response_type = $request->response_type();
        }

        return $response_type;
    }
}
