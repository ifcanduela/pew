<?php

namespace pew;

use SplStack;
use pew\libs\FileCache;

/**
 * This class encapsulates the template rendering functionality.
 *
 * @author ifcanduela <ifcanduela@gmail.com>
 */
class View implements \ArrayAccess
{
    /**
     * @var bool Render the view or not
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
    protected $layout = false;

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
    public function __construct(string $templates_folder = null, FileCache $file_cache = null)
    {
        $this->blockStack = new SplStack();
        $this->folderStack = new SplStack();
        $this->fileCache = $file_cache;

        if (is_null($templates_folder)) {
            $templates_folder = getcwd();
        }

        $this->addFolder($templates_folder);
    }

    /**
     * Renders a view according to the request info.
     *
     * @param type $data Template data
     * @param type $view View to render
     */
    public function render(array $data = [], string $template = null): string
    {
        if (!$template) {
            $template = $this->template;
        }

        if (!is_array($data)) {
            $data = [$data];
        }

        # Get the view file
        $template_file = $this->resolve($template);

        if ($template_file === false) {
            throw new \RuntimeException("Template {$template} not found");
        }

        $view_data = array_merge($this->variables, $data);
        $this->output
            = $output
            = $view_data['output']
            = $this->_render($template_file, $view_data);

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
    public function exists(string $template = null): bool
    {
        if (is_null($template)) {
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
    protected function addFolder(string $folder): self
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
        if (!is_null($folder)) {
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
        if (!is_null($template)) {
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
        if (!is_null($extension)) {
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
        if (!is_null($layout)) {
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
        if (!is_null($title)) {
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
    public function child(): string
    {
        return $this->output;
    }

    /**
     * Load and render another view into the current view.
     *
     * Elements only inherit view data set with __set(), accesible via $this->{key}.
     *
     * @param string $template The snippet to be loaded, relative to the templates folder
     * @param array $data Additional variables for use in the partial tempalte
     * @return void
     */
    public function insert(string $template, array $data = []): string
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
     * @param int $duration Time to live of the cache fragment, in seconds
     * @param string $open_buffer Set to false to prevent the opening of a buffer
     * @return bool True if the cached fragment could be inserted, false otherwise
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
    public function hasBlock(string $name): bool
    {
        return array_key_exists($name, $this->blocks);
    }

    /**
     * Inserts a previously-rendered block.
     *
     * @param string $name
     * @return string
     */
    public function block(string $name): string
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
     * The value is escaped using htmlspecialchars with ENT_QUOTES enabled
     * and UTF8 encoding.
     *
     * @param string $value
     * @return string
     */
    public function escape($value): string
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
        return $this->variables[$key];
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
        return $this->variables[$key] = $value;
    }

    /**
     * Check if a template variable was set.
     *
     * @param string $key
     * @return bool
     */
    public function offsetExists($key)
    {
        return isset($this->variables[$key]);
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
