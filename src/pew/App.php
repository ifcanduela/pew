<?php

namespace pew;

use Monolog\Logger;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Stringy\Stringy as Str;
use pew\libs\Injector;
use pew\router\Route;

/**
 * The App class is a request/response processor.
 *
 * Its purpose is to route the request into a response.
 */
class App
{
    /** @var \Pimple\Container */
    private $container;

    /** @var array */
    protected $middleware = [];

    /** @var static */
    private static $instance;

    /**
     * Bootstrap a web app.
     *
     * The App Folder provided must be relative to the current working directory, or
     * absolute. The Config File Name is a base name (e.g. `config`) located in the
     * `config` folder inside the App Folder (e.g. `app/config/config.php`).
     *
     * @param string $app_folder The path to the app folder
     * @param string $config_file_name Base name of the file to use for configuration.
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     */
    public function __construct($app_folder, $config_file_name = 'config')
    {
        $this->container = require __DIR__ . '/config/bootstrap.php';

        if (realpath($app_folder)) {
            $app_path_pre = $app_folder;
        } else {
            $app_path_pre = getcwd() . DIRECTORY_SEPARATOR . $app_folder;
        }

        $app_path = realpath($app_path_pre);

        if ($app_path === false) {
            throw new \InvalidArgumentException("The app path does not exist: {$app_path_pre}");
        }

		$config_folder = $this->container['config_folder'];
        $this->container['app_path'] = $app_path;
        $this->container['config_file_name'] = $config_file_name;

        # init the pew() helper
        pew(null, $this->container);

        # import app config and services
        $this->loadAppConfig("{$app_path}/{$config_folder}/{$config_file_name}.php");
        $this->loadAppBootstrap();

        static::$instance = $this;

        App::log("App path set to {$app_path}", Logger::INFO);
    }

    /**
     * Get the application instance.
     *
     * @return static
     */
    public static function instance()
    {
        return static::$instance;
    }

    /**
     * Import the application configuration.
     *
     * @param string $config_filename The file name, relative to the base path
     * @return bool TRUE when the file exists, FALSE otherwise
     * @throws \RuntimeException
     */
    protected function loadAppConfig($config_filename)
    {
        if (file_exists($config_filename)) {
            $app_config = require $config_filename;

            if (!is_array($app_config)) {
                throw new \RuntimeException("Configuration file {$config_filename} must return an array");
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
            require_once $this->container['app_path'] . '/config/bootstrap.php';

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
     * @throws \Exception
     */
    public function run()
    {
        $result = false;
        $injector = $this->container['injector'];

        try {
            $route = $this->container['route'];

            $response = $this->runBeforeMiddlewares($route, $injector);

            if ($response instanceof Response) {
                $result = $response;
            } else {
                $handler = $this->container['controller'];

                if (!$handler) {
                    throw new \RuntimeException("No handler specified for route (" . $request->getPathInfo() . ")");
                }

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

    protected function runBeforeMiddlewares(Route $route, Injector $injector)
    {
        $middlewareClasses = $route->getBefore() ?: [];

        foreach ($middlewareClasses as $middlewareClass) {
            $mw = $injector->createInstance($middlewareClass);
            $this->middleware[$middlewareClass] = $mw;
            $result = $injector->callMethod($mw, 'before');

            if ($result instanceof Response) {
                return $result->send();
            }
        }
    }

    protected function runAfterMiddleware(Route $route, Response $response, Injector $injector)
    {
        $middlewareClasses = $route->getAfter() ?: [];

        foreach ($middlewareClasses as $middlewareClass) {
            if (array_key_exists($middlewareClass, $this->middleware)) {
                $mw = $this->middleware[$middlewareClass];
            } else {
                $mw = $injector->createInstance($middlewareClass);
            }
            $newResponse = $injector->callMethod($mw, 'after');

            if ($newResponse instanceof Response) {
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
        $controller = $injector->createInstance(Controller::class);

        return $injector->callFunction($handler, $controller);
    }

    /**
     * Process the request through a controller action.
     *
     * @param string $handler
     * @param Injector $injector
     * @return mixed
     * @throws \InvalidArgumentException
     */
    protected function handleAction(string $handler, Injector $injector)
    {
        $controllerClass = $handler;
        $controllerSlug = Str::create(basename($controllerClass))->removeRight('Controller')->underscored()->slugify();
        $actionName = $this->container['action'];

        $view = $this->container['view'];

        $view->template($controllerSlug . '/' . $actionName);
        $view->layout('default.layout');

        $controller = $injector->createInstance($controllerClass);

        $response = null;

        if (method_exists($controller, 'beforeAction')) {
            $response = $injector->callMethod($controller, 'beforeAction');
        }

        if (!($response instanceof Response)) {
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
    protected function handleError(\Exception $e)
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
     * @throws \InvalidArgumentException
     */
    protected function transformActionResult($actionResult)
    {
        # if $actionResult is false, return an empty response
        if ($actionResult === false) {
            return new Response();
        }

        # if it's already a response, return it
        if ($actionResult instanceof Response) {
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
    public function get(string $key)
    {
        return $this->container[$key];
    }

    /**
     * Set a value in the container.
     *
     * @param string $key
     * @param mixed $value
     */
    public function set(string $key, $value)
    {
        $this->container[$key] = $value;
    }

    /**
     * Log a message to the application log file.
     *
     * @param string $message
     * @param int $level
     */
    public static function log($message, $level = Logger::DEBUG)
    {
        /** @var Logger $logger */
        $logger = static::$instance->container['app_log'];
        $logger->log($level, $message);
    }
}
