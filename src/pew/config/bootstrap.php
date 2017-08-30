<?php

require __DIR__ . '/functions.php';

$container = new \Pimple\Container();

//
// CONFIG
//

$container['app_namespace'] = "\\app\\";
$container['config_folder'] = "config";
$container['debug'] = false;
$container['default_action'] = 'index';
$container['env'] = 'dev';
$container['use_db'] = $container['env'];
$container['www_path'] = getcwd();

$container['root_path'] = function ($c) {
    return dirname($c['app_path']);
};

$container['cache_path'] = function ($c) {
    return $c['root_path'] . DIRECTORY_SEPARATOR . 'cache';
};

$container['cache_duration'] = 15 * 60;

$container['ignore_url_separator'] = '[\.|]';
$container['ignore_url_suffixes'] = [
    'json',
    'html',
    'php'
];

//
// FACTORIES
//

$container['action'] = function ($c) {
    $route = $c['route'];
    $parts = preg_split('/[@\.]/', $route->getHandler());

    $action = $parts[1] ?? $route['action'] ?? $c['default_action'];

    return preg_replace('/[^a-zA-Z0-9_]/', '_', $action);
};

$container['controller'] = function ($c) {
    $route = $c['route'];
    $handler = $route->getHandler();

    if (is_callable($handler)) {
        return $handler;
    }

    $parts = explode('@', $handler);

    return $c['controller_namespace'] . $parts[0];
};

$container['controller_namespace'] = function ($c) {
    return $c['app_namespace'] . 'controllers\\';
};

$container['controller_slug'] = function ($c) {
    $controller_class = basename($c['controller']);
    $controller_name = preg_replace('/.+(Controller)$/', '', $controller_class);

    return Stringy\StaticStringy::slugify($controller_name);
};

$container['db'] = function ($c) {
    $db_config = $c['db_config'];

    if (isset($c['use_db'])) {
        $use_db = $c['use_db'];
    } else {
        $use_db = 'default';
    }

    if (!array_key_exists($use_db, $db_config)) {
        throw new \RuntimeException("Database configuration preset '{$use_db}' does not exist");
    }

    $config = $db_config[$use_db];

    if (!isset($config)) {
        throw new \RuntimeException("Database is disabled.");
    }

    $db = \ifcanduela\db\Database::fromArray($config);

    if (isset($db_config['log_queries'])) {
        $logger = $c['logger'];
        $db->setLogger($logger);
    }

    return $db;
};

$container['logger'] = function ($c) {
    $logger = new Monolog\Logger('App log');
    $logfile = $c['app_path'] . '/logs/app.log';
    $logger->pushHandler(new Monolog\Handler\StreamHandler($logfile, Monolog\Logger::INFO));

    return $logger;
};

$container['db_config'] = function ($c) {
    return require $c['app_path'] . DIRECTORY_SEPARATOR . $c['config_folder'] . DIRECTORY_SEPARATOR . 'database.php';
};

$container['file_cache'] = function ($c) {
    $cache_path = $c['cache_path'];
    $cache_duration = $c['cache_duration'];

    return new \pew\libs\FileCache($cache_duration, $cache_path);
};

$container['injector'] = function ($c) {
    return new \pew\libs\Injector(
        $c['request']->request->all(),
        $c['request']->query->all(),
        $c['route'],
        $c
    );
};

$container['path'] = function ($c) {
    $request = $c['request'];
    $path_info = $request->getPathInfo();

    $ignore_url_suffixes = $c['ignore_url_suffixes'];
    $ignore = implode('|', $ignore_url_suffixes);
    $suffix_separator = $c['ignore_url_separator'];

    $path_info = preg_replace("/{$suffix_separator}({$ignore})$/", '', $path_info);

    return '/' . trim($path_info, '/');
};

$container['request'] = function () {
    return pew\request\Request::createFromGlobals();
};

$container['route'] = function ($c) {
    $request = $c['request'];
    $router = $c['router'];
    $path_info = $c['path'];

    return $router->route($path_info, $request->getMethod());
};

