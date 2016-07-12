<?php

namespace pew;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

use Stringy\Stringy as Str;

/**
 * The App class is a simple interface between the front controller and the
 * rest of the controllers.
 *
 * @package pew
 * @author ifcanduela <ifcanduela@gmail.com>
 */
class App
{
    protected $container;

    public function __construct($app_folder = 'app', $config_file_name = 'config')
    {
        $this->container = require __DIR__ . '/config/bootstrap.php';

        $app_path_pre = getcwd() . DIRECTORY_SEPARATOR . $app_folder;
        $app_path = realpath($app_path_pre);

        if ($app_path === false) {
            throw new \InvalidArgumentException("The app path does not exist: {$app_path_pre}");
        }

        $this->container['app_path'] = $app_path;
        $this->container['config_folder'] = $config_file_name;


        # init the pew() helper
        pew(null, $this->container);

        # import app config and services
        $this->loadAppConfig("{$app_path}/config/{$config_file_name}.php");

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
                $this->container[$key] = $value;
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
        if (file_exists($this->container['app_path'] . '/config/bootstrap.php')) {
            require $this->container['app_path'] . '/config/bootstrap.php';
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
        $request = $this->container['request'];
        $injector = $this->container['injector'];
        $handler = $this->container['controller'];

        try {
            if (is_callable($handler)) {
                $response = $this->handleCallback($handler, $injector);
            } else {
                $response = $this->handleAction($handler, $injector);
            }

            $response = $this->transformResponse($response);
        } catch (\Exception $e) {
            if ($this->container['debug']) {
                throw $e;
            } else {
                $view = $this->container['view'];
                $view->template('errors/404');
                $view->layout(false);
                $output = $view->render(['exception' => $e]);
                $response = new Response($output);
            }
        }

        $response->send();
    }

    protected function handleCallback(callable $handler, Injector $injector)
    {
        $injector = $this->container['injector'];

        $response = $injector->callFunction($handler);
    }

    protected function handleAction(string $handler, Injector $injector)
    {
        $controllerClass = $handler;
        $controllerSlug = Str::create(basename($controllerClass))->removeRight('Controller')->underscored()->slugify();
        $route = $this->container['route'];
        $actionName = $this->container['action'];

        $view = $this->container['view'];

        $view->template($controllerSlug . '/' . $actionName);
        $view->layout('default.layout');

        foreach ($route->getConditions() as $callback) {
            $injector->callFunction($callback);
        }

        $controller = $injector->createinstance($controllerClass);

        $response = null;

        if (method_exists($controller, 'beforeAction')) {
            $response = $injector->callMethod($controller, 'beforeAction');
        }

        if (!is_a($response, Response::class)) {
            $response = $injector->callMethod($controller, $actionName);
        }

        return $response;
    }

    public function transformResponse($response)
    {
        if (is_a($response, Response::class)) {
            return $response;
        }

        $request = $this->container['request'];
        
        if ($request->isJson()) {
            return new JsonResponse($response);
        }

        if (is_object($response) || is_array($response)) {
            $view = $this->container['view'];
            $output = $view->render($response);
        } else {
            $output = $reponse;
        }

        return new Response($output);
    }

    public function get($key)
    {
        return $this->container[$key];
    }
}
