<?php

namespace pew;

use SplStack;
use pew\lib\FileCache;

/**
 * This class encapsulates the template rendering functionality.
 */
class View implements \ArrayAccess
{
    /** @var boolean */
    public $render = true;

    /** @var SplStack Base templates directory */
    protected $folderStack;

    /** @var string Template name */
    protected $template = 'index';

    /** @var string Layout name */
    protected $layout = false;

    /** @var string View title */
    protected $title;

    /** @var string Templates file extension */
    protected $extension = '.php';

    /** @var string Result of rendering the view */
    protected $output = '';

    /** @var array Rendered partial blocks */
    protected $blocks = [];

    /** @var SplStack Stack of block names */
    protected $blockStack;

    /** @var array */
    protected $variables = [];

    /** @var FileCache */
    protected $fileCache;

    /**
     * Creates a View object based on a folder.
     *
     * If no folder is provided, the current working directory is used.
     *
     * @param string $templates_folder
     * @param FileCache $file_cache
     */
    public function __construct(string $templates_folder = null, FileCache $file_cache = null)
    {
        $this->blockStack = new SplStack();
        $this->folderStack = new SplStack();
        $this->fileCache = $file_cache;

        if ($templates_folder === null) {
            $templates_folder = getcwd();
        }

        $this->addFolder($templates_folder);
    }

    /**
     * Set the value of a template variable.
     *
     * @param string $key
     * @param mixed $value
     * @return mixed
     */
    public function set(string $key, $value)
    {
        $this->variables[$key] = $value;
    }

    /**
     * Get the value of a template variable.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get(string $key, $default = null)
    {
        return $this->variables[$key] ?? $default;
    }

    /**
     * Check if a template variable has been set.
     *
     * @param string $key
     * @return bool
     */
    public function has(string $key)
    {
        return array_key_exists($this->variables, $key);
    }

