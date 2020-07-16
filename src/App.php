<?php declare(strict_types=1);

namespace pew;

use Exception;
use ifcanduela\events\CanEmitEvents;
use ifcanduela\events\CanListenToEvents;
use InvalidArgumentException;
use Monolog\Logger;
use pew\di\Container;
use pew\di\Injector;
use pew\model\TableManager;
use pew\request\ActionResolver;
use pew\response\HtmlResponse;
use pew\response\HttpException;
use pew\response\JsonResponse;
use pew\response\Response;
use pew\router\Route;
use ReflectionException;
use RuntimeException;
use Stringy\Stringy as S;

/**
 * The App class is a request/response processor.
 *
 * Its purpose is to route the request into a response.
 */
class App
{
    use CanEmitEvents;
    use CanListenToEvents;

    /** @var array */
    protected $middleware = [];

    /** @var Container */
    protected $container;

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
     * @throws RuntimeException
     * @throws InvalidArgumentException
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
            throw new InvalidArgumentException("The app path does not exist: `{$guessedAppPath}`");
        }

        $this->container->set("app_path", $appPath);
        $this->container->set("config_file_name", $configFileName);
        $this->container->set("app", $this);

        static::$instance = $this;

        $this->emit("pew.init");

        # Import app-defined configuration
        $this->loadAppConfig($configFileName);
        $this->loadAppBootstrap();

        # Initialize the database manager
        TableManager::instance($this->container->get("tableManager"));

        $this->emit("app.init");

        App::log("App path set to {$appPath}", Logger::INFO);
    }

    /**
     * Get a value from the container.
     *
     * @param string $key
     * @return mixed
     */
    public function get(string $key)
    {
        return $this->container->get($key);
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
        $this->container->set($key, $value);
    }

    /**
     * Check if a value is present in the container.
     *
     * @param string $key
     * @return boolean
     */
    public function has(string $key)
    {
        return $this->container->has($key);
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
     * @throws RuntimeException
     */
    protected function loadAppConfig(string $configFileName)
    {
        $appPath = $this->container->get("app_path");
        $configFolder = $this->container->get("config_folder");
        $filename = "{$appPath}/{$configFolder}/{$configFileName}.php";

        return $this->container->loadFile($filename);
    }

    /**
     * Load the user bootstrap file.
     *
     * @return bool TRUE when the file exists, FALSE otherwise
     */
    protected function loadAppBootstrap()
    {
        $appPath = $this->container->get("app_path");
        $configFolder = $this->container->get("config_folder");
        $filename = "{$appPath}/{$configFolder}/bootstrap.php";

        if (is_readable($filename)) {
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
     * @throws Exception
     */
    public function run()
    {
        $errorHandler = $this->container->get("error_handler");
        $errorHandler->register();

        $this->emit("timer.start", ["app.run", "Application run"]);

        try {
            # Process the request
            $response = $this->handle();
        } catch (Exception $e) {
            $response = $this->handleError($e);
        }

        $this->emit("timer.stop", ["app.run"]);

        $response->send();
    }

    /**
     * Handle the current request.
     *
     * @return Response
     * @throws Exception
     */
    protected function handle()
    {
        $injector = $this->container->get("injector");
        $request = $this->container->get("request");
        # Add get and post parameters to the injection container
        $injector->appendContainer($request->request->all());
        $injector->appendContainer($request->query->all());

        $route = $this->container->get("route");
        # Add route parameters to the injection container
        $injector->appendContainer($route);

        App::log("Matched route " . $route->getPath());

        $result = $this->runBeforeMiddleware($route, $injector);

        if ($result instanceof Response) {
            App::log("Middleware returned response");
        } else {
            # Resolve the route to a callable or a controller class
            $resolver = new ActionResolver($route);
            $handler = $resolver->getController($this->container->get("controller_namespace"));
            $this->emit("request.handler", $handler);
            $actionName = $resolver->getAction($this->container->get("default_action"));
            $this->emit("request.actionName", $actionName);

            if (!$handler) {
                throw new RuntimeException("No handler specified for route `" . $request->getPathInfo() . "`");
            }

            $this->emit("timer.start", ["app.action", "Handle action"]);

            if (is_callable($handler)) {
                $result = $this->handleCallback($handler, $injector);
            } else {
                $result = $this->handleAction($handler, $actionName, $injector);
            }

            $this->emit("request.result", $result);
            $this->emit("timer.stop", ["app.action"]);
        }

        # Process whatever the handler returned into a Response object
        $response = $this->transformActionResult($result);
        $this->emit("response.start", $response);

        try {
            $response = $this->runAfterMiddleware($route, $response, $injector);
        } catch (Exception $e) {
            $response = $this->handleError($e);
        }

        $this->emit("response.end", $response);

        return $response;
    }

    /**
     * Run configured middleware callbacks before the controller action.
     *
     * @param Route $route
     * @param Injector $injector
     * @return Response|null
     * @throws ReflectionException
     * @throws di\KeyNotFoundException
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

        return null;
    }

    /**
     * Run configured middleware callbacks after the controller action.
     *
     * @param Route $route
     * @param Response $response
     * @param Injector $injector
     * @return Response
     * @throws ReflectionException
     * @throws di\KeyNotFoundException
     */
    protected function runAfterMiddleware(Route $route, Response $response, Injector $injector)
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
     * @throws ReflectionException
     * @throws di\KeyNotFoundException
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
     * @throws ReflectionException
     * @throws di\KeyNotFoundException
     */
    protected function handleAction(string $controllerClass, string $actionName, Injector $injector)
    {
        # Guess the template path and filename
        $controllerPath = $this->getControllerPath($controllerClass, $this->container->get("controller_namespace"));
        $actionId = (string) S::create($actionName)->underscored();
        $actionMethod = (string) S::create($actionName)->camelize();
        $template = $controllerPath . DIRECTORY_SEPARATOR . $actionId;

        $this->container->set("controller_slug", basename($controllerPath));
        $this->container->set("action_slug", $actionId);

        App::log("Request handler is {$controllerPath}/{$actionMethod}");

        # Set up the template
        $view = $this->container->get("view");
        $view->template($template);

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
     * @param Exception $e
     * @return Response
     * @throws Exception
     */
    protected function handleError(Exception $e)
    {
        # If debug mode is on, let the error handler take care of it
        if ($this->container->get("debug")) {
            throw $e;
        }

        $errorCode = 404;

        if ($e instanceof HttpException) {
            $errorCode = $e->getCode();
        }

        $view = $this->container->get("view");
        $view->layout(false);
        $view->template("errors/view");
        $view->set("exception", $e);

        if ($view->exists("errors/{$errorCode}")) {
            $view->template("errors/{$errorCode}");
        }

        $response = new HtmlResponse($view);
        $response->code($errorCode);

        return $response;
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
        $relativeNamespace = (string) S::create($controllerClass)->removeLeft($baseNamespace);
        $parts = explode("\\", $relativeNamespace);
        # The last segment is the short class name
        $controllerClassName = array_pop($parts);
        # The other segments will be the path to the controller templates in the
        # `views` folder
        $controllerPath = implode(DIRECTORY_SEPARATOR, array_filter($parts));
        # Convert the short class name into a slug to use as folder name
        $controllerSlug = S::create($controllerClassName)->removeRight("Controller")->underscored();

        # Make the controller slug available for use elsewhere
        $this->container->set("controller_slug", $controllerSlug);

        return implode(DIRECTORY_SEPARATOR, array_filter([$controllerPath, $controllerSlug]));
    }

    /**
     * Convert the result of an action into a Response object.
     *
     * @param mixed $actionResult
     * @return Response
     */
    protected function transformActionResult($actionResult)
    {
        $request = $this->container->get("request");
        $response = $this->container->get("response")->getResponse();

        # If $actionResult is false, return an empty response
        if ($actionResult === false) {
            return new Response($response);
        }

        # If it's already a response, return it
        if ($actionResult instanceof Response) {
            return $actionResult;
        }

        # Check if the request is JSON and return an appropriate response
        if ($request->isJson()) {
            return new JsonResponse($actionResult, $response);
        }

        # If the action result is a string, use as the content of the response
        if (is_string($actionResult)) {
            $response->setContent($actionResult);
            return new Response($response);
        }

        # Use the action result to render the view
        $view = $this->container->get("view");
        $view->setData($actionResult ?? []);

        return new HtmlResponse($view, $response);
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
        $logger = static::$instance->container->get("app_log");
        $logger->log($level, $message);
    }
}
