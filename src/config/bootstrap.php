<?php

declare(strict_types=1);

use ifcanduela\db\Database;
use ifcanduela\router\Router;

use Monolog\Handler\StreamHandler;
use Monolog\Level;
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

//
// CONFIG
//

$container["app_namespace"] = "\\app\\";
$container["cache_duration"] = 15 * 60;
$container["commands_namespace"] = "commands";
$container["config_folder"] = "config";
$container["debug"] = false;
$container["default_action"] = "index";
$container["default_layout"] = "";
$container["env"] = "dev";
$container["ignore_url_separator"] = ["\\", ".", "|"];
$container["ignore_url_suffixes"] = ["json", "html", "php"];
$container["log_level"] = Level::Warning;
$container["views_folder"] = "views";

$container["views_path"] = fn (Container $c) => $c["app_path"] . DIRECTORY_SEPARATOR . $c["views_folder"];

if (php_sapi_name() === "cli") {
    $container["root_path"] = getcwd();
    $container["www_path"] = getcwd() . DIRECTORY_SEPARATOR . "www";
} else {
    $container["root_path"] = dirname(getcwd());
    $container["www_path"] = getcwd();
}

//
// FACTORIES
//

$container[LoggerInterface::class] = function (Container $c): LoggerInterface {
    $logger = new Logger("App log");
    $logfile = $c["app_path"] . "/logs/app.log";
    $logger->pushHandler(new StreamHandler($logfile, $c["log_level"]));

    return $logger;
};

$container->alias("app_log", LoggerInterface::class);

$container[CacheInterface::class] = function (Container $c): CacheInterface {
    return new FilesystemAdapter(
        "pew",
        $c["cache_duration"],
        $c["cache_path"]
    );
};

$container->alias("cache", CacheInterface::class);

$container["cache_path"] = fn (Container $c): string => $c["root_path"] . DIRECTORY_SEPARATOR . "cache";

$container["controller_namespace"] = fn (Container $c): string => $c["app_namespace"] . "controllers\\";

$container[Database::class] = function (Container $c): Database {
    $dbConfig = $c["db_config"];
    $useDb = $c["use_db"] ?? "default";

    if (!array_key_exists($useDb, $dbConfig)) {
        throw new RuntimeException("Database configuration preset `$useDb` does not exist");
    }

    $tableManager = $c["tableManager"];

    return $tableManager->getConnection($useDb);
};

$container->alias("db", Database::class);

$container["db_config"] = fn (Container $c): array => require $c["app_path"] . "/" . $c["config_folder"] . "/database.php";

$container["db_log"] = function (Container $c): LoggerInterface {
    $logger = new Logger("DB log");
    $logfile = $c["app_path"] . "/logs/db.log";
    $logger->pushHandler(new StreamHandler($logfile, Level::Debug));

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

$container[Injector::class] = fn (Container $c): Injector => new Injector($c);

$container->alias("injector", Injector::class);

$container["path"] = function (Container $c): string {
    $request = $c["request"];
    $pathInfo = $request->getPathInfo();

    $ignoreUrlSuffixes = join("|", $c["ignore_url_suffixes"]);
    $ignoreUrlSeparator = join("", $c["ignore_url_separator"]);

    $pathInfo = preg_replace("/[$ignoreUrlSeparator]($ignoreUrlSuffixes)$/", "", $pathInfo);

    return "/" . trim($pathInfo, "/");
};

$container[Request::class] = fn (Container $c): Request => Request::createFromGlobals();

$container->alias("request", Request::class);

$container[Response::class] = fn (Container $c): Response => new Response();

$container->alias("response", Response::class);

$container[Router::class] = function (Container $c): Router {
    $appFolder = $c["app_path"];
    $configFolder = $c["config_folder"];
    $routesPath = "$appFolder/$configFolder/routes.php";

    $router = new Router();
    $router->loadFile($routesPath);

    return $router;
};

$container->alias("router", Router::class);

$container[Session::class] = fn (Container $c): Session => new Session();

$container->alias("session", Session::class);

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

$container->alias("tableManager", TableManager::class);

$container["url"] = function (Container $c): Url {
    $request = $c["request"];

    return new Url($request);
};

$container["use_db"] = function (Container $c): string {
    $dbConfig = $c["db_config"];

    return $dbConfig["use_db"] ?? $c["env"] ?? "default";
};

$container[View::class] = function (Container $c): View {
    $view = new View($c["views_path"]);
    $view->layout($c["default_layout"]);

    return $view;
};

$container->alias("view", View::class);

$container["views_path"] = function (Container $c): string {
    $appPath = $c["app_path"];
    $viewsFolder = $c["views_folder"];

    return realpath("$appPath/$viewsFolder/");
};

return $container;