$container['router'] = function ($c) {
    $routes = $c['routes'];

    return new \pew\router\Router($routes);
};

$container['routes'] = function ($c) {
    $app_folder = $c['app_path'];
    $routes_path = $app_folder . DIRECTORY_SEPARATOR . $c['config_folder'] . DIRECTORY_SEPARATOR . 'routes.php';

    $definitions = require $routes_path;

    $routes = [];

    foreach ($definitions as $path => $handler) {
        if ($handler instanceof \pew\router\Route) {
            $routes[] = $handler;
        } elseif (is_string($handler) || is_callable($handler)) {
            // convert simple route to array route
            $handler = \pew\router\Route::from($path)->handler($handler)->methods('GET', 'POST');
            $routes[] = $handler;
        } elseif (is_array($handler) && isset($handler['resource'])) {
            // create CRUD routes from resource route
            $controller_class = $handler['resource'];

            if (isset($handler['path'])) {
                $slug = \Stringy\Stringy::create($handler['path']);
            } else {
                $slug = \Stringy\Stringy::create($controller_class)->humanize()->slugify();
            }

            $underscored = $slug->underscored();

            $routes[] = [
                    'path' => "/{$slug}/{id}/edit",
                    'handler' => "{$controller_class}@edit",
                    'methods' => 'GET POST',
                    'name'=> "{$underscored}_edit",
                    'defaults' => $handler['defaults'] ?? [],
                    'conditions' => $handler['conditions'] ?? [],
                ];
            $routes[] = [
                    'path' => "/{$slug}/{id}/delete",
                    'handler' => "{$controller_class}@delete",
                    'methods' => 'GET POST',
                    'name'=> "{$underscored}_delete",
                    'defaults' => $handler['defaults'] ?? [],
                    'conditions' => $handler['conditions'] ?? [],
                ];
            $routes[] = [
                    'path' => "/{$slug}/add",
                    'handler' => "{$controller_class}@add",
                    'methods' => 'GET POST',
                    'name'=> "{$underscored}_add",
                    'defaults' => $handler['defaults'] ?? [],
                    'conditions' => $handler['conditions'] ?? [],
                ];
            $routes[] = [
                    'path' => "/{$slug}/{id}",
                    'handler' => "{$controller_class}@view",
                    'name'=> "{$underscored}_view",
                    'defaults' => $handler['defaults'] ?? [],
                    'conditions' => $handler['conditions'] ?? [],
                ];
            $routes[] = [
                    'path' => "/{$slug}",
                    'handler' => "{$controller_class}@index",
                    'name'=> "{$underscored}_index",
                    'defaults' => $handler['defaults'] ?? [],
                    'conditions' => $handler['conditions'] ?? [],
                ];
        } elseif (isset($handler['handler'], $handler['path'])) {
            $routes[] = $handler;
        } else {
            throw new \InvalidArgumentException("Invalid route: missing path or handler");
        }
    }

    return $routes;
};

$container['session'] = function () {
    return new \pew\libs\Session();
};

$container['url'] = function ($c) {
    $request = $c['request'];
    $routes = $c['routes'];

    return new \pew\libs\Url($request, $routes);
};

$container['view'] = function ($c) {
    $app_path = $c['app_path'];
    $file_cache = $c['file_cache'];
    $views_folder = $app_path . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR;

    return new \pew\View($views_folder, $file_cache);
};

$container['whoops_handler'] = function ($c) {
    $request = $c['request'];

    if (php_sapi_name() === 'cli' || $request->isJson()) {
        return new \Whoops\Handler\PlainTextHandler();
    }

    return new \Whoops\Handler\PrettyPageHandler();
};

# setup the Whoops error handler

$whoops = new \Whoops\Run;
$whoops->pushHandler($container['whoops_handler']);
$whoops->register();

\pew\model\TableFactory::setConnection('default', null, function () use ($container) {
    return $container['db'];
});

return $container;
