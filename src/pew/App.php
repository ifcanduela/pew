<?php

namespace pew;

use pew\Pew;
use pew\View;
use pew\libs\Env;
use pew\route\Router;
use pew\request\Request;
use pew\request\exception\ControllerMissingException;
use pew\request\exception\ActionMissingException;

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

        $app_folder = $this->pew['root_folder'] . '/' . $app_folder;

        # import app config and services
        $this->import_config("{$app_folder}/config/{$config}.php");
        $this->import_services("{$app_folder}/config/services.php");
        
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
     * @param string $config_filename The file name, relative to the base path
     * @return null
     */
    protected function import_config($config_filename)
    {
        if (file_exists($config_filename)) {
            $app_config = require $config_filename;

            if (!is_array($app_config)) {
                throw new \RuntimeException("Configuration file {$config_filename} does not return an array");
            }
            
            $this->pew->import($app_config);
        }
    }

    /**
     * Import the application services definitions.
     * 
     * @param string $services_filename The file name, relative to the base path
     * @return null
     */
    public function import_services($services_filename)
    {
        if (file_exists($services_filename)) {
            $services = require $services_filename;

            if (!is_array($services)) {
                throw new \RuntimeException("Services file {$services_filename} does not return an array");
            }

            foreach ($services as $key => $factory) {
                $this->pew->register($key, $factory);
            }
        }
    }

    /**
     * Load the user bootstrap file.
     *
     * @return null
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
        $skip_action = false;
        $view_data = [];

        $request = $this->pew['request'];
        $response = false;

        try {
            if (!$request->has_route()) {
                throw new \pew\router\exception\RouteNotFoundException('No route found for '. $request->path());
            }

            if (is_callable($request->is_callable())) {
                $view_data = $this->handle_callable($request->destination());
            } else {
                # instantiate and configure the view
                $view = $this->pew['view'];
                $view->template($request->controller() . '/' . $request->action());
                $view->layout($this->pew['default_layout']);
                $view->title(ucfirst($request->action()) . ' - ' . ucfirst($request->controller()) . ' - ' . $this->pew['app_title']);
                
                $view_data = $this->handle_controller($request->controller(), $request->action());
            }
            
            if (false !== $view_data) {
                $response = $this->respond($request, $view, $view_data);
            }
        } catch (\Exception $exception) {
            $view = $this->pew->view;
            $view->layout('error.layout');
            
            if ($this->pew['debug']) {
                header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error', true, 500);

                $view->template('error/error');
                $view->title('Application Error (' . get_class($exception) . ')');
            } else {
                header($_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found', true, 404);
                $view->template('error/404');
                $view->title('Page not found');
            }

            $response = $this->respond($request, $view, ['exception' => $exception]);
        }

        if ($response) {
            echo $response;
        }
    }

    protected function handle_callable($callable)
    {
        $parameters = $this->pew->resolve_call(new \ReflectioNFunction($callable));

        return call_user_func_array($callable, $parameters);
    }

    public function handle_controller($controller_slug, $action_slug)
    {
        $view_data = [];
        $skip_action = false;
        $controller = $this->pew->controller($controller_slug);

        # check controller instantiation
        if (!$controller) {
            if ($this->pew->view->exists()) {
                $skip_action = true;
            } else {
                throw new ControllerMissingException("Controller " . $controller_slug . " does not exist.");
            }
        }

        if (method_exists($controller, 'before_action')) {
            $controller->before_action($this->pew->request);
        }

        # call the action method and let the controller decide what to do
        if (!$skip_action) {
            try {
                $parameters = $this->pew->resolve_call(new \ReflectionMethod(get_class($controller), $action_slug));
                $view_data = call_user_func_array([$controller, $action_slug], $parameters);
            } catch (\ReflectionException $e) {
                $view_data = $controller($this->pew->request);
            }
        }

        if ($view_data !== false) {
            if (method_exists($controller, 'after_action')) {
                $view_data = $controller->after_action($view_data);
            }
        }

        return $view_data;
    }

    public function respond(Request $request, View $view, $view_data)
    {
        if ($view->render && $view_data !== false) {
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

            return $page;
        }

        return false;
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
