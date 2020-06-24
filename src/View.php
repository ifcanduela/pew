<?php

namespace pew;

use ifcanduela\events\CanEmitEvents;
use SplStack;
use Stringy\Stringy as S;

/**
 * This class encapsulates the template rendering functionality.
 */
class View
{
    use CanEmitEvents;

    /** @var SplStack Base templates directory */
    protected $folderStack;

    /** @var string Template name */
    protected $template = "";

    /** @var string Layout name */
    protected $layout = "";

    /** @var string View title */
    protected $title = "";

    /** @var string Templates file extension */
    protected $extension = ".php";

    /** @var string Result of rendering the view */
    protected $output = "";

    /** @var array Rendered partial blocks */
    protected $blocks = [];

    /** @var SplStack Stack of block names */
    protected $blockStack;

    /** @var array */
    protected $variables = [];

    /**
     * Creates a View object based on a folder.
     *
     * If no folder is provided, the current working directory is used.
     *
     * @param string $templatesFolder
     * @param SymfonyResponse $response
     */
    public function __construct(string $templatesFolder = "")
    {
        $this->blockStack = new SplStack();
        $this->folderStack = new SplStack();

        $this->addFolder($templatesFolder ?: getcwd());
    }

    /**
     * Set the value of a template variable.
     *
     * @param string $key
     * @param mixed $value
     * @return self
     */
    public function set(string $key, $value)
    {
        $this->variables[$key] = $value;

        return $this;
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
     * Set data for the template.
     *
     * @param array $data
     * @return self
     */
    public function setData(array $data)
    {
        $this->variables = array_replace($this->variables, $data);

        return $this;
    }

    /**
     * Get the current data for the template.
     *
     * @return array
     */
    public function getData()
    {
        return $this->variables;
    }

    /**
     * Check if a template variable has been set.
     *
     * @param string $key
     * @return bool
     */
    public function has(string $key)
    {
        return array_key_exists($key, $this->variables);
    }

    /**
     * Renders a view according to the request info.
     *
     * Template names are resolved using the configured template directories and template file extension.
     *
     * @param null|string|array $template Template name, relative to one of the template directories.
     * @param array $data Template data
     * @return string
     * @throws \Exception
     * @throws \RuntimeException
     */
    public function render($template = "", array $data = [])
    {
        if (count(func_get_args()) === 1) {
            if (is_array($template)) {
                $data = $template;
                $template = null;
            }
        }

        if (!$template) {
            if (!$this->template) {
                throw new \RuntimeException("No template specified");
            }

            $template = $this->template;
        }

        # find the template file
        $templateFile = $this->resolve($template);

        if ($templateFile === false) {
            throw new \RuntimeException("Template `{$template}` not found");
        }

        # make previous and received variables available using the index operator
        $this->variables = array_merge($this->variables, $data);
        $this->output = $output = $this->renderFile($templateFile, $this->variables);

        if ($this->layout) {
            $layoutFile = $this->resolve($this->layout);

            if ($layoutFile === false) {
                throw new \RuntimeException("Layout `{$this->layout}` not found");
            }

            $this->output = $this->renderFile($layoutFile, ["output" => $output]);
        }

        return $this->output;
    }

    /**
     * Check if a template file exists.
     *
     * @param string $template Base file name (without extension)
     * @return bool True if the file can be read, false otherwise
     */
    public function exists(string $template = "")
    {
        if (!$template) {
            if (!$this->template) {
                throw new \RuntimeException("No template specified");
            }

            $template = $this->template;
        }

        $exists = $this->resolve($template);

        return $exists !== false;
    }

    /**
     * Add a template folder to the top of the stack.
     *
     * @param string $folder Folder location
     * @return void
     */
    public function addFolder(string $folder)
    {
        $this->folder($folder);
    }

    /**
     * Find a template in the folder stack.
     *
     * @param string $templateFile Template file name and extension
     * @return string|bool The location of the template, or false
     */
    protected function resolve(string $templateFile)
    {
        $templateFileName = S::create($templateFile)->ensureRight($this->extension());

        foreach ($this->folderStack as $folder) {
            if (file_exists($folder . DIRECTORY_SEPARATOR . $templateFileName)) {
                return realpath($folder . DIRECTORY_SEPARATOR . $templateFileName);
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
    public function folder(string $folder = "")
    {
        if ($folder) {
            $this->folderStack->push(rtrim($folder, "\\/"));

            return $this;
        }

        return $this->folderStack->top();
    }

    /**
     * Set or get the template to render.
     *
     * @param string $template Name of the template
     * @return self|string Name of the template
     */
    public function template(string $template = "")
    {
        if ($template) {
            $this->template = $template;

            return $this;
        }

        return $this->template;
    }

    /**
     * Set or get the view file extension.
     *
     * @param string $extension View file extension
     * @return self|string View file extension
     */
    public function extension(string $extension = "")
    {
        if ($extension) {
            $this->extension = S::create($extension)->ensureLeft(".");

            return $this;
        }

        return "." . ltrim($this->extension, ".");
    }

    /**
     * Set or get the layout to use.
     *
     * Use `$view->layout(false)` or `$view->layout("")` to disable layout rendering.
     *
     * @param string|bool|null $layout Name of the layout, or `false` to disable.
     * @return self|string Name of the layout
     */
    public function layout($layout = null)
    {
        if ($layout !== null) {
            $this->layout = (string) $layout;

            return $this;
        }

        return $this->layout;
    }

    /**
     * Do not wrap the template in a layout.
     *
     * @return self
     */
    public function noLayout()
    {
        $this->layout = "";

        return $this;
    }

    /**
     * Set and get the view title.
     *
     * @param string|null $title The title of the view
     * @return self|string The title of the view
     */
    public function title($title = null)
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
    public function content()
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
        $templateFile = $this->resolve($template);

        if ($templateFile === false) {
            throw new \RuntimeException("Partial template `{$template}` not found");
        }

        # Render the element.
        return $this->renderFile($templateFile, array_replace($this->variables, $data));
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
    protected function renderFile()
    {
        $this->emit("view.render", func_get_args());

        extract(func_get_arg(1), EXTR_PREFIX_INVALID, "v_");
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
            return join("", $this->blocks[$name]);
        }

        return "";
    }

    /**
     * Starts a block.
     *
     * @param string $blockName
     * @param bool $replace
     * @return void
     */
    public function beginBlock(string $blockName, bool $replace = false)
    {
        $this->blockStack->push([$blockName, $replace]);
        ob_start();
    }

    /**
     * Closes the current block.
     *
     * @return void
     */
    public function endBlock()
    {
        $output = ob_get_clean();

        list($blockName, $replace) = $this->blockStack->pop();

        if (!array_key_exists($blockName, $this->blocks)) {
            $this->blocks[$blockName] = [];
        }

        if ($replace) {
            $this->blocks[$blockName] = [$output];
        } else {
            $this->blocks[$blockName][] = $output;
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
        return htmlspecialchars($value, ENT_QUOTES, "UTF-8");
    }
}
