<?php

require __DIR__ . "/functions.php";

use ifcanduela\db\Database;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use pew\di\Container;
use pew\di\Injector;
use pew\lib\Session;
use pew\lib\Url;
use pew\model\TableManager;
use pew\request\Request;
use pew\router\Route;
use pew\router\Router;
use pew\View;
use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Symfony\Contracts\Cache\CacheInterface;
use Whoops\Handler\JsonResponseHandler;
use Whoops\Handler\PlainTextHandler;
use Whoops\Handler\PrettyPageHandler;
use Whoops\Run;

$container = new Container();

#
# CONFIG
#

$container["app_namespace"] = "\\app\\";
$container["cache_duration"] = 15 * 60;
$container["commands_namespace"] = "commands";
$container["config_folder"] = "config";
$container["debug"] = false;
$container["default_action"] = "index";
$container["default_layout"] = "default.layout";
$container["env"] = "dev";
$container["ignore_url_separator"] = ["\\", ".", "|"];
$container["ignore_url_suffixes"] = ["json", "html", "php"];
$container["log_level"] = Logger::WARNING;
$container["views_folder"] = "views";

if (php_sapi_name() === "cli") {
    $container["root_path"] =  getcwd();
    $container["www_path"] = getcwd() . DIRECTORY_SEPARATOR . "www";
} else {
    $container["root_path"] =  dirname(getcwd());
    $container["www_path"] = getcwd();
}

#
# FACTORIES
#

$container["app_log"] = function (Container $c): LoggerInterface {
    $logger = new Logger("App log");
    $logfile = $c["app_path"] . "/logs/app.log";
    $logger->pushHandler(new StreamHandler($logfile, $c["log_level"]));

    return $logger;
};

$container["cache"] = function (Container $c): CacheInterface {
    return new FilesystemAdapter(
        "pew",
        $c["cache_duration"],
        $c["cache_path"]
    );
};

$container["cache_path"] = function (Container $c): string {
    return $c["root_path"] . DIRECTORY_SEPARATOR . "cache";
};

$container["controller_namespace"] = function (Container $c): string {
    return $c["app_namespace"] . "controllers\\";
};

$container["db"] = function (Container $c): Database {
    $dbConfig = $c["db_config"];
    $useDb = $c["use_db"] ?? "default";

    if (!array_key_exists($useDb, $dbConfig)) {
        throw new RuntimeException("Database configuration preset `{$useDb}` does not exist");
    }

    $tableManager = $c["tableManager"];

    return $tableManager->getConnection($useDb);
};

$container["db_config"] = function (Container $c): array {
    return require $c["app_path"] . "/" . $c["config_folder"] . "/database.php";
};

$container["db_log"] = function (Container $c): LoggerInterface {
    $logger = new Monolog\Logger("DB log");
    $logfile = $c["app_path"] . "/logs/db.log";
    $logger->pushHandler(new StreamHandler($logfile, Monolog\Logger::DEBUG));

    return $logger;
};

$container["error_handler"] = function (Container $c): Run {
    $request = $c["request"];
    $isCli = (php_sapi_name() === "cli");

    $whoops = new Run();
    $whoops->prependHandler($isCli ? new PlainTextHandler() : new PrettyPageHandler());

    if ($request->isJson()) {
        $whoops->prependHandler(new JsonResponseHandler());
    }

    return $whoops;
};

$container["injector"] = function (Container $c): Injector {
    return new Injector($c);
};

$container["path"] = function (Container $c): string {
    $request = $c["request"];
    $pathInfo = $request->getPathInfo();

    $ignore_url_suffixes = join("|", $c["ignore_url_suffixes"]);
    $ignore_url_separator = join("", $c["ignore_url_separator"]);

    $pathInfo = preg_replace("/[{$ignore_url_separator}]({$ignore_url_suffixes})$/", "", $pathInfo);

    return "/" . trim($pathInfo, "/");
};

$container["request"] = function (Container $c): Request {
    return Request::createFromGlobals();
};

$container["response"] = function (Container $c): SymfonyResponse {
    return new SymfonyResponse();
};

$container["route"] = function (Container $c): Route {
    $request = $c["request"];
    $router = $c["router"];
    $pathInfo = $c["path"];

    return $router->route($pathInfo, $request->getMethod());
};

$container["router"] = function (Container $c): Router {
    $routes = $c["routes"];

    return new Router($routes);
};

$container["routes"] = function (Container $c): array {
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

$container["session"] = function (): Session {
    return new Session();
};

$container["tableManager"] = function (Container $c): TableManager {
    $dbConfig = $c["db_config"];
    $tableManager = new TableManager();
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

$container["url"] = function (Container $c): Url {
    $request = $c["request"];

    return new Url($request);
};

$container["use_db"] = function (Container $c): string {
    $db_config = $c["db_config"];

    return $db_config["use_db"] ?? $c["env"] ?? "default";
};

$container["view"] = function (Container $c): View {
    $view = new View($c["views_path"]);
    $view->layout($c["default_layout"]);

    return $view;
};

$container["views_path"] = function (Container $c): string {
    $app_path = $c["app_path"];
    $views_folder = $c["views_folder"];

    return realpath("{$app_path}/{$views_folder}/");
};

return $container;
