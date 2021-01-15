<?php declare(strict_types=1);

use ifcanduela\db\Database;
use ifcanduela\router\Router;
use ifcanduela\router\Route;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;

use pew\di\Container;
use pew\di\Injector;
use pew\lib\Session;
use pew\lib\Url;
use pew\model\TableManager;
use pew\request\Request;
use pew\response\Response;
use pew\View;

use Psr\Log\LoggerInterface;

use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Contracts\Cache\CacheInterface;

use Whoops\Handler\JsonResponseHandler;
use Whoops\Handler\PlainTextHandler;
use Whoops\Handler\PrettyPageHandler;
use Whoops\Run;

$container = new Container();
$container[Container::class] = $container;

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

$container["views_path"] = function (Container $c) {
    return $c["app_path"] . DIRECTORY_SEPARATOR . $c["views_folder"];
};

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

$container[LoggerInterface::class] = function (Container $c): LoggerInterface {
    $logger = new Logger("App log");
    $logfile = $c["app_path"] . "/logs/app.log";
    $logger->pushHandler(new StreamHandler($logfile, $c["log_level"]));

    return $logger;
};

$container->alias(LoggerInterface::class, "app_log");

$container[CacheInterface::class] = function (Container $c): CacheInterface {
    return new FilesystemAdapter(
        "pew",
        $c["cache_duration"],
        $c["cache_path"]
    );
};

$container->alias(CacheInterface::class, "cache");

$container["cache_path"] = function (Container $c): string {
    return $c["root_path"] . DIRECTORY_SEPARATOR . "cache";
};

$container["controller_namespace"] = function (Container $c): string {
    return $c["app_namespace"] . "controllers\\";
};

$container[Database::class] = function (Container $c): Database {
    $dbConfig = $c["db_config"];
    $useDb = $c["use_db"] ?? "default";

    if (!array_key_exists($useDb, $dbConfig)) {
        throw new RuntimeException("Database configuration preset `{$useDb}` does not exist");
    }

    $tableManager = $c["tableManager"];

    return $tableManager->getConnection($useDb);
};

$container->alias(Database::class, "db");

$container["db_config"] = function (Container $c): array {
    return require $c["app_path"] . "/" . $c["config_folder"] . "/database.php";
};

$container["db_log"] = function (Container $c): LoggerInterface {
    $logger = new Logger("DB log");
    $logfile = $c["app_path"] . "/logs/db.log";
    $logger->pushHandler(new StreamHandler($logfile, Logger::DEBUG));

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

$container[Injector::class] = function (Container $c): Injector {
    return new Injector($c);
};

$container->alias(Injector::class, "injector");


$container["path"] = function (Container $c): string {
    $request = $c["request"];
    $pathInfo = $request->getPathInfo();

    $ignore_url_suffixes = join("|", $c["ignore_url_suffixes"]);
    $ignore_url_separator = join("", $c["ignore_url_separator"]);

    $pathInfo = preg_replace("/[{$ignore_url_separator}]({$ignore_url_suffixes})$/", "", $pathInfo);

    return "/" . trim($pathInfo, "/");
};

$container[Request::class] = function (Container $c): Request {
    return Request::createFromGlobals();
};

$container->alias(Request::class, "request");

$container[Response::class] = function (Container $c): Response {
    return new Response();
};

$container->alias(Response::class, "response");

$container[Route::class] = function (Container $c): Route {
    $request = $c["request"];
    $router = $c["router"];
    $pathInfo = $c["path"];

    return $router->resolve($pathInfo, $request->getMethod());
};

$container->alias(Route::class, "route");

$container[Router::class] = function (Container $c): Router {
    $appFolder = $c["app_path"];
    $configFolder = $c["config_folder"];
    $routesPath = "{$appFolder}/{$configFolder}/routes.php";

    $router = new Router();
    $router->loadFile($routesPath);

    return $router;
};

$container->alias(Router::class, "router");

$container[Session::class] = function (Container $c): Session {
    return new Session();
};

$container->alias(Session::class, "session");

$container[TableManager::class] = function (Container $c): TableManager {
    $dbConfig = $c["db_config"];
    $tableManager = new TableManager();
    $tableManager->setDefaultConnectionName($c["use_db"]);
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

$container->alias(TableManager::class, "tableManager");

$container["url"] = function (Container $c): Url {
    $request = $c["request"];

    return new Url($request);
};

$container["use_db"] = function (Container $c): string {
    $db_config = $c["db_config"];

    return $db_config["use_db"] ?? $c["env"] ?? "default";
};

$container[View::class] = function (Container $c): View {
    $view = new View($c["views_path"]);
    $view->layout($c["default_layout"]);

    return $view;
};

$container->alias(View::class, "view");

$container["views_path"] = function (Container $c): string {
    $app_path = $c["app_path"];
    $views_folder = $c["views_folder"];

    return realpath("{$app_path}/{$views_folder}/");
};

return $container;
