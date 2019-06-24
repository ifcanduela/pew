<?php

namespace pew;

use Monolog\Logger;
use pew\lib\Injector;
use pew\model\TableManager;
use pew\response\Response;
use pew\router\InvalidHttpMethod;
use pew\router\Route;
use pew\router\RouteNotFound;
use Psr\Container\ContainerInterface;
use Stringy\Stringy as S;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

/**
 * The App class is a request/response processor.
 *
 * Its purpose is to route the request into a response.
 */
class App implements ContainerInterface
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
            $guessedAppPath = $appFolder;
        } else {
            $guessedAppPath = getcwd() . "/{$appFolder}";
        }

        $appPath = realpath($guessedAppPath);

        if ($appPath === false) {
            throw new \InvalidArgumentException("The app path does not exist: {$guessedAppPath}");
        }

        $this->set("app_path", $appPath);
        $this->set("config_file_name", $configFileName);
        $this->set("app", $this);

        static::$instance = $this;

        # import app-defined configuration
        $this->loadAppConfig($configFileName);
        $this->loadAppBootstrap();

        # Initialize the database manager
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
     * @return void
     * @throws \Exception
     */
    public function run()
    {
        $errorHandler = $this->get("error_handler");
        $errorHandler->register();

        try {
            # Process the request
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
     * @throws \Exception
     */
    protected function handle()
    {
        $injector = $this->get("injector");
        $request = $this->get("request");
        # Add get and post parameters to the injection container
        $injector->appendContainer($request->request->all());
        $injector->appendContainer($request->query->all());

        $route = $this->get("route");
        # Add route parameters to the injection container
        $injector->appendContainer($route);

        App::log("Matched route " . $route->getPath());

        $result = $this->runBeforeMiddleware($route, $injector);

        if ($result instanceof Response) {
            App::log("Middleware returned response");
        } else {
            # Resolve the route to a callable or a controller class
            $handler = $this->resolveController($route);
            $actionName = $this->resolveAction($route);

            if (!$handler) {
                throw new \RuntimeException("No handler specified for route (" . $request->getPathInfo() . ")");
            }

            if (is_callable($handler)) {
                $result = $this->handleCallback($handler, $injector);
            } else {
                $result = $this->handleAction($handler, $actionName, $injector);
            }
        }

        # Process whatever the handler returned into a Response object
        $response = $this->transformActionResult($result);

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
        # Get the "before" middleware services for the route
        $middlewareClasses = $route->getBefore() ?: [];

        foreach ($middlewareClasses as $middlewareClass) {
            # Create an instance of the service
            $mw = $injector->createInstance($middlewareClass);
            $this->middleware[$middlewareClass] = $mw;
            $result = $injector->callMethod($mw, "before");

            if ($result instanceof Response) {
                # Short-circuit the request if any middleware returns a response
                return $result;
            }
        }
    }

    /**
     * Run configured middleware callbacks after the controller action.
     *
     * @param Route $route
     * @param SymfonyResponse $response
     * @param Injector $injector
     * @return Response
     */
    protected function runAfterMiddleware(Route $route, SymfonyResponse $response, Injector $injector)
    {
        # Get the "after" middleware services for the route
        $middlewareClasses = $route->getAfter() ?: [];

        foreach ($middlewareClasses as $middlewareClass) {
            # Check if the middleware was activated before calling the action
            if (array_key_exists($middlewareClass, $this->middleware)) {
                # Reuse the instance
                $mw = $this->middleware[$middlewareClass];
            } else {
                # Create a new instance
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
        # Create a basic controller as a host for the callback
        $controller = $injector->createinstance(Controller::class);

        # Call the handler using the basic controller as context
        return $injector->callFunction($handler, $controller);
    }

    /**
     * Process the request through a controller action.
     *
     * @param string $controllerClass
     * @param string $actionName
     * @param Injector $injector
     * @return mixed
     */
    protected function handleAction(string $controllerClass, string $actionName, Injector $injector)
    {
        # Guess the template path and filename
        $controllerPath = $this->getControllerPath($controllerClass, $this->get("controller_namespace"));
        $actionId = S::create($actionName)->underscored();
        $actionMethod = S::create($actionName)->camelize();
        $template = $controllerPath . DIRECTORY_SEPARATOR . $actionId;

        $this->set("controller_slug", basename($controllerPath));
        $this->set("action_slug", $actionId);

        App::log("Request handler is {$controllerPath}/{$actionMethod}");

        # Set up the template
        $view = $this->get("view");
        $view->template($template);
        $view->layout("default.layout");

        # Create the controller
        $controller = $injector->createInstance($controllerClass);
        $result = null;

        # Run the before-action function, if present
        if (method_exists($controller, "beforeAction")) {
            $result = $injector->callMethod($controller, "beforeAction");
        }

        # Run the action if `beforeAction` did not return a result
        if ($result === null) {
            $result = $injector->callMethod($controller, $actionMethod);
        }

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
        # If debug mode is on, let the error handler take care of the error
        if ($this->get("debug")) {
            throw $e;
        }

        $view = $this->get("view");
        $view->layout(false);
        $output = $view->render("errors/404", ["exception" => $e]);

        return new Response($output, 404);
    }

    /**
     * Get a controller from the route.
     *
     * @param Route $route
     * @return mixed|string
     */
    public function resolveController(Route $route)
    {
        # The handler can be a string like "controller@action" or a callback function
        $handler = $route->getHandler();

        if (is_string($handler)) {
            # Separate controller and action
            $handlerParts = explode("@", $handler);
            # Separate controller class and namespaces
            $controllerParts = preg_split("~[\\\/]~", $handlerParts[0]);
            # Get controller slug
            $controllerSlug = array_pop($controllerParts);
            # Turn the controller slug into a class name
            $controllerParts[] = S::create($controllerSlug)->upperCamelize();
            # Assemble the controller identifier
            $controllerId = join("\\", $controllerParts);
            # The namespace is the default controller namespace with an optional,
            # additional namespace set in the route
            $namespace = S::create($this->get("controller_namespace") . $route->getNamespace())->ensureLeft("\\")->ensureRight("\\");

            # Check if the controller class exists -- it may have an optional "Controller" suffix
            foreach ([$controllerId, $controllerId . "Controller"] as $c) {
                if (class_exists($namespace . $c)) {
                    # Return the FQCN of the controller
                    return $namespace . $c;
                }
            }

            throw new \RuntimeException("No controller found for handler `{$handler}`");
        }

        return $handler;
    }

    /**
     * Get an action name from the route.
     *
     * @param Route $route
     * @return string|null
     */
    public function resolveAction(Route $route)
    {
        $handler = $route->getHandler();

        if (is_callable($handler)) {
            return null;
        }

        if (is_string($handler)) {
            $parts = explode("@", $handler);

            if (isset($parts[1])) {
                return $parts[1];
            }
        }

        $actionSlug = $route->getParam("action", $this->get("default_action"));

        return S::create($actionSlug)->camelize();
    }

    /**
     * Find the filesystem path to the controller.
     *
     * @param string $controllerClass
     * @param string $baseNamespace
     * @return string
     */
    public function getControllerPath(string $controllerClass, string $baseNamespace)
    {
        # Get the namespace of the controller relative to the base controller namespace
        # by removing \app\controllers (by default) from the beginning
        $relativeNamespace = S::create($controllerClass)->removeLeft($baseNamespace);
        $parts = explode("\\", $relativeNamespace);
        # The last segment is the short class name
        $controllerClassName = array_pop($parts);
        # The other segments will be the path to the controller templates in the
        # `views` folder
        $controllerPath = implode(DIRECTORY_SEPARATOR, $parts);
        # Convert the short class name into a slug to use as folder name
        $controllerSlug = S::create($controllerClassName)->removeRight("Controller")->underscored();

        # Make the controller slug available for use elsewhere
        $this->set("controller_slug", $controllerSlug);

        return $controllerPath . DIRECTORY_SEPARATOR . $controllerSlug;
    }

    /**
     * Convert the result of an action into a Response object.
     *
     * @param mixed $actionResult
     * @return Response
     */
    protected function transformActionResult($actionResult)
    {
        $request = $this->get("request");
        $response = $this->get("response");

        # If $actionResult is false, return an empty response
        if ($actionResult === false) {
            return $response;
        }

        # If it's already a response, return it
        if ($actionResult instanceof Response) {
            return $actionResult;
        }

        # Check if the request is JSON and return an appropriate response
        if ($request->isJson()) {
            return new JsonResponse($actionResult);
        }

        # If the action result is a string, use as the content of the response
        if (is_string($actionResult)) {
            $response->setContent($actionResult);

            return $response;
        }

        # If the action result is not an array, make it into one
        if (!is_array($actionResult)) {
            $actionResult = ["data" => $actionResult];
        }

        # Use the action result to render the view
        $view = $this->get("view");
        $response = $view->render($actionResult);

        return $response;
    }

    /**
     * Get a value from the container.
     *
     * @param string $key
     * @return mixed
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function get($key)
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
    public function set($key, $value)
    {
        $this->container[$key] = $value;
    }

    /**
     * Check if a key exists in the container.
     *
     * @param string $key
     * @return bool
     */
    public function has($key)
    {
        return isset($this->container[$key]);
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
        $logger = static::$instance->get("app_log");
        $logger->log($level, $message);
    }
}
