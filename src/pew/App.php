<?php

namespace pew;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Stringy\Stringy as Str;
use pew\libs\Injector;
use pew\router\Route;
use pew\request\Request;

/**
 * The App class is a request/response processor.
 *
 * Its purpose is to route the request into a response.
 *
 * @author ifcanduela <ifcanduela@gmail.com>
 */
class App
{
    /** @var \Pimple\Container */
    protected $container;

    /**
     * Bootstrap a web app.
     *
     * The App Folder provided must be relative to the current working directory, or
     * absolute. The Config File Name is a base name (e.g. `config`) located in the
     * `config` folder inside the App Folder (e.g. `app/config/config.php`).
     *
     * @param string $app_folder The path to the app folder
     * @param string $config_file_name Base name of the file to use for configuration.
     */
    public function __construct($app_folder, $config_file_name = 'config')
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
     * @return bool TRUE when the file exists, FALSE otherwise
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

            return true;
        }

        return false;
    }

    /**
     * Load the user bootstrap file.
     *
     * @return bool TRUE when the file exists, FALSE otherwise
     */
    protected function loadAppBootstrap()
    {
        # load app/config/bootstrap.php
        if (file_exists($this->container['app_path'] . '/config/bootstrap.php')) {
            require $this->container['app_path'] . '/config/bootstrap.php';

            return true;
        }

        return false;
    }

    /**
     * Application entry point, manages controllers, actions and views.
     *
     * This function is responsible of creating an instance of the appropriate
     * Controller class and calling its action() method, which will handle
     * the controller call.
     *
     * @return null
     */
    public function run()
    {
        $result = false;
        $injector = $this->container['injector'];

        try {
            $route = $this->container['route'];
            $request = $this->container['request'];

            $response = $this->runBeforeMiddlewares($route, $request, $injector);

            if (is_a($response, Response::class)) {
                $result = $response;
            } else {
            $handler = $this->container['controller'];

            if (is_callable($handler)) {
                $result = $this->handleCallback($handler, $injector);
            } else {
                $result = $this->handleAction($handler, $injector);
            }
            }
        } catch (\Exception $e) {
            $result = $this->handleError($e);
        }

        $response = $this->transformActionResult($result);
        $this->container['response'] = $response;

        try {
            $response = $this->runAfterMiddleware($route, $response, $injector);
        } catch (\Exception $e) {
            $response = $this->handleError($e);
        }

        $response->send();
    }

    protected function runBeforeMiddlewares(Route $route, Request $request, Injector $injector)
    {
        $middlewareClasses = $route->getBefore() ?: [];

        foreach ($middlewareClasses as $middlewareClass) {
            $mw = $injector->createInstance($middlewareClass);
            $result = $injector->callMethod($mw, 'before');

            if (is_a($result, Response::class)) {
                return $result->send();
            }
        }
    }

    protected function runAfterMiddleware(Route $route, Response $response, Injector $injector)
    {
        $middlewareClasses = $route->getAfter() ?: [];

        foreach ($middlewareClasses as $middlewareClass) {
            $mw = $injector->createInstance($middlewareClass);
            $newResponse = $injector->callMethod($mw, 'after');

            if (is_a($newResponse, Response::class)) {
                $response = $newResponse;
            }
        }

        return $response;
    }

    /**
     * Process the request through a callback.
     *
     * @param callable $handler
     * @param Injector $injector
     * @return mixed
     */
    protected function handleCallback(callable $handler, Injector $injector)
    {
        $controller = $injector->createinstance(Controller::class);

        return $injector->callFunction($handler, $controller);
    }

    /**
     * Process the request through a controller action.
     *
     * @param string $handler
     * @param Injector $injector
     * @return mixed
     */
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

    /**
     * Generate an error response.
     *
     * @param \Exception $e
     * @return Response
     * @throws \Exception
     */
    protected function handleError(\Exception $e): Response
    {
        if ($this->container['debug']) {
            throw $e;
        }

        $view = $this->container['view'];
        $view->template('errors/404');
        $view->layout(false);

        $output = $view->render(['exception' => $e]);

        return new Response($output, 404);
    }

    /**
     * Convert the result of an action into a Response object.
     *
     * @param mixed $actionResult
     * @return Response
     */
    protected function transformActionResult($actionResult): Response
    {
        # if $actionResult is false, return an empty response
        if ($actionResult === false) {
            return new Response();
        }

        # if it's already a response, return it
        if (is_a($actionResult, Response::class)) {
            return $actionResult;
        }

        # check if the request is JSON and return an appropriate response
        $request = $this->container['request'];
        if ($request->isJson()) {
            return new JsonResponse($actionResult);
        }

        # if the action result is a string, use as the content of the response
        if (is_string($actionResult)) {
            return new Response($actionResult);
        }

        # if the action result is not an array, make it into one
        if (!is_array($actionResult)) {
            $actionResult = ['data' => $actionResult];
        }

        # use the action result to render the view
        $view = $this->container['view'];
        $output = $view->render($actionResult);

        return new Response($output);
    }

    /**
     * Get a value from the container.
     *
     * @param string $key
     * @return mixed
     */
    public function get($key)
    {
        return $this->container[$key];
    }
}
