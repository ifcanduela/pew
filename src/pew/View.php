<?php

namespace pew;

use SplStack;

/**
 * This class encapsulates the template rendering functionality.
 * 
 * @package pew
 * @author ifcanduela <ifcanduela@gmail.com>
 */
class View implements \ArrayAccess
{
    /**
     * @var boolean Render the view or not
     */
    public $render = true;

    /**
     * @var SplStack Base templates directory
     */
    protected $folderStack;

    /**
     * @var string Template name
     */
    protected $template = 'index';

    /**
     * @var string Layout name
     */
    protected $layout = '';

    /**
     * @var string View title
     */
    protected $title;

    /**
     * @var string Templates file extension
     */
    protected $extension = '.php';

    /**
     * @var string Result of rendering the view
     */
    protected $output = '';

    /**
     * @var array Rendered partial blocks
     */
    protected $blocks = [];

    /**
     * @var SplStack Stack of block names
     */
    protected $blockStack;

    /**
     * @var array
     */
    protected $variables = [];

    /**
     * Creates a View object based on a folder.
     *
     * If no folder is provided, the current working directory is used.
     */
    public function __construct($templates_folder = null)
    {
        $this->blockStack = new SplStack;
        $this->folderStack = new SplStack;

        if (is_null($templates_folder)) {
            $templates_folder = getcwd();
        }

        $this->addFolder($templates_folder);
    }

    /**
     * Renders a view according to the request info
     *
     * @param type $data Template data
     * @param type $view View to render
     */
    public function render($data = [], $template = null)
    {
        if (!$template) {
            $template = $this->template;
        }

        if (!is_array($data)) {
            $data = [$data];
        }

        # Get the view file
        $template_file = $this->resolve($template . $this->extension());

        if ($template_file === false) {
            throw new \RuntimeException("Template {$template} not found");
        }

        $view_data = array_merge($this->variables, $data);
        $this->output = $output = $view_data['output'] = $this->_render($template_file, $view_data);

        if ($this->layout && $this->layout !== 'none') {
            $layout_file = $this->resolve($this->layout . $this->extension());

            if ($layout_file === false) {
                throw new \RuntimeException("Layout {$this->layout} not found");
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
    protected function addFolder($folder)
    {
        $this->folderStack->push(rtrim($folder, '\\/'));
    }

    /**
     * Find a template in the folder stack.
     *
     * @param string $template_file Template file name and extension
     * @return string|bool The location of the template, or false
     */
    protected function resolve($template_file)
    {
        foreach ($this->folderStack as $folder) {
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
            $this->folderStack->push($folder);
        }

        return $this->folderStack->top();
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
            $this->title = $title;
        }

        return $this->title;
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
     * Load and render another view into the current view.
     *
     * Elements only inherit view data set with __set(), accesible via $this->{key}.
     *
     * @param string $element The snippet to be loaded, relative to the templates folder
     * @param array $element_data Additional variables for use in the element
     * @return void
     */
    public function insert($element, $element_data = [])
    {
        $element_file = $this->resolve($element . $this->extension());

        if ($element_file === false) {
            throw new \RuntimeException("The element file $element could not be found.");
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

        try {
            require func_get_arg(0);
            return ob_get_clean();
        } catch (\Exception $e) {
            ob_end_clean();
            throw $e;
        }
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
        $cache = Pew::instance()->file_cache;

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
        $cache = Pew::instance()->file_cache;

        $output = ob_end_clean();
        $cache->save($key, $output);

        echo $output;
    }

    /**
     * Inserts a previously-rendered block.
     *
     * @param string $name
     * @return string
     */
    public function block($name)
    {
        if (array_key_exists($name, $this->blocks)) {
            return join('', $this->blocks[$name]);
        }

        return '';
    }

    /**
     * Starts a block.
     *
     * @param string $block_name
     */
    public function beginBlock($block_name)
    {
        $this->blockStack->push($block_name);
        ob_start();
    }

    /**
     * Closes the current block.
     */
    public function endBlock()
    {
        $output = ob_get_clean();

        $block_name = $this->blockStack->pop();

        if (!array_key_exists($block_name, $this->blocks)) {
            $this->blocks[$block_name] = [];
        }

        $this->blocks[$block_name][] = $output;
    }


    public function offsetGet($key)
    {
        return $this->variables[$key];
    }


    public function offsetSet($key, $value)
    {
        return $this->variables[$key] = $value;
    }

    public function offsetExists($key)
    {
        return isset($this->variables[$key]);
    }

    public function offsetUnset($key)
    {
        unset($this->variables[$key]);
    }
}
