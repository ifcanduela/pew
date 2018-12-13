<?php

require __DIR__ . "/functions.php";

use ifcanduela\db\Database;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use pew\lib\FileCache;
use pew\lib\Injector;
use pew\lib\Session;
use pew\lib\Url;
use pew\model\TableManager;
use pew\request\Request;
use pew\router\Route;
use pew\router\Router;
use pew\View;
use Pimple\Container;
use Stringy\Stringy as S;
use Symfony\Component\HttpFoundation\Response;
use Whoops\Handler\PlainTextHandler;
use Whoops\Handler\PrettyPageHandler;
use Whoops\Run;

$container = new Container();

#
# CONFIG
#

$container["app_namespace"] = "\\app\\";
$container["cache_duration"] = 15 * 60;
$container["config_folder"] = "config";
$container["debug"] = false;
$container["default_action"] = "index";
$container["env"] = "dev";
$container["ignore_url_separator"] = ["\\", ".", "|"];
$container["ignore_url_suffixes"] = ["json", "html", "php"];
$container["log_level"] = Logger::WARNING;
$container["root_path"] = dirname(getcwd());
$container["www_path"] = getcwd();

#
# FACTORIES
#

$container["action"] = function (Container $c) {
    $route = $c["route"];
    $handler = $route->getHandler();

    if (is_callable($handler)) {
        return null;
    }

    if (is_string($handler)) {
        $parts = preg_split('/[@\.]/', $handler);

        if (isset($parts[1])) {
            return $parts[1];
        }
    }

    $actionSlug = $route->getParam("action", $c["default_action"]);

    return (string) S::create($actionSlug)->camelize();
};

$container["app_log"] = function (Container $c) {
    $logger = new Logger("App log");
    $logfile = $c["app_path"] . "/logs/app.log";
    $logger->pushHandler(new StreamHandler($logfile, $c["log_level"]));

    return $logger;
};

$container["cache_path"] = function (Container $c) {
    return $c["root_path"] . "/cache";
};

$container["controller"] = function (Container $c) {
    $route = $c["route"];
    $handler = $route->getHandler();
    $namespace = $c["route_namespace"];

    if (is_callable($handler)) {
        return $handler;
    }

    if ($handler) {
        $parts = explode("@", $handler);

        return  $namespace . $parts[0];
    } elseif ($route->checkParam("controller")) {
        $handler = S::create($route->getParam("controller"))->upperCamelize();

        return $namespace . $handler;
    }

    return null;
};

$container["controller_namespace"] = function (Container $c) {
    return $c["app_namespace"] . "controllers\\";
};

$container["controller_path"] = function (Container $c) {
    $controllerNamespace = $c["controller_namespace"];
    $routeNamespace = $c["route_namespace"];
    $controllerSlug = $c["controller_slug"];

    $path = str_replace(
            [$controllerNamespace, "\\"],
            ["", DIRECTORY_SEPARATOR],
            $routeNamespace
        ) . $controllerSlug;

    return $path;
};

$container["controller_slug"] = function (Container $c) {
    $controller = $c["controller"];

    if ($controller) {
        $shortName = (new \ReflectionClass($controller))->getShortName();

        return (string) S::create($shortName)
            ->removeRight("Controller")
            ->underscored()
            ->slugify();
    }

    return null;
};

$container["db"] = function (Container $c) {
    $dbConfig = $c["db_config"];
    $useDb = $c["use_db"] ?? "default";

    if (!array_key_exists($useDb, $dbConfig)) {
        throw new RuntimeException("Database configuration preset '{$useDb}' does not exist");
    }

    $tableManager = $c["tableManager"];

    return $tableManager->getConnection($useDb);
};

$container["db_config"] = function (Container $c) {
    return require $c["app_path"] . "/" . $c["config_folder"] . "/database.php";
};

$container["db_log"] = function (Container $c) {
    $logger = new Monolog\Logger("DB log");
    $logfile = $c["app_path"] . "/logs/db.log";
    $logger->pushHandler(new StreamHandler($logfile, Monolog\Logger::DEBUG));

    return $logger;
};

