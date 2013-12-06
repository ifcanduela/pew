<?php

namespace pew;

use \pew\libs\Registry;

class ViewException extends \Exception {}
class ViewTemplateNotFoundException extends ViewException {}
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
    protected $folder = '';

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
    protected $layout= 'default.layout';

    /**
     * Templates file extension.
     * 
     * @var string
     */
    protected $extension = '.php';
    
    /**
     * Creates a View object based on a folder.
     *
     * If no folder is provided, the current working directory is used.
     */
    public function __construct($templates_folder = null)
    {
        $this->pew = Pew::instance();

        if (is_null($templates_folder)) {
            $templates_folder = getcwd();
        }

        $this->folder($templates_folder);
    }

    /**
     * Renders a view according to the request info
     *
     * @param type $data Template data
     * @param type $view View to render
     */
    public function render($template = null, $data = array())
    {
        if (!$template) {
            $template = $this->template;
        }

        # Get the view file
        $template_file = $this->folder() . DIRECTORY_SEPARATOR . $template . $this->extension();

        if (!file_exists($template_file)) {
            throw new ViewTemplateNotFoundException("Template {$template_file} not found");
        }

        $view_data = array_merge($this->export(), $data);
        extract($view_data);

        # Output the view and save it into a buffer.
        ob_start();
        require $template_file;
        $template_output = ob_get_clean();
        
        return $template_output;
    }
    
    /**
     * Check if a template file exists in the templates folder.
     * 
     * @param string $template Base file name (without extension)
     * @return bool True if the file can be read, false otherwise
     */
    public function exists($template = null)
    {
        if (is_null($template)) {
            $template = $this->template;
        }

        return file_exists($this->folder() . DIRECTORY_SEPARATOR . $template . $this->extension());
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
            $this->folder = rtrim($folder, '\\/');
        }

        return rtrim($this->folder, '\\/');
    }

    /**
     * Set and get the templates to render.
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
            $this->title = $title;
        }

        return $this->title;
    }

    /**
     * Load and output a snippet into the current view.
     * 
     * @param string $element The snippet to be loaded, relative to the templates folder
     * @param array $element_data Additional variables for use in the element
     * @return void
     */
    public function element($element, $element_data = null)
    {
        $element_file = $this->folder() . '/' . $element . $this->extension();
        
        # If the element .php file cannot be found, show an error page.
        if (!file_exists($element_file)) {
            throw new ViewElementFileNotFoundException("The element file $element_file could not be found.");
        }

        # If there are variables, make them easily available to the template.
        if (is_array($element_data)) {
            extract($element_data);
        } elseif (count(func_get_args()) > 1) {
            $args = array_slice(func_get_args(), 1);
            extract($args, EXTR_PREFIX_ALL, 'param');
        }
        
        # Render the element.
        require $element_file;
    }
}
