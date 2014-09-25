<?php

return [
    'env' => function ($pew) {
        return new \pew\libs\Env;
    },

    'db_config' => function ($pew) {
        if (file_exists($pew['app_folder'] . '/config/database.php')) {
            return include $pew['app_folder'] . '/config/database.php';
        }

        return [];
    },

    'db' => function ($pew) {
        $db_config = $pew['db_config'];
        
        if (isSet($pew['use_db'])) {
            $use_db = $pew['use_db'];
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

        return new \pew\db\Database($config);
    },

    'file_cache' => function ($pew) {
        $cache_location = isSet($pew['cache_location']) ? $pew['cache_location'] : 'cache';

        $cache = new \pew\libs\FileCache();
        $cache->folder($pew['root_folder'] . '/cache');

        return $cache;
    },

    'log' => function ($pew) {
        return new \pew\libs\FileLogger('logs', $this['log_level']);
    },

    'request' => function ($pew) {
        $router = $pew['router'];
        $env = $pew['env'];

        $router->route($env->segments, $env->method);

        return new \pew\libs\Request($router, $env);
    },

    'routes' => function ($pew) {
        if (file_exists($pew['app_folder'] . '/config/routes.php')) {
            return include $pew['app_folder'] . '/config/routes.php';
        }

        return $pew['default_routes'];
    },

    'router' => function ($pew) {
        $routes = $pew['routes'];
        $resources = [];

        if (array_key_exists('resources', $routes)) {
                $resources = $routes['resources'];
                unset($routes['resources']);
        }

        # instantiate the router object
        $router = new libs\Router($routes);

        # configure resource routes
        foreach ($resources as $controller) {
            $router->resource($controller);
        }

        $router->default_controller($this['default_controller']);
        $router->default_action($this['default_action']);

        return $router;
    },

    'session' => function($pew) {
        // @todo Use a specific $group 
        return new \pew\libs\Session;
    },

    'view' => function ($pew) {
        $views_folder = trim($this['views_folder'], '/\\');
        $pew_views_folder = $this['system_folder'];
        $app_views_folder = $this['app_folder'];

        $v = new \pew\View($pew_views_folder . DIRECTORY_SEPARATOR . $views_folder);
        $v->folder($app_views_folder . DIRECTORY_SEPARATOR . $views_folder);

        return $v;
    },
];
