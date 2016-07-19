<?php

require __DIR__ . '/functions.php';

$container = new \Pimple\Container();

//
// CONFIG
//

$container['app_namespace'] = "\\app\\";
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

//
// FACTORIES
//

$container['action'] = function ($c) {
    $route = $c['route'];
    $parts = preg_split('/[@\.]/', $route->getHandler());

    return $parts[1] ?? $route['action'] ?? $c['default_action'];
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
    $db_config = require $c['app_path'] . DIRECTORY_SEPARATOR . $c['config_folder'] . DIRECTORY_SEPARATOR . 'database.php';

    if (isSet($c['use_db'])) {
        $use_db = $c['use_db'];
    } else {
        $use_db = 'default';
    }

    if (!array_key_exists($use_db, $db_config)) {
        throw new \RuntimeException("Database configuration preset '$use_db' does not exist");
    }

    $config = $db_config[$use_db];

    if (!isSet($config)) {
        throw new \RuntimeException("Database is disabled.");
    }

    return new \pew\libs\Database($config);
};

$container['file_cache'] = function ($c) {
    $cache_path = $c['cache_path'];

    return new \pew\libs\FileCache($cache_path);
};

$container['injector'] = function ($c) {
    return new \pew\Injector(
        $c['request']->request->all(),
        $c['request']->query->all(),
        $c['route'],
        $c
    );
};

$container['model_factory'] = function ($c) {
    $f = new \pew\db\TableFactory($c['db']);

    $f->register_namespace($c['app_namespace'] . 'models', 'Model');
    $f->register_namespace('pew\models', 'Model');

    return $f;
};

$container['path'] = function ($c) {
    $request = $c['request'];
    $pathInfo = $request->getPathInfo();

    return '/' . trim($pathInfo, '/');
};

$container['request'] = function ($c) {
    return pew\request\Request::createFromGlobals();
};

$container['route'] = function ($c) {
    $request = $c['request'];
    $router = $c['router'];
    $pathInfo = $c['path'];

    return $router->route($pathInfo, $request->getMethod());
};

$container['router'] = function ($c) {
    $app_path = $c['app_path'];
    $routes = $c['routes'];

    return new \pew\router\Router($routes);
};

$container['routes'] = function ($c) {
    $app_folder = $c['app_path'];
    $routes_path = $app_folder . DIRECTORY_SEPARATOR . $c['config_folder'] . DIRECTORY_SEPARATOR . 'routes.php';

    $definitions = require($routes_path);

    $routes = [];

    foreach ($definitions as $path => $controller) {
        if (!is_array($controller) ) {
            // convert simple route to array route
            $controller = [
                'path' => $path,
                'controller' => $controller,
                'methods' => 'GET POST',
            ];

            $routes[] = $controller;
        } elseif (isset($controller['resource'])) {
            // create CRUD routes from resource route
            $controller_class = $controller['resource'];
            $slug = \Stringy\Stringy::create($controller_class)->humanize()->slugify();
            $underscored = $slug->underscored();

            $routes[] = [
                    'path' => "/{$slug}/{id}/edit",
                    'controller' => "{$controller_class}@edit",
                    'methods' => 'GET POST',
                    'name'=> "{$underscored}_edit",
                    'defaults' => $controller['defaults'] ?? [],
                    'conditions' => $controller['conditions'] ?? [],
                ];
            $routes[] = [
                    'path' => "/{$slug}/{id}/delete",
                    'controller' => "{$controller_class}@delete",
                    'methods' => 'GET POST',
                    'name'=> "{$underscored}_delete",
                    'defaults' => $controller['defaults'] ?? [],
                    'conditions' => $controller['conditions'] ?? [],
                ];
            $routes[] = [
                    'path' => "/{$slug}/add",
                    'controller' => "{$controller_class}@add",
                    'methods' => 'GET POST',
                    'name'=> "{$underscored}_add",
                    'defaults' => $controller['defaults'] ?? [],
                    'conditions' => $controller['conditions'] ?? [],
                ];
            $routes[] = [
                    'path' => "/{$slug}/{id}",
                    'controller' => "{$controller_class}@view",
                    'name'=> "{$underscored}_view",
                    'defaults' => $controller['defaults'] ?? [],
                    'conditions' => $controller['conditions'] ?? [],
                ];
            $routes[] = [
                    'path' => "/{$slug}",
                    'controller' => "{$controller_class}@index",
                    'name'=> "{$underscored}_index",
                    'defaults' => $controller['defaults'] ?? [],
                    'conditions' => $controller['conditions'] ?? [],
                ];
        } else {
            $routes[] = $controller;
        }
    }

    return $routes;
};

$container['session'] = function ($c) {
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
