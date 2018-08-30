<?php

require __DIR__ . "/functions.php";

use ifcanduela\db\Database;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use pew\lib\FileCache;
use pew\lib\Injector;
use pew\lib\Session;
use pew\lib\Url;
use pew\model\TableFactory;
use pew\request\Request;
use pew\router\Route;
use pew\router\Router;
use pew\View;
use Pimple\Container;
use Stringy\Stringy as S;
use Whoops\Handler\PlainTextHandler;
use Whoops\Handler\PrettyPageHandler;
use Whoops\Run;

return (function () {
    $container = new Container();

    #
    # CONFIG
    #

    $container["app_namespace"] = "\\app\\";
    $container["config_folder"] = "config";
    $container["debug"] = false;
    $container["default_action"] = "index";
    $container["env"] = "dev";
    $container["log_level"] = Logger::WARNING;
    $container["use_db"] = $container["env"];
    $container["www_path"] = getcwd();

    $container["root_path"] = function (Container $c) {
        return dirname($c["app_path"]);
    };

    $container["cache_path"] = function (Container $c) {
        return $c["root_path"] . "/cache";
    };

    $container["cache_duration"] = 15 * 60;

    $container["ignore_url_separator"] = ["\\", ".", "|"];
    $container["ignore_url_suffixes"] = ["json", "html", "php"];

    #
    # FACTORIES
    #

    $container["action"] = function (Container $c) {
        $route = $c["route"];
        $handler = $route->getHandler();

        if (is_string($handler)) {
            $parts = preg_split('/[@\.]/', $handler);

            if (!empty($parts[1])) {
                return $parts[1];
            }

            if ($route["action"]) {
                return (string) S::create($route["action"])->camelize();
            }

            return $c["default_action"];
        }

        return null;
    };

    $container["controller"] = function (Container $c) {
        $route = $c["route"];
        $handler = $route->getHandler();

        if (is_callable($handler)) {
            return $handler;
        }

        if ($handler) {
            $parts = explode("@", $handler);
            return $c["controller_namespace"] . $parts[0];
        } elseif ($route->checkParam("controller")) {
            $handler = S::create($route->getParam("controller"))->upperCamelize();
            return $c["controller_namespace"] . $handler;
        }

        return null;
    };

    $container["controller_namespace"] = function (Container $c) {
        return $c["app_namespace"] . "controllers\\";
    };

    $container["controller_slug"] = function (Container $c) {
        $controller = $c["controller"];

        if ($controller) {
            $controller_class = basename($c["controller"]);

            return (string) S::create($controller_class)
                ->removeRight("Controller")
                ->slugify();
        }

        return null;
    };

    $container["db"] = function (Container $c) {
        $db_config = $c["db_config"];

        if (isset($c["use_db"])) {
            $use_db = $c["use_db"];
        } else {
            $use_db = "default";
        }

        if (!array_key_exists($use_db, $db_config)) {
            throw new RuntimeException("Database configuration preset '{$use_db}' does not exist");
        }

        $connection_settings = $db_config[$use_db];

        if (!isset($connection_settings)) {
            throw new RuntimeException("Database is disabled.");
        }

        $db = Database::fromArray($connection_settings);

        if (isset($db_config["log_queries"]) && $db_config["log_queries"]) {
            $logger = $c["db_log"];
            $db->setLogger($logger);
        }

        return $db;
    };

    $container["app_log"] = function (Container $c) {
        $logger = new Logger("App log");
        $logfile = $c["app_path"] . "/logs/app.log";
        $logger->pushHandler(new StreamHandler($logfile, $c["log_level"]));

        return $logger;
    };

    $container["db_log"] = function (Container $c) {
        $logger = new Monolog\Logger("DB log");
        $logfile = $c["app_path"] . "/logs/db.log";
        $logger->pushHandler(new StreamHandler($logfile, Monolog\Logger::DEBUG));

        return $logger;
    };

    $container["db_config"] = function (Container $c) {
        return require $c["app_path"] . "/" . $c["config_folder"] . "/database.php";
    };

    $container["file_cache"] = function (Container $c) {
        $cache_path = $c["cache_path"];
        $cache_duration = $c["cache_duration"];

        return new FileCache($cache_duration, $cache_path);
    };

    $container["injector"] = function (Container $c) {
        return new Injector(
            $c["request"]->request->all(),
            $c["request"]->query->all(),
            $c["route"],
            $c
        );
    };

    $container["path"] = function (Container $c) {
        $request = $c["request"];
        $path_info = $request->getPathInfo();

        $ignore_url_suffixes = join("|", $c["ignore_url_suffixes"]);
        $ignore_url_separator = join("|", $c["ignore_url_separator"]);

        $path_info = preg_replace('/[' . $ignore_url_separator. '](' . $ignore_url_suffixes . ')$/', "", $path_info);

        return "/" . trim($path_info, "/");
    };

    $container["request"] = function () {
        return Request::createFromGlobals();
    };

    $container["route"] = function (Container $c) {
        $request = $c["request"];
        $router = $c["router"];
        $path_info = $c["path"];

        return $router->route($path_info, $request->getMethod());
    };

    $container["router"] = function (Container $c) {
        $routes = $c["routes"];

        return new Router($routes);
    };

    $container["routes"] = function (Container $c) {
        $app_folder = $c["app_path"];
        $routes_path = $app_folder . "/". $c["config_folder"] . "/routes.php";

        $definitions = require $routes_path;

        $routes = [];

        foreach ($definitions as $path => $handler) {
            if ($handler instanceof Route) {
                $routes[] = $handler;
            } elseif (is_string($handler) || is_callable($handler)) {
                // convert simple route to array route
                $handler = Route::from($path)->handler($handler)->methods("GET", "POST");
                $routes[] = $handler;
            } elseif (is_array($handler) && isset($handler["resource"])) {
                // create CRUD routes from resource route
                $controller_class = $handler["resource"];

                if (isset($handler["path"])) {
                    $slug = S::create($handler["path"]);
                } else {
                    $slug = S::create($controller_class)->humanize()->slugify();
                }

                $underscored = $slug->underscored();

                $routes[] = [
                        "path" => "/{$slug}/{id}/edit",
                        "handler" => "{$controller_class}@edit",
                        "methods" => "GET POST",
                        "name"=> "{$underscored}_edit",
                        "defaults" => $handler["defaults"] ?? [],
                        "conditions" => $handler["conditions"] ?? [],
                    ];
                $routes[] = [
                        "path" => "/{$slug}/{id}/delete",
                        "handler" => "{$controller_class}@delete",
                        "methods" => "GET POST",
                        "name"=> "{$underscored}_delete",
                        "defaults" => $handler["defaults"] ?? [],
                        "conditions" => $handler["conditions"] ?? [],
                    ];
                $routes[] = [
                        "path" => "/{$slug}/add",
                        "handler" => "{$controller_class}@add",
                        "methods" => "GET POST",
                        "name"=> "{$underscored}_add",
                        "defaults" => $handler["defaults"] ?? [],
                        "conditions" => $handler["conditions"] ?? [],
                    ];
                $routes[] = [
                        "path" => "/{$slug}/{id}",
                        "handler" => "{$controller_class}@view",
                        "name"=> "{$underscored}_view",
                        "defaults" => $handler["defaults"] ?? [],
                        "conditions" => $handler["conditions"] ?? [],
                    ];
                $routes[] = [
                        "path" => "/{$slug}",
                        "handler" => "{$controller_class}@index",
                        "name"=> "{$underscored}_index",
                        "defaults" => $handler["defaults"] ?? [],
                        "conditions" => $handler["conditions"] ?? [],
                    ];
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

    $container["url"] = function (Container $c) {
        $request = $c["request"];
        $routes = $c["routes"];

        return new Url($request, $routes);
    };

    $container["view"] = function (Container $c) {
        $app_path = $c["app_path"];
        $file_cache = $c["file_cache"];
        $views_folder = $app_path . "/views/";

        return new View($views_folder, $file_cache);
    };

    # setup the Whoops error handler

    $container["whoops_handler"] = function (Container $c) {
        $request = $c["request"];

        if (php_sapi_name() === "cli" || $request->isJson()) {
            return new PlainTextHandler();
        }

        return new PrettyPageHandler();
    };

    $whoops = new Run();
    $whoops->pushHandler($container["whoops_handler"]);
    $whoops->register();

    TableFactory::setConnection("default", null, function () use ($container) {
        return $container["db"];
    });

    return $container;
})();
