<?php

$whoops = new \Whoops\Run;
$whoops->pushHandler(new \Whoops\Handler\PrettyPageHandler);
$whoops->register();

require __DIR__ . '/functions.php';

$container = new \Pimple\Container();

//
// CONFIG
//

$container['default_action'] = 'index';
$container['env'] = 'dev';
$container['root_path'] = dirname(getcwd());
$container['use_db'] = $container['env'];
$container['www_path'] = getcwd();
$container['cache_path'] = $container['root_path'] . DIRECTORY_SEPARATOR . 'cache';

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

    $parts = explode('@', $route->getHandler());

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

$container['fileCache'] = function ($c) {
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

    $routes = require($routes_path);

    foreach ($routes as $path => $controller) {
        if (!is_array($controller) ) {
            $controller = [
                'path' => $path,
                'controller' => $controller,
                'methods' => 'GET POST',
            ];
            
            $routes[$path] = $controller;
        }
    }

    return $routes;
};

$container['session'] = function ($c) {
    return new \pew\libs\Session();
};

$container['view'] = function ($c) {
    $app_path = $c['app_path'];
    $views_folder = $app_path . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR;

    return new \pew\View($views_folder);
};

\pew\model\TableFactory::setConnection('default', null, function () use ($container) {
    return $container['db'];
});

return $container;
