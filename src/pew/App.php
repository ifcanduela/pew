<?php

namespace pew;

use Monolog\Logger;
use pew\lib\Injector;
use pew\model\TableManager;
use pew\router\InvalidHttpMethod;
use pew\router\Route;
use pew\router\RouteNotFound;
use Stringy\Stringy as Str;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * The App class is a request/response processor.
 *
 * Its purpose is to route the request into a response.
 */
class App
{
    /** @var \Pimple\Container */
    protected $container;

    /** @var array */
    protected $middleware = [];

    /** @var static */
    protected static $instance;

    /**
     * Bootstrap a web app.
     *
     * The App Folder provided must be relative to the current working directory, or
     * absolute. The Config File Name is a base name (e.g. `config`) located in the
     * `config` folder inside the App Folder (e.g. `app/config/config.php`).
     *
     * @param string $appFolder The path to the app folder
     * @param string $configFileName Base name of the file to use for configuration.
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     */
    public function __construct(string $appFolder, string $configFileName = "config")
    {
        $this->container = require __DIR__ . "/config/bootstrap.php";

        if (realpath($appFolder)) {
            $appPathPre = $appFolder;
        } else {
            $appPathPre = getcwd() . "/{$appFolder}";
        }

        $appPath = realpath($appPathPre);

        if ($appPath === false) {
            throw new \InvalidArgumentException("The app path does not exist: {$appPathPre}");
        }

        $this->set("app_path", $appPath);
        $this->set("config_file_name", $configFileName);

        # import app-defined configuration
        $this->loadAppConfig($configFileName);
        $this->loadAppBootstrap();

        static::$instance = $this;
        TableManager::instance($this->get("tableManager"));

        App::log("App path set to {$appPath}", Logger::INFO);
    }

    /**
     * Get the application instance.
     *
     * Will return null if the application has not been initialized.
     *
     * @return static|null
     */
    public static function instance()
    {
        return static::$instance;
    }

