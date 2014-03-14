<?php 

namespace pew\libs;

/**
 * Gathers css and js files and outputs them where requested.
 *
 * Use this class by calling the add_css and add_js static methods in the
 * controllers and the print_css and print_js static methods in the head and
 * before the closing body tag of the layout, respectively. For it to work,
 * css files must be placed in www/css (or a sub-folder) and script files must
 * be in www/js (or a sub-folder).
 * 
 * 16-may-2012: This class is mostly useless; it should be transformed in a
 * tool to automatically concatenate CSS and JS files and maybe also compile
 * LESS files into CSS files (using the less-php library by Leaf Corcoran 
 * (http://leafo.net/lessphp).
 * 
 * @package pew
 * @author ifcanduela <ifcanduela@gmail.com>
 */
class Asset
{
    protected static $asset_types = array('css', 'less', 'js');

    /**
     * Holds the collected Cascading Stylesheet files.
     *
     * @var array
     * @access protected
     */
    protected $css_files = array();
    
    /**
     * Holds the collected LESS stylesheet files.
     *
     * @var array
     * @access protected
     */
    protected $less_files = array();

    /**
     * Holds the collected JavaScript files.
     *
     * @var array
     * @access protected
     */
    protected $js_files = array();
    
    /**
     * Location of CSS files.
     * 
     * @var string
     * @access protected
     */
    protected $css_folder = '';

    /**
     * Location of LESS files.
     * 
     * @var string
     * @access protected
     */
    protected $less_folder;

    /**
     * Location of JS files
     * 
     * @var string
     * @access protected
     */
    protected $js_folder = '';

    /**
     * Less compiler command to process LESS files.
     * 
     * @var string
     * @access  protected
     */
    protected static $_less_command = '';

    /**
     * Initializes an asset collector.
     * 
     * @param string $base_path Base path for css, less and js folders
     */
    public function __construct($base_path = '')
    {
        if ($base_path == '') {
            $base_path = __DIR__;
        }

        $base_path = rtrim($base_path, '/\\') . DIRECTORY_SEPARATOR;

        foreach (self::$_asset_types as $type) {
            $type_folder_name = "_{$type}_folder";
            $this->$type_folder_name = $base_path . $type;
        }
    }

    /**
     * Configures the base folder for the specified file type.
     * 
     * @param string $type File type extension
     * @param [type] $folder Folder for the files of the type
     * @return bool Returns false for unsupported file types
     */
    public function set_folder($type, $folder)
    {
        if (in_array($type, self::$_asset_types)) {
            $type_folder_name = "_{$type}_folder";
            $this->$type_folder_name = $folder;
            return true;
        } else {
            return false;
        }
    }

    /**
     * Gets the current folder for the specified file type.
     * 
     * @param  string $type File type extension
     * @return string|bool Returns false for unsupported file types
     */
    public function get_folder($type)
    {
        if (in_array($type, self::$_asset_types)) {
            $type_folder_name = '_' . $type . '_folder';
            return $this->$type_folder_name;
        } else {
            return false;
        }
    }

    /**
     * Sets the LESS compiler command to use.
     * 
     * The LESS compiler is expected to produce tits output directly to
     * the command line. For example:
     * 
     * shell> lessc $input_file.less > $input_file.css
     * 
     * @param string $cmd Command
     */
    public function set_less_command($cmd)
    {
        self::$_less_command = $cmd;
    }
    
    /**
     * Adds a stylesheet to the assets list
     *
     * @param string $slug the filename without extension of the stylesheet,
     *                     relative to the www/css folder
     * @return boolean true if the file exists, false otherwise
     * @access public
     */
    public function add_css($slug)
    {
        $slug = str_replace($slug, '.css', '');
        
        if (file_exists(root('www' . DS . 'css' . DS . $slug . '.css', false))) {
            if (!in_array($slug, $this->css_files, true)) {
                $this->css_files[] = $slug;
            }
            return true;
        } else {
            return false;
        }
    }
    
    /**
     * Adds a JavaScript file to the assets list
     *
     * @param string $slug The filename without extension of the javascript
     *                     file, relative to the www/css folder
     * @return bool True if the file exists, false otherwise
     * @access public
     */
    public function add_js($slug)
    {
        $slug = str_replace($slug, '.js', '');
        
        if (file_exists(root('www' . DS . 'js' . DS . $slug . '.js', false))) {
            if (!in_array($slug, $this->js_files, true)) {
                $this->js_files[] = $slug;
            }
            return true;
        } else {
            return false;
        }
    }
    
    /**
     * Prints well-formatted LINK elements to include CSS files into the page.
     *
     * @param boolean $print Set to false to retrieve the HTML without printing
     * @return string|void $output The generated code, if $print is false
     * @access public
     */
    public function print_css($print = true)
    {
        $output = '';
        
        foreach ($this->js_files as $key => $value) {
            $output .= '<link rel"stylesheet" href="'
                     . url('www/css/' . $value
                     . '.css', false) . '"> ' . PHP_EOL;
        }
        
        if ($print) {
            echo $output;
        } else {
            return $output;
        }
    }
    
    /**
     * Prints well-formatted SCRIPT elements to include JS files into the page.
     *
     * @param boolean $print Set to false to retrieve the HTML without printing
     * @return string|void $output The generated code, if $print is false
     * @access public
     */
    public function print_js($print = true)
    {
        $output = '';
        
        foreach ($this->js_files as $key => $value) {
            $output .= '<script type="text/javascript" src="'
                     . url('www/js/' . $value
                     . '.js', false) . '"></script> ' . PHP_EOL;
        }
        
        if ($print) {
            echo $output;
        } else {
            return $output;
        }
    }
}
