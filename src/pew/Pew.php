<?php

namespace pew;

use \pew\libs\Registry as Registry;
use \pew\libs\Str as Str;

/**
 * An object store.
 * 
 * The Pew class is a registry/factory that can build instances of classes
 * in the framework.
 * 
 * @package pew
 * @author ifcanduela <ifcanduela@gmail.com>
 */
class Pew extends Registry
{
    /**
     * Singleton instance.
     * 
     * @var \pew\Pew
     */
    protected static $instance;

    /**
     * Constructor is out of bounds.
     *
     * @throws Exception
     */
    protected function __construct(array $config = [])
    {
        $this->import($config);
        $this->init();
    }

    public static function instance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new self;
        }

        return self::$instance;
    }

    /**
     * Load the framework configuration file.
     */
    protected function init()
    {
        if (file_exists(__DIR__ .'/config.php')) {
            $pew_config = require_once __DIR__ . '/config.php';
            $this->import($pew_config);
        }

        $this['env'] = new \pew\libs\Env;

        $this->register('db_config', function ($pew) {
                    if (file_exists($pew['app_folder'] . '/config/database.php')) {
                        return include $pew['app_folder'] . '/config/database.php';
                    }
        
                    return [];
                });

        $this->register('db', function ($pew) {
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

            return new \pew\libs\Database($config);
        });

        $this->register('file_cache', function ($pew) {
            $cache_location = isSet($pew['cache_location']) ? $pew['cache_location'] : 'cache';

            $cache = new \pew\libs\FileCache();
            $cache->folder($pew['root_folder'] . '/cache');

            return $cache;
        });

        $this->register('log', function ($pew) {
            return new \pew\libs\FileLogger('logs', $this['log_level']);
        });

        $this->register('request', function ($pew) {
            $router = $pew['router'];
            $env = $pew['env'];

            $router->route($env->segments, $env->method);

            return new \pew\libs\Request($router, $env);
        });

        $this->register('routes', function ($pew) {
            if (file_exists($this['app_folder'] . '/config/routes.php')) {
                return include $this['app_folder'] . '/config/routes.php';
            }

            return $pew['default_routes'];
        });

        $this->register('router', function ($pew) {
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
        });

        $this->register('session', function($pew) {
            // @todo Use a specific $group 
            return new \pew\libs\Session;
        });

        $this->register('view', function ($pew) {
            $views_folder = trim($this['views_folder'], '/\\');
            $pew_views_folder = $this['system_folder'];
            $app_views_folder = $this['app_folder'];

            $v = new \pew\View($pew_views_folder . DIRECTORY_SEPARATOR . $views_folder);
            $v->folder($app_views_folder . DIRECTORY_SEPARATOR . $views_folder);

            return $v;
        });
    }

    /**
     * Obtains the current instance of the Pew-Pew-Pew application.
     *
     * @return App Instance of the application
     */
    public function app()
    {
        return $this['app'];
    }

    /**
     * Obtains a controller instance of the specified class.
     * 
     * @param string $controller_name Name of the controller class
     * @param Request $request a Request object
     * @return Object An instance of the required Controller
     * @throws InvalidArgumentException When no current controller exists and no class name is provided
     */
    public function controller($controller_name = null)
    {
        # check if the class name is omitted
        if (!isSet($controller_name)) {
            if (isSet($this['CurrentRequestController'])) {
                # if exists, return the current controller
                return $this['CurrentRequestController'];
            } else {
                # if not, throw an exception
                throw new \InvalidArgumentException("No current controller could be retrieved");
            }
        } else {
            $class_name = Str::camel_case($controller_name);

            $app_class_name = $this['app_namespace'] . '\\controllers\\' . $class_name;
            $pew_class_name = __NAMESPACE__ . '\\controllers\\' . $class_name;

            if (class_exists($app_class_name)) {
                return new $app_class_name($this['view']);
            } elseif (class_exists($pew_class_name)) {
                return new $pew_class_name($this['view']);
            }
        }

        return false;
    }

    /**
     * Obtains a model instance of the specified class.
     * 
     * This function returns a generic model if the specific model class is not
     * defined.
     *
     * @param string $table_name Name of the table
     * @return Object An instance of the required Model
     */
    public function model($table_name)
    {
        $class_base_name = Str::camel_case($table_name) . 'Model';
        $class_name = $this['app_namespace'] . '\\models\\' . $class_base_name;

        # Use the base Model class if the user-defined model is not available
        if (!class_exists($class_name)) {
            $class_name = __NAMESPACE__ . '\\Model';
        }
        
        $model = new $class_name($this['db'], $table_name);
        
        return $model;
    }

    /**
     * Obtains a library instance of the specified class.
     *
     * @param string $class_name Name of the library class
     * @param mixed $arguments One or more arguments for the constructor of the library
     * @return Object An instance of the required Library
     */
    public function library($class_name, array $arguments = [])
    {
        $app_class_name = $this['app_namespace'] . '\\libs\\' . $class_name;
        $pew_class_name = __NAMESPACE__ . '\\libs\\' . $class_name; 

        if (class_exists($app_class_name)) {
            $r = new \ReflectionClass($app_class_name);
            return $r->newInstanceArgs($arguments);
        } elseif (class_exists($pew_class_name)) {
            $r = new \ReflectionClass($pew_class_name);
            return $r->newInstanceArgs($arguments);
        }

        throw new \RuntimeException("Class {$class_name} not found");
    }

    /**
     * Obtains an instance of the database access object.
     * 
     * This function retrieves an instance of the current Database access class,
     * usually and by default PewDatabase.
     * 
     * The $config parameter specifies the connection configuration to use.
     *
     * @param string $config The configuration name
     * @return Object An instance of the database access object
     * @throws RuntimeException If database use is disabled
     */
    public function database($config = null)
    {
        return $this['db'];
    }

    /**
     * Get a FileLogger instance.
     * 
     * @return \pew\libs\FileLogger The log instance
     */
    public function log()
    {
        return $this['log'];
    }

    /**
     * Get a Session instance.
     * 
     * @return \pew\libs\Session The Session object
     */
    public function session()
    {
        return $this['session'];
    }

    /**
     * Gets a view and initialises it.
     *
     * Call this method without arguments to retrieve the base view. Use a key
     * to create and initialize a view conforming to the following parameters:
     *
     * $key = 'default' -> the base folder is the views folder of the framework
     * $key = !null     -> the view is a new instance
     *
     * @return \pew\View A view object
     */
    public function view($key = '')
    {
        return $this['view'];
    }

    /**
     * Shortcut for Pew::instance()->method().
     * 
     * @param string $name Static method name
     * @param array $arguments Method call argument
     * @return mixed Result of relayed instance call
     */
    public static function __callStatic($name, array $arguments)
    {
        if (method_exists(self::$instance, $name)) {
            return self::$instance->$name($arguments);
        }

        throw new \RuntimeException("No method $name in class " . __CLASS__);
    }
}
