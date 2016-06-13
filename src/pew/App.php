<?php

namespace pew;

use pew\Pew;
use pew\View;
use pew\libs\Env;
use pew\route\Router;
use pew\request\Request;
use pew\request\exception\ControllerMissingException;
use pew\request\exception\ActionMissingException;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

use Stringy\StaticStringy as Str;

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
    protected $app;

    public function __construct($app_folder = 'app', $config = 'config')
    {
        $this->app = require __DIR__ . '/config/bootstrap.php';
        $this->app['app_namespace'] = "\\{$app_folder}\\";
        $this->app['app_path'] = $app_path = dirname(getcwd()) . DIRECTORY_SEPARATOR . $app_folder;
        $this->app['config_folder'] = $config;

        # init the pew() helper
        pew(null, $this->app);

        $app_folder = $this->pew['root_folder'] . '/' . $app_folder;

        # import app config and services
        $this->loadAppConfig("{$app_path}/config/{$config}.php");

        $this->loadAppBootstrap();
    }

    /**
     * Import the application configuration.
     * 
     * @param string $config_filename The file name, relative to the base path
     * @return null
     */
    protected function loadAppConfig($config_filename)
    {
        if (file_exists($config_filename)) {
            $app_config = require $config_filename;

            if (!is_array($app_config)) {
                throw new \RuntimeException("Configuration file {$config_filename} does not return an array");
            }

            foreach ($app_config as $key => $value) {
                $this->app[$key] = $value;
            }
        }
    }

    /**
     * Load the user bootstrap file.
     *
     * @return null
     */
    protected function loadAppBootstrap()
    {
        # load app/config/bootstrap.php
        if (file_exists($this->app['app_path'] . '/config/bootstrap.php')) {
            require $this->app['app_path'] . '/config/bootstrap.php';
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
        $request = $this->app['request'];
        $controllerClass = $this->app['controller'];
        $controllerSlug = Str::slugify(basename($controllerClass));
        $actionName = $this->app['action'];
        $injector = $this->app['injector'];

        $view = $this->app['view'];
        
        $view->template($controllerSlug . '/' . $actionName);
        $view->layout('default.layout');

        $controller = $injector->createinstance($controllerClass);

        $response = null;

        if (method_exists($controller, 'beforeAction')) {
            $response = $injector->callMethod($controller, 'beforeAction');
        }

        if (!is_a($response, Response::class)) {
            $response = $injector->callMethod($controller, $actionName);

            if ($response === false) {
                die();
            } elseif (!is_object($response) || !is_a($response, Response::class)) {
                if ($request->isJson()) {
                    $response = new JsonResponse($response);
                } else {
                    $output = $view->render($response);
                    $response = new Response($output);
                }
            }
        }

        $response->send();
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
