<?php

/**
 ******************************************************************************
 *     P E W - P E W - P E W   D E F A U L T   C O N F I G U R A T I O N      *
 *******************************IT**BEGINS**NAO********************************
 *
 * @package pew
 */

/**
 * @var array Configuration array.
 */
$cfg = [];

/**
 * @var string Server string. This goes before URL to assemble a full server URL.
 */
if (isSet($_SERVER['SERVER_NAME'])) {
    $cfg['host'] = (empty($_SERVER['HTTPS']) ? 'http://' : 'https://') 
                 . $_SERVER['SERVER_NAME']
                 . ($_SERVER['SERVER_PORT'] != 80 ? ':' . $_SERVER['SERVER_PORT'] : '');
} else {
    $cfg['host'] = php_sapi_name();
}

/**
 * @var string Root path (url), '/' in case of root installation.
 */
$cfg['path'] = str_replace(basename($_SERVER['SCRIPT_NAME']), '', $_SERVER['SCRIPT_NAME']);

/**
 * @var string Full path (url) to the app, including server name and folder path.
 */
$cfg['app_url'] = $cfg['host'] . $cfg['path'];

/**
 * @var string Full path to the base folder (filesystem).
 */
$cfg['root_folder'] = getcwd();

/**
 * @var string Full path to the framework folder (filesystem).
 */
$cfg['system_folder'] = dirname(__FILE__);

/**
 * @var string Full path to the application folder (filesystem).
 */
$cfg['app_folder'] = getcwd() . DIRECTORY_SEPARATOR . 'app';

/**
 * @var string Full path to the public assets folder (filesystem).
 */
$cfg['www_folder'] = getcwd() . DIRECTORY_SEPARATOR . 'www';

/**
 * @var string Full path to the public assets folder (url).
 */
$cfg['www_url'] = $cfg['app_url'] . 'www/';

/**
 * @var default namespace for application classes
 */
$cfg['app_namespace'] = 'app';

/**
 * @var string Framework version numbers.
 */
$cfg['version_major'] = '0';
$cfg['version_minor'] = '2';
$cfg['version_patch'] = '1';
$cfg['version_date'] = '2013-04-16';
$cfg['version_string'] = "{$cfg['version_major']}.{$cfg['version_minor']}.{$cfg['version_patch']} ({$cfg['version_date']})";

/**
 * @var boolean Whether the App is running on the localhost space.
 */
$cfg['localhost'] = isSet($_SERVER['REMOTE_ADDR']) 
                  ? in_array($_SERVER['REMOTE_ADDR'], array('localhost', '127.0.0.1', '::1')) 
                  : php_sapi_name() == 'cli';

/**
 * @var string Option to use a prefix for action method names in controllers.
 */
$cfg['action_prefix'] = '';

/**
 * @var boolean Define DEBUG if it's not defined in app/config 
 */
$cfg['debug'] = false;

/**
 * @var integer Logging level -- the higher, the more things get logged
 */
$cfg['log_level'] = 0;

/**
 * @var string Default folder names for files.
 *
 * This is used for both the Pew folder and the App folder
 */
$cfg['models_folder'] = 'models';
$cfg['controllers_folder'] = 'controllers';
$cfg['views_folder'] = 'views';
$cfg['elements_folder'] = 'views' . DIRECTORY_SEPARATOR . 'elements';
$cfg['layouts_folder'] = 'views';
$cfg['libraries_folder'] = 'libs';

/**
 * @var string Default file extensions for files.
 * 
 * The values must include the first period (i.e., '.php')
 */
$cfg['view_ext'] = '.php';
$cfg['element_ext'] = '.php';
$cfg['layout_ext'] = '.php';
$cfg['class_ext'] = '.php';

/**
 * @var string Default controller to use if none is specified (slug)
 */
$cfg['default_controller'] = 'pages';

/**
 * @var string Default action to use if none is specified (no prefix)
 */
$cfg['default_action'] = 'index';

/**
 * @var string Default layout to use if none is specified (no extension)
 */
$cfg['default_layout'] = 'default.layout';

/**
 * @var array Configured routes.
 */
$cfg['routes'] = [
    ['/:controller/:action', "/:controller/:action",                                   'get post'],
    ['/:controller',         "/:controller/{$cfg['default_action']}",                  'get post'],
    ['/',                    "/{$cfg['default_controller']}/{$cfg['default_action']}", 'get post'],
];

/**
 * Base URL of the application (the location of index.php).
 */
define('APP_URL', $cfg['app_url']);

/**
 * Path to the Pew-Pew-Pew files.
 */
define('PEW_PATH', $cfg['system_folder']);

/**
 * Full path to the base folder (filesystem).
 */
define('BASE_PATH', $cfg['root_folder']);

return $cfg;
