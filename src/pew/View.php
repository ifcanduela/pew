<?php

namespace pew;

use \pew\libs\Registry;
use \pew\libs\FileCache;

class ViewException extends \Exception {}
class ViewTemplateNotFoundException extends ViewException {}
class ViewLayoutNotFoundException extends ViewException {}
class ViewElementFileNotFoundException extends ViewTemplateNotFoundException {}

/**
 * This class encapsulates the view rendering functionality.
 * 
 * @package pew
 * @author ifcanduela <ifcanduela@gmail.com>
 */
class View extends \pew\libs\Registry
{
    /**
     * Render the view or not.
     * 
     * @var boolean
     */
    public $render = true;
    
    /**
     * Base templates directory.
     * 
     * @var string
     */
    protected $folder_stack = [];

    /**
     * Template name.
     * 
     * @var string
     */
    protected $template = 'index';
    
    /**
     * Layout name.
     * 
     * @var string
     */
    protected $layout= '';

    /**
     * Templates file extension.
     * 
     * @var string
     */
    protected $extension = '.php';

    /**
     * Result of rendering the view.
     * 
     * @var string
     */
    protected $output = '';
    
    /**
     * Creates a View object based on a folder.
     *
     * If no folder is provided, the current working directory is used.
     */
    public function __construct($templates_folder = null)
    {
        if (is_null($templates_folder)) {
            $templates_folder = getcwd();
        }

        $this->add_folder($templates_folder);
    }

    /**
     * Renders a view according to the request info
     *
     * @param type $data Template data
     * @param type $view View to render
     */
    public function render(array $data = array(), $template = null)
    {
        if (!$template) {
            $template = $this->template;
        }
        
        # Get the view file
        $template_file = $this->resolve($template . $this->extension());

        if ($template_file === false) {
            throw new ViewTemplateNotFoundException("Template {$template} not found");
        }

        $view_data = array_merge($this->export(), $data);
        $this->output = $output = $view_data['output'] = $this->_render($template_file, $view_data);

        if ($this->layout && $this->layout !== 'none') {
            $layout_file = $this->resolve($this->layout . $this->extension());
            
            if ($layout_file === false) {
                throw new ViewLayoutNotFoundException("Layout {$this->layout} not found");
            }

            $output = $this->_render($layout_file, $view_data);
        }

        return $output;
    }
    
    /**
     * Check if a template file exists.
     * 
     * @param string $template Base file name (without extension)
     * @return bool True if the file can be read, false otherwise
     */
    public function exists($template = null)
    {
        if (is_null($template)) {
            $template = $this->template;
        }

        $exists = $this->resolve($template . $this->extension());
        
        return $exists !== false;
    }

    /**
     * Add a template folder to the current stack.
     * 
     * @param string $folder Folder location
     */
    protected function add_folder($folder)
    {
        array_unshift($this->folder_stack, rtrim($folder, '\\/'));
    }

    /**
     * Find a template in the folder stack.
     * 
     * @param string $template_file Template file name and extension
     * @return tring|bool The location of the template, or false
     */
    protected function resolve($template_file)
    {
        foreach ($this->folder_stack as $index => $folder) {
            if (file_exists($folder . '/' . $template_file)) {
                return $folder . '/' . $template_file;
            }
        }
        
        return false;
    }

    /**
     * Set and get the templates folder.
     *
     * Always includes a trailing slash (OS-dependent)
     * 
     * @param string $folder Folder where templates should be located
     * @return string Folder where templates should be located
     */
    public function folder($folder = null)
    {
        if (!is_null($folder)) {
            $this->add_folder($folder);
        }

        return $this->folder_stack[0];
    }

    /**
     * Set and get the template to render.
     * 
     * @param string $template Name of the template
     * @return string Name of the template
     */
    public function template($template = null)
    {
        if (!is_null($template)) {
            $this->template = $template;
        }

        return $this->template;
    }

    /**
     * Set and get the view file extension.
     * 
     * @param string $extension View file extension
     * @return string View file extension
     */
    public function extension($extension = null)
    {
        if (!is_null($extension)) {
            $this->extension = '.' . ltrim($extension, '.');
        }

        return '.' . ltrim($this->extension, '.');
    }

    /**
     * Set and get the layout to use.
     * 
     * @param string $layout Name of the layout
     * @return string Name of the layout
     */
    public function layout($layout = null)
    {
        if (!is_null($layout)) {
            $this->layout = $layout;
        }

        return $this->layout;
    }

    /**
     * Set and get the view title.
     * 
     * @param string $title The title of the view
     * @return string The title of the view
     */
    public function title($title = null)
    {
        if (!is_null($title)) {
            $this['title'] = $title;
        }

        return $this['title'];
    }

    /**
     * Get the output of the previous render call.
     * 
     * @return string View output
     */
    public function child()
    {
        return $this->output;
    }

    /**
     * Load and output a snippet into the current view.
     *
     * Elements only inherit view data set with __set(), accesible via $this->{key}.
     * 
     * @param string $element The snippet to be loaded, relative to the templates folder
     * @param array $element_data Additional variables for use in the element
     * @return void
     */
    public function element($element, $element_data = [])
    {
        $element_file = $this->resolve($element . $this->extension());

        if ($element_file === false) {
            throw new ViewElementFileNotFoundException("The element file $element could not be found.");
        }
        
        # Render the element.
        return $this->_render($element_file, $element_data);
    }

    /**
     * Import and process a template file.
     *
     * This method encapsulates the replacement of template variables, avoiding the 
     * creation of extra variables in its scope.
     *
     * @param string $filename Template file name
     * @param array $data Template data
     * @return string
     */
    protected function _render()
    {
        extract(func_get_arg(1));

        ob_start();
            require func_get_arg(0);
        return ob_get_clean();
    }

    /**
     * Attempt to insert a cached fragment into the view.
     *
     * This method automatically inserts the cached fragment if it's found and
     * then returns TRUE. If the return value is FALSE, the cached fragment was 
     * not found.
     * 
     * @param string $key Name key of the cached fragment to load
     * @param string $open_buffer Set to false to prevent the opening of a buffer
     * @return bool True if the cached fragment could be inserted, false otherwise.
     */
    public function load($key, $duration, $open_buffer = true)
    {
        $cache = Pew::instance()->singleton('file_cache');

        if ($cache->cached($key, $duration)) {
            $fragment = $cache->load($key);
            echo $fragment;
            return true;
        }

        if ($open_buffer) {
            ob_start();
        }

        return false;
    }

    /**
     * Save a fragment to the cache.
     * 
     * @param string $key Name key for the cached fragment
     */
    public function save($key)
    {
        # save the output into a cache key
        $cache = Pew::instance()->singleton('file_cache');

        $output = ob_end_clean();
        $cache->save($key, $output);

        echo $output;
    }
}