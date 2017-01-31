<?php
/**
 ******************************************************************************
 *     P E W - P E W - P E W   D E F A U L T   C O N F I G U R A T I O N      *
 *******************************IT**BEGINS**NAO********************************
 */

/**
 * @var array Configuration array.
 */
$cfg = [];

/**
 * @var string Server string. This goes before URL to assemble a full server URL.
 */
if (isset($_SERVER['SERVER_NAME'])) {
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
$cfg['root_folder'] = dirname(getcwd());

/**
 * @var string Full path to the framework folder (filesystem).
 */
$cfg['system_folder'] = dirname(dirname(__FILE__));

/**
 * @var string Full path to the application folder (filesystem).
 */
$cfg['app_folder'] = $cfg['root_folder'] . DIRECTORY_SEPARATOR . 'app';

/**
 * @var string Full path to the public assets folder (filesystem).
 */
$cfg['www_folder'] = getcwd();

/**
 * @var string Full path to the public assets folder (url).
 */
$cfg['www_url'] = $cfg['app_url'];

/**
 * @var string Default namespace for application classes
 */
$cfg['app_namespace'] = 'app';

/**
 * @var boolean Whether the App is running on the localhost space.
 */
$cfg['localhost'] = isset($_SERVER['REMOTE_ADDR'])
                  ? in_array($_SERVER['REMOTE_ADDR'], array('localhost', '127.0.0.1', '::1'))
                  : php_sapi_name() == 'cli';

/**
 * @var boolean Autodetect if the source of the request was an XmlHttpRequest
 */
$cfg['request_is_ajax'] = isset($_SERVER['HTTP_X_REQUESTED_WITH'])
                        ? strToLower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
                        : false;

/**
 * @var boolean Use the automatically-detected AJAX flag.
 */
$cfg['autodetect_ajax'] = true;

/**
 * @var string Name of the session group key
 */
$cfg['session_group'] = basename($cfg['root_folder']);

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
 * @var string Save location for log files
 */
$cfg['log_dir'] = '../logs';

/**
 * @var string Default folder names for files.
 *
 * This is used for both the Pew folder and the App folder
 */
$cfg['models_folder'] = 'models';
$cfg['controllers_folder'] = 'controllers';
$cfg['views_folder'] = 'views';
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
$cfg['default_routes'] = [
    ['/:controller/:action', "/!controller/!action",                                   'get post'],
    ['/:controller',         "/!controller/{$cfg['default_action']}",                  'get post'],
    ['/',                    "/{$cfg['default_controller']}/{$cfg['default_action']}", 'get post'],
];

/**
 * @var string Route capturing group indicator for named arguments.
 */
$cfg['router_token_prefix'] = '!';

/**
 * @var array Route capturing group indicator for remainder arguments.
 */
$cfg['router_sequence_prefix'] = '*';

return $cfg;
