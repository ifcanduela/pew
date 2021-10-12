<?php declare(strict_types=1);

namespace pew;

use Closure;
use Exception;
use InvalidArgumentException;
use ReflectionException;
use RuntimeException;
use Throwable;

use ifcanduela\events\CanEmitEvents;
use ifcanduela\events\CanListenToEvents;
use ifcanduela\router\Router;
use ifcanduela\router\Route;
use Monolog\Logger;
use pew\di\Container;
use pew\di\Injector;
use pew\model\TableManager;
use pew\request\ActionResolver;
use pew\request\Request;
use pew\response\HttpException;
use pew\response\JsonResponse;
use pew\response\Response;

use function pew\str;

/**
 * The App class is a request/response processor.
 *
 * Its purpose is to route the request into a response.
 */
class App
{
    use CanEmitEvents, CanListenToEvents;

    protected array $middleware = [];

    protected Container $container;

    protected static App $instance;

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
        $this->initFrameworkContainer();

        $this->emit("pew.init");

        $this->initApplication($appFolder, $configFileName);

        # Initialize the database manager
        if ($this->get("db_config")) {
            TableManager::instance($this->container->get("tableManager"));
        }

        $this->emit("app.init");

        static::log("App path set to {$this->container->get("app_path")}", Logger::INFO);
    }

    /**
     * Get the application instance.
     *
     * Will return null if the application has not been initialized.
     *
     * @return static|null
     */
    public static function instance(): ?App
    {
        return static::$instance;
    }

    /**
     * Populate the framework container with default values.
     *
     * @return void
     */
    protected function initFrameworkContainer()
    {
        $containerFilename = __DIR__ . "/config/bootstrap.php";

        if (file_exists($containerFilename)) {
            $this->container = require $containerFilename;
        }

        static::$instance = $this;
    }

    /**
     * Initialize the application container and load app settings.
     *
     * @param string $appFolder
     * @param string $configFileName
     * @return void
     */
    protected function initApplication(string $appFolder, string $configFileName = "config")
    {
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
        $this->container->set("app", $this);

        # Import app-defined configuration
        $this->loadAppConfig($configFileName);
        $this->loadAppBootstrap();
    }

    /**
     * Import the application configuration.
     *
     * @param string $configFileName The file name, relative to the base path
     * @return bool `true` when the file exists, `false` otherwise
     * @throws RuntimeException
     */
    protected function loadAppConfig(string $configFileName): bool
    {
        $this->container->set("config_file_name", $configFileName);

        $appPath = $this->container->get("app_path");
        $configFolder = $this->container->get("config_folder");
        $filename = "{$appPath}/{$configFolder}/{$configFileName}.php";

        return $this->container->loadFile($filename);
    }

    /**
     * Load the user bootstrap file.
     *
     * @return bool `true` when the file exists, `false` otherwise
     */
    protected function loadAppBootstrap(): bool
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
     * @return bool
     */
    public function has(string $key): bool
    {
        return $this->container->has($key);
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
        $request = $this->container->get("request");

        try {
            # Process the request
            $response = $this->handle($request);
        } catch (Exception $e) {
            $response = $this->handleError($e);
        }

        $this->emit("timer.stop", ["app.run"]);

        $response->send();
    }

    /**
     * Handle the current request.
     *
     * @param Request $request
     * @return Response
     * @throws Exception
     */
    protected function handle(Request $request): Response
    {
        $injector = $this->container->get(Injector::class);

        # Add get and post parameters to the injection container
        $injector->prependContainer($request->request->all());
        $injector->prependContainer($request->query->all());

        # Resolve the route
        $router = $this->container->get(Router::class);
        $route = $router->resolve($request->getPathInfo(), $request->getMethod());
        $this->emit("route.resolved", $route);
        $this->container->set(Route::class, $route);

        # Add route parameters to the injection container
        $injector->prependContainer($route->getParams());

        static::log("Matched route " . $route->getPath());

        $result = $this->runBeforeMiddleware($route, $injector);

        if ($result instanceof Response) {
            static::log("Middleware returned response");
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

            if ($handler instanceof Closure) {
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
    protected function runBeforeMiddleware(Route $route, Injector $injector): ?Response
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
    protected function runAfterMiddleware(Route $route, Response $response, Injector $injector): Response
    {
        # Get the "after" middleware services for the route
        $middlewareClasses = $route->getAfter() ?: [];

        $injector->prependContainer([
            "request" => $this->get("request"),
            Request::class => $this->get("request"),
            "response" => $response,
            Response::class => $response,
        ]);

        foreach ($middlewareClasses as $middlewareClass) {
            # Check if the middleware was activated before calling the action
            if (array_key_exists($middlewareClass, $this->middleware)) {
                # Reuse the instance
                $mw = $this->middleware[$middlewareClass];
            } else {
                # Create a new instance
                $mw = $injector->createInstance($middlewareClass);
            }

            $result = $injector->callMethod($mw, "after");

            if ($result instanceof Response) {
                return $result;
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
        static::log("Request handler is anonymous callback");
        # Create a basic controller as a host for the callback
        $controller = $injector->createInstance(Controller::class);

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
        $actionId = (string) str($actionName)->snake();
        $actionMethod = (string) str($actionName)->camel();
        $template = $controllerPath . DIRECTORY_SEPARATOR . $actionId;

        $this->container->set("controller_slug", basename($controllerPath));
        $this->container->set("action_slug", $actionId);

        $this->emit("request.actionResolved", "$controllerClass::$actionMethod");

        static::log("Request handler is {$controllerPath}/{$actionMethod}");

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
    protected function handleError(Throwable $e): Response
    {
        # If debug mode is on, let the error handler take care of it
        if ($this->container->get("debug")) {
            throw $e;
        }

        $errorCode = 404;

        if ($e instanceof HttpException) {
            $errorCode = $e->getCode();
        }

        $view = $this->get(View::class);
        $template = "errors/view";

        if ($view->exists("errors/{$errorCode}")) {
            $template = "errors/{$errorCode}";
        }

        $response = new Response();
        $response->setContent($view->render($template, ["exception" => $e]));
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
    public function getControllerPath(string $controllerClass, string $baseNamespace): string
    {
        # Get the namespace of the controller relative to the base controller namespace
        # by removing \app\controllers (by default) from the beginning
        $relativeNamespace = (string) str($controllerClass)->after($baseNamespace);
        $parts = explode("\\", $relativeNamespace);
        # The last segment is the short class name
        $controllerClassName = array_pop($parts);
        # The other segments will be the path to the controller templates in the
        # `views` folder
        $controllerPath = implode(DIRECTORY_SEPARATOR, array_filter($parts));
        # Convert the short class name into a slug to use as folder name
        $controllerSlug = (string) str($controllerClassName)->before("Controller")->snake();

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
    protected function transformActionResult($actionResult): Response
    {
        $request = $this->container->get("request");
        $response = $this->container->get("response");

        # If $actionResult is `false`, return the global response
        if ($actionResult === false) {
            // die("actionResult is false /// " . __FILE__."::".__LINE__ . PHP_EOL);
            return $response;
        }

        # If it's already a response, return it
        if ($actionResult instanceof Response) {
            return $actionResult;
        }

        # Check if the request is JSON and return an appropriate response
        if ($request->acceptsJson()) {
            die("request acceptsJson /// " . __FILE__."::".__LINE__ . PHP_EOL);
            return new JsonResponse($actionResult, $response->getSymfonyResponse());
        }

        # If the action result is a string, use as the content of the response
        if (is_string($actionResult)) {
            $response->setContent($actionResult);
            return $response;
        }

        # Use the action result to render the view
        $view = $this->container->get(View::class);
        $content = $view->render($actionResult);
        $response->setContent($content);

        return $response;
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