    /**
     * Import the application configuration.
     *
     * @param string $configFileName The file name, relative to the base path
     * @return bool TRUE when the file exists, FALSE otherwise
     * @throws \RuntimeException
     */
    protected function loadAppConfig(string $configFileName)
    {
        $appPath = $this->get("app_path");
        $configFolder = $this->get("config_folder");
        $filename = "{$appPath}/{$configFolder}/{$configFileName}.php";

        if (file_exists($filename)) {
            $appConfig = require $filename;

            if (!is_array($appConfig)) {
                throw new \RuntimeException("Configuration file {$filename} must return an array");
            }

            foreach ($appConfig as $key => $value) {
                $this->set($key, $value);
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
        $appPath = $this->get("app_path");
        $configFolder = $this->get("config_folder");
        $filename = "{$appPath}/{$configFolder}/bootstrap.php";

        if (file_exists($filename)) {
            require_once $filename;

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
        $errorHandler = $this->get("error_handler");
        $errorHandler->register();

        try {
            $response = $this->handle();
        } catch (RouteNotFound $e) {
            # Bad route
            $response = $this->handleError($e);
        } catch (InvalidHttpMethod $e) {
            # Bad method
            $response = $this->handleError($e);
        } catch (\Exception $e) {
            # Other exceptions
            $response = $this->handleError($e);
        }

        $response->send();
    }

    /**
     * Handle the current request.
     *
     * @return Response
     */
    protected function handle()
    {
        $injector = $this->get("injector");
        $request = $this->get("request");
        $route = $this->get("route");
        App::log("Matched route " . $route->getPath());

        $result = $this->runBeforeMiddleware($route, $injector);

        if ($result instanceof Response) {
            App::log("Middleware returned response");
        } else {
            $handler = $this->get("controller");

            if (!$handler) {
                throw new \RuntimeException("No handler specified for route (" . $request->getPathInfo() . ")");
            }

            if (is_callable($handler)) {
                $result = $this->handleCallback($handler, $injector);
            } else {
                $result = $this->handleAction($handler, $injector);
            }
        }

        $response = $this->get("response");
        $response = $this->transformActionResult($result, $response);

        try {
            $response = $this->runAfterMiddleware($route, $response, $injector);
        } catch (\Exception $e) {
            $response = $this->handleError($e);
        }

        return $response;
    }

    /**
     * Run configured middleware callbacks before the controller action.
     *
     * @param Route $route
     * @param Injector $injector
     * @return Response|null
     */
    protected function runBeforeMiddleware(Route $route, Injector $injector)
    {
        $middlewareClasses = $route->getBefore() ?: [];

        foreach ($middlewareClasses as $middlewareClass) {
            $mw = $injector->createInstance($middlewareClass);
            $this->middleware[$middlewareClass] = $mw;
            $result = $injector->callMethod($mw, "before");

            if ($result instanceof Response) {
                return $result->send();
            }
        }
    }

    /**
     * Run configured middleware callbacks after the controller action.
     *
     * @param Route $route
     * @param Response $response
     * @param Injector $injector
     * @return Response
     */
    protected function runAfterMiddleware(Route $route, Response $response, Injector $injector)
    {
        $middlewareClasses = $route->getAfter() ?: [];

        foreach ($middlewareClasses as $middlewareClass) {
            if (array_key_exists($middlewareClass, $this->middleware)) {
                $mw = $this->middleware[$middlewareClass];
            } else {
                $mw = $injector->createInstance($middlewareClass);
            }

            $newResponse = $injector->callMethod($mw, "after");

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
        App::log("Request handler is anonymous callback");
        $controller = $injector->createinstance(Controller::class);

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
        $controllerPath = $this->get("controller_path");
        $actionName = $this->get("action");

        App::log("Request handler is {$controllerPath}/{$actionName}");

        $view = $this->get("view");
        $view->template($controllerPath . "/" . $actionName);
        $view->layout("default.layout");

        $controller = $injector->createInstance($controllerClass);
        $result = null;

        if (method_exists($controller, "beforeAction")) {
            $result = $injector->callMethod($controller, "beforeAction");
        }

        if (!($result instanceof Response)) {
            $result = $injector->callMethod($controller, $actionName);
        }

        App::log("Request handler is {$handler}@{$actionName}");

        return $result;
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
        if ($this->get("debug")) {
            throw $e;
        }

        $view = $this->get("view");
        $view->layout(false);

        $output = $view->render("errors/404", ["exception" => $e]);

        return new Response($output, 404);
    }

    /**
     * Convert the result of an action into a Response object.
     *
     * @param mixed $actionResult
     * @param Response $response
     * @return Response
     */
    protected function transformActionResult($actionResult, Response $response)
    {
        # if $actionResult is false, return an empty response
        if ($actionResult === false) {
            return $response;
        }

        # if it's already a response, return it
        if ($actionResult instanceof Response) {
            return $actionResult;
        }

        # check if the request is JSON and return an appropriate response
        $request = $this->get("request");

        if ($request->isJson()) {
            return new JsonResponse($actionResult);
        }

        # if the action result is a string, use as the content of the response
        if (is_string($actionResult)) {
            $response->setContent($actionResult);

            return $response;
        }

        # if the action result is not an array, make it into one
        if (!is_array($actionResult)) {
            $actionResult = ["data" => $actionResult];
        }

        # use the action result to render the view
        $view = $this->get("view");
        $output = $view->render(null, $actionResult);
        $response->setContent($output);

        return $response;
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
     * @return void
     */
    public function set(string $key, $value)
    {
        $this->container[$key] = $value;
    }

    /**
     * Log a message to the application log file.
     *
     * The available log levels are constants of the Monolog\Logger class:
     * DEBUG, INFO, NOTICE, WARNING, ERROR, CRITICAL, ALERT and EMERGENCY.
     *
     * @param string $message
     * @param int $level
     * @return void
     */
    public static function log(string $message, $level = Logger::DEBUG)
    {
        /** @var Logger $logger */
        $logger = static::$instance->get("app_log");
        $logger->log($level, $message);
    }
}