    /**
     * Renders a view according to the request info.
     *
     * Template names are resolved using the configured template directories and template file extension.
     *
     * @param string $template Template name, relative to one of the template directories.
     * @param array $data Template data
     * @return string
     * @throws \Exception
     * @throws \RuntimeException
     */
    public function render(string $template = null, array $data = [])
    {
        if (!$template) {
            $template = $this->template;
        }

        if ($template === null) {
            throw new \RuntimeException("No template specified");
        }

        # find the template file
        $template_file = $this->resolve($template);

        if ($template_file === false) {
            throw new \RuntimeException("Template {$template} not found");
        }

        # make previous and received variables available using the index operator
        $this->variables = array_merge($this->variables, $data);

        $this->output
            = $output
            = $view_data['output']
            = $this->_render($template_file, $this->variables);

        if ($this->layout) {
            $layout_file = $this->resolve($this->layout);

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
    public function exists(string $template = null)
    {
        if ($template === null) {
            if ($this->template === null) {
                throw new \RuntimeException("No template specified");
            }
            
            $template = $this->template;
        }

        $exists = $this->resolve($template);

        return $exists !== false;
    }

    /**
     * Add a template folder to the current stack.
     *
     * @param string $folder Folder location
     * @return self
     */
    protected function addFolder(string $folder)
    {
        $this->folderStack->push(rtrim($folder, '\\/'));

        return $this;
    }

    /**
     * Find a template in the folder stack.
     *
     * @param string $template_file Template file name and extension
     * @return string|bool The location of the template, or false
     */
    protected function resolve(string $template_file)
    {
        $template_file_name = $template_file . $this->extension();

        foreach ($this->folderStack as $folder) {
            if (file_exists($folder . DIRECTORY_SEPARATOR . $template_file_name)) {
                return $folder . DIRECTORY_SEPARATOR . $template_file_name;
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
     * @return self|string Folder where templates should be located
     */
    public function folder(string $folder = null)
    {
        if ($folder !== null) {
            $this->folderStack->push($folder);

            return $this;
        }

        return $this->folderStack->top();
    }

    /**
     * Set and get the template to render.
     *
     * @param string $template Name of the template
     * @return self|string Name of the template
     */
    public function template(string $template = null)
    {
        if ($template !== null) {
            $this->template = $template;

            return $this;
        }

        return $this->template;
    }

    /**
     * Set and get the view file extension.
     *
     * @param string $extension View file extension
     * @return self|string View file extension
     */
    public function extension(string $extension = null)
    {
        if ($extension !== null) {
            $this->extension = '.' . ltrim($extension, '.');
            return $this;
        }

        return '.' . ltrim($this->extension, '.');
    }

    /**
     * Set and get the layout to use.
     *
     * @param string $layout Name of the layout
     * @return self|string Name of the layout
     */
    public function layout(string $layout = null)
    {
        if ($layout !== null) {
            $this->layout = $layout;

            return $this;
        }

        return $this->layout;
    }

    /**
     * Set and get the view title.
     *
     * @param string $title The title of the view
     * @return self|string The title of the view
     */
    public function title(string $title = null)
    {
        if ($title !== null) {
            $this->title = $title;

            return $this;
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
     * Elements only inherit view data set with __set(), accessible via $this->{key}.
     *
     * @param string $template The snippet to be loaded, relative to the templates folder
     * @param array $data Additional variables for use in the partial template
     * @return string
     * @throws \Exception
     * @throws \RuntimeException
     */
    public function insert(string $template, array $data = [])
    {
        $element_file = $this->resolve($template);

        if ($element_file === false) {
            throw new \RuntimeException("The partial template file $template could not be found.");
        }

        # Render the element.
        return $this->_render($element_file, $data);
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
     * @throws \Exception
     */
    protected function _render()
    {
        extract(func_get_arg(1), EXTR_PREFIX_INVALID, 'v_');
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
     * @param int $duration Time to live of the cache fragment, in seconds
     * @param bool $open_buffer Set to false to prevent the opening of a buffer
     * @return bool True if the cached fragment could be inserted, false otherwise
     * @throws \RuntimeException
     */
    public function load(string $key, int $duration, bool $open_buffer = true)
    {
        if ($this->fileCache) {
            if ($this->fileCache->cached($key, $duration)) {
                $fragment = $this->fileCache->load($key);
                echo $fragment;

                return true;
            }

            if ($open_buffer) {
                ob_start();
            }
        }

        return false;
    }

    /**
     * Save a fragment to the cache.
     *
     * @param string $key Name key for the cached fragment
     * @return string
     */
    public function save(string $key)
    {
        if ($this->fileCache) {
            # save the output into a cache key
            $output = ob_end_clean();
            $this->fileCache->save($key, $output);

            echo $output;
        }

        return null;
    }

    /**
     * Checks whether or not a block has been defined.
     *
     * @param string $name
     * @return bool
     */
    public function hasBlock(string $name)
    {
        return array_key_exists($name, $this->blocks);
    }

    /**
     * Inserts a previously-rendered block.
     *
     * @param string $name
     * @return string
     */
    public function block(string $name)
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
     * @param bool $replace
     */
    public function beginBlock(string $block_name, bool $replace = false)
    {
        $this->blockStack->push([$block_name, $replace]);
        ob_start();
    }

    /**
     * Closes the current block.
     */
    public function endBlock()
    {
        $output = ob_get_clean();

        list($block_name, $replace) = $this->blockStack->pop();

        if (!array_key_exists($block_name, $this->blocks)) {
            $this->blocks[$block_name] = [];
        }

        if ($replace) {
            $this->blocks[$block_name] = [$output];
        } else {
            $this->blocks[$block_name][] = $output;
        }
    }

    /**
     * Escape an input string.
     *
     * The value is escaped using `htmlspecialchars` with ENT_QUOTES enabled
     * and UTF8 encoding.
     *
     * @param string $value
     * @return string
     */
    public function escape($value)
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Get the value of a template variable.
     *
     * @param string $key
     * @return mixed
     */
    public function offsetGet($key)
    {
        return $this->get($key);
    }

    /**
     * Set the value of a template variable.
     *
     * @param string $key
     * @param mixed $value
     * @return mixed
     */
    public function offsetSet($key, $value)
    {
        return $this->set($key, $value);
    }

    /**
     * Check if a template variable was set.
     *
     * @param string $key
     * @return bool
     */
    public function offsetExists($key)
    {
        return array_key_exists($key, $this->variables);
    }

    /**
     * Unset a template variable.
     *
     * @param string $key
     */
    public function offsetUnset($key)
    {
        unset($this->variables[$key]);
    }
}