$container["error_handler"] = function (Container $c) {
    $request = $c["request"];
    $handler = null;

    if (php_sapi_name() === "cli" || $request->isJson()) {
        $handler = new PlainTextHandler();
    } else {
        $handler = new PrettyPageHandler();
    }

    $whoops = new Run();
    $whoops->pushHandler($handler);

    return $whoops;
};

$container["file_cache"] = function (Container $c) {
    $cachePath = $c["cache_path"];
    $cacheDuration = $c["cache_duration"];

    return new FileCache($cacheDuration, $cachePath);
};

$container["injector"] = function (Container $c) {
    return new Injector($c);
};

$container["path"] = function (Container $c) {
    $request = $c["request"];
    $pathInfo = $request->getPathInfo();

    $ignore_url_suffixes = join("|", $c["ignore_url_suffixes"]);
    $ignore_url_separator = join("", $c["ignore_url_separator"]);

    $pathInfo = preg_replace('/[' . $ignore_url_separator. '](' . $ignore_url_suffixes . ')$/', "", $pathInfo);

    return "/" . trim($pathInfo, "/");
};

$container["request"] = function (Container $c) {
    return Request::createFromGlobals();
};

$container["response"] = function (Container $c) {
    return new Response();
};

$container["route"] = function (Container $c) {
    $request = $c["request"];
    $router = $c["router"];
    $pathInfo = $c["path"];

    return $router->route($pathInfo, $request->getMethod());
};

$container["route_namespace"] = function (Container $c) {
    $route = $c["route"];

    $namespace = S::create($c["controller_namespace"])
        ->ensureLeft("\\")
        ->ensureRight("\\");

    if ($routeNamespace = $route->getNamespace()) {
        $namespace .= S::create($routeNamespace)
            ->removeLeft("\\")
            ->ensureRight("\\");
    }

    return $namespace;
};

$container["router"] = function (Container $c) {
    $routes = $c["routes"];

    return new Router($routes);
};

$container["routes"] = function (Container $c) {
    $appFolder = $c["app_path"];
    $configFolder = $c["config_folder"];
    $routesPath = "{$appFolder}/{$configFolder}/routes.php";
    $routes = [];

    $definitions = require $routesPath;

    foreach ($definitions as $path => $handler) {
        if ($handler instanceof Route) {
            $routes[] = $handler;
        } elseif (is_string($handler) || is_callable($handler)) {
            // convert simple route to array route
            $routes[] = Route::from($path)->handler($handler)->methods("GET", "POST");
        } elseif (isset($handler["handler"], $handler["path"])) {
            $routes[] = $handler;
        } else {
            throw new InvalidArgumentException("Invalid route: missing path or handler");
        }
    }

    return $routes;
};

$container["session"] = function () {
    return new Session();
};

$container["tableManager"] = function (Container $c) {
    $dbConfig = $c["db_config"];
    $tableManager = new \pew\model\TableManager();
    $tableManager->setDefaultConnection($c["use_db"]);
    $logger = null;

    if (isset($dbConfig["log_queries"]) && $dbConfig["log_queries"]) {
        $logger = $c["db_log"];
    }

    foreach ($dbConfig as $name => $connectionSettings) {
        if (isset($connectionSettings["engine"])) {
            $tableManager->setConnection($name, function () use ($connectionSettings, $logger) {
                $db = Database::fromArray($connectionSettings);

                if ($logger) {
                    $db->setLogger($logger);
                }

                return $db;
            });
        }
    }

    return $tableManager;
};

$container["url"] = function (Container $c) {
    $request = $c["request"];
    $router = $c["router"];

    return new Url($request, $router->routes);
};

$container["use_db"] = function (Container $c) {
    $db_config = $c["db_config"];

    return $db_config["use_db"] ?? $c["env"] ?? "default";
};

$container["view"] = function (Container $c) {
    $app_path = $c["app_path"];
    $fileCache = $c["file_cache"];
    $viewsFolder = $app_path . "/views/";

    return new View($viewsFolder, $fileCache);
};

return $container;
