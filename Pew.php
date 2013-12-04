<?php

namespace pew;

use \pew\libs\Registry as Registry;

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
     * Framework and application configuration settings.
     * 
     * @var \pew\libs\Registry
     */
    protected $config;

    /**
     * Singleton-like instances.
     * 
     * @var \pew\libs\Registry
     */
    protected $instances;

    /**
     * Constructor is out of bounds.
     *
     * @throws Exception
     */
    public function __construct(array $config = [])
    {
        parent::__construct();

        $this->import($config);
        $this->instances = new Registry();
        $this->init();
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
    }

    /**
     * Obtains the current instance of the Pew-Pew-Pew application.
     *
     * The folder parameter must be a sub-folder of the folder in which the
     * main index.php file resides.
     *
     * @param $app_folder Folder name that holds the application folders and files
     * @return App Instance of the application
     */
    public function app($app_folder = 'app', $config_file = 'config')
    {
        if (!isset($this['App'])) {
            # load app/config/{$config}.php
            $app_config = include getcwd() . '/' . $app_folder . '/config/' . $config_file . '.php';

            // merge user config with Pew config
            $this['import']($app_config);

            if (!isSet($this['env'])) {
                $this['env'] = 'development';
            }

            # add application namespace and path
            $app_folder_name = trim(basename($app_folder));
            $this['app_namespace'] = '\\' . $app_folder_name;
            $this['app_folder'] = realpath($app_folder);
            $this['app_config'] = $config_file;

            # load app/config/bootstrap.php
            if (file_exists($this['app_folder'] . '/config/bootstrap.php')) {
                require $this['app_folder'] . '/config/bootstrap.php';
            }

            $this['App'] = new App;
        }

        return $this['App'];
    }

    /**
     * Obtains a controller instance of the specified class.
     * 
     * @param string $controller_name Name of the controller class
     * @param Request $request a Request object
     * @return Object An instance of the required Controller
     * @throws InvalidArgumentException When no current controller exists and no class name is provided
     */
    public function controller($controller_name = null, \pew\libs\Request $request = null)
    {
        # check if the class name is omitted
        if (!isset($controller_name)) {
            if (isset($this['CurrentRequestController'])) {
                # if exists, return the current controller
                return $this['CurrentRequestController'];
            } else {
                # if not, throw an exception
                throw new \InvalidArgumentException("No current controller could be retrieved");
            }
        } else {
            $class_name = file_name_to_class_name($controller_name);

            $app_class_name = $this['app_namespace'] . '\\controllers\\' . $class_name;
            $pew_class_name = '\\pew\\controllers\\' . $class_name;

            if (!$request) {
                $request = self::request();
            }
            
            if (class_exists($app_class_name)) {
                return new $app_class_name($request);
            } elseif (class_exists($pew_class_name)) {
                return new $pew_class_name($request);
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
        $class_base_name = file_name_to_class_name($table_name) . 'Model';
        $class_name = $this['app_namespace'] . '\\models\\' . $class_base_name;

        # Check that the model has not been previously instantiated
        if (!isset($this[$class_name])) {
            # Instantiate Model if the derived class is not available
            if (!class_exists($class_name)) {
                $class_name = '\\pew\\Model';
            }
        
            # Dependencies
            $database = self::database();

            # Instantiation and storage
            $this[$class_name] = new $class_name($database, $table_name);
        }
        
        return $this[$class_name];
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
            $r = new \ReflectionClass($this['app_namespace'] . '\\lib\\' . $class_name);
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
        if (!isset($this['Database'])) {
            if (!is_array($config)) {
                # load app/config/database.php
                if (file_exists($this['app_folder'] . '/config/database.php')) {
                    $this['database_config'] = include $this['app_folder'] . '/config/database.php';
                }

                $db_config = $this['database_config'];

                if (isSet($this['use_db'])) {
                    $use_db = $this['use_db'];
                } else {
                    $use_db = 'default';
                }

                if (!array_key_exists($use_db, $db_config)) {
                    throw new \RuntimeException("Database configuration preset '$use_db' does not exist");
                }

                $config = $db_config[$use_db];
            }

            if (isset($config)) {
                $this['Database'] = new libs\Database($config);
            } else {
                throw new \RuntimeException("Database is disabled.");
            }
        }

        return $this['Database'];
    }
    
    /**
     * Retrieves and initialises the Request object for the current request.
     * 
     * @param string $uri_string A list of slash-separated segments.
     * @return Request The initialised request object
     */
    public function request($uri_string = null)
    {
        if (!isset($this['Request'])) {
            # instantiate the request object
            $this['Request'] = new libs\Request;
        }

        return $this['Request'];
    }

    /**
     * Retrieves and initialises a Router object.
     * 
     * @param string $uri_string A list of slash-separated segments.
     * @return Router The initialised request object
     * @throws Exception When the class does not exist.
     */
    public function router($uri_string = null)
    {
        if (!isset($this['Router'])) {
            $routes = [];
            # fetch the routes configuration

            // load app/config/routes.php
            if (file_exists($this['app_folder'] . '/config/routes.php')) {
                $routes = include $this['app_folder'] . '/config/routes.php';
            }

            # instantiate the router object
            $router = new libs\Router($routes);
            $router->default_controller($this['default_controller']);
            $router->default_action($this['default_action']);

            $this['Router'] = $router;
        }

        return $this['Router'];
    }

    /**
     * Get a FileLogger instance.
     * 
     * @return \pew\libs\FileLogger The log instance
     */
    public function log()
    {
        if (!isset($this['FileLogger'])) {
            $this['FileLogger'] = new libs\FileLogger('logs', $this['log_level']);
        }

        return $this['FileLogger'];
    }

    /**
     * Get a Session instance.
     * 
     * @return \pew\libs\Session The Session object
     */
    public function session()
    {
        if (!isset($this['Session'])) {
            $this['Session'] = new \pew\libs\Session();
        }

        return $this['Session'];
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
        $view_key = "View_$key";

        if (!isset($this[$view_key])) {
            $prefix = in_array($this['views_folder']{0}, ['/', '\\']) 
                          ? $this['root_folder']
                          : $this['app_folder'];

            $views_folder = $prefix . '/' . trim($this['views_folder'], '/\\');

            $this[$view_key] = new View($views_folder);
        }

        return $this[$view_key];
    }

    /**
     * Store or retrieve a singleton-like instance of an item.
     * 
     * @param string $key A key stored in the registry
     * @param mixed $value The value for the key
     * @return mixed The instance of the item
     */
    public function singleton($key, $value = null)
    {
        if (isSet($value)) {
            $this[$key] = $value;
        } else {
            if (!isSet($this->singleton[$key])) {
                if (!isSet($this[$key])) {
                    throw new \Exception(__CLASS__ . " does not know what to do with the key ${key}");
                }

                $this->singleton[$key] = $this[$key];
            }

            return $this[$key];
        }
    }

    /**
     * Shortcut for Pew::instance()->method().
     * 
     * @param string $name Static method name
     * @param array $arguments Method call argument
     * @return mixed Result of relayed instance call
     */
    public static function __callstatic($name, array $arguments)
    {
        if (method_exists(self::$instance, $name)) {
            return self::$instance->$name($arguments);
        }

        throw new \RuntimeException("No method $name in class " . __CLASS__);
    }
}
