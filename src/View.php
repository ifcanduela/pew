<?php

declare(strict_types=1);

namespace pew;

use Exception;
use RuntimeException;
use SplStack;
use ifcanduela\events\CanEmitEvents;

/**
 * This class encapsulates the template rendering functionality.
 */
class View
{
    use CanEmitEvents;

    /** @var SplStack<string> Base templates directory */
    protected SplStack $folderStack;

    /** @var string Template name */
    protected string $template = "";

    /** @var string Layout name */
    protected string $layout = "";

    /** @var string View title */
    protected string $title = "";

    /** @var string Templates file extension */
    protected string $extension = ".php";

    /** @var SplStack<string> Result of rendering the view */
    protected SplStack $outputStack;

    /** @var array Rendered partial blocks */
    protected array $blocks = [];

    /** @var SplStack<array> Stack of blocks */
    protected SplStack $blockStack;

    /** @var array */
    protected array $variables = [];

    /**
     * Creates a View object based on a folder.
     *
     * If no folder is provided, the current working directory is used.
     *
     * @param string $templatesFolder
     */
    public function __construct(string $templatesFolder = "")
    {
        $this->blockStack = new SplStack();
        $this->folderStack = new SplStack();
        $this->outputStack = new SplStack();

        $this->addFolder($templatesFolder ?: getcwd());
    }

    /**
     * Set the value of a template variable.
     *
     * @param string $key
     * @param mixed $value
     * @return self
     */
    public function set(string $key, mixed $value): self
    {
        $this->variables[$key] = $value;

        return $this;
    }

    /**
     * Get the value of a template variable.
     *
     * @param string $key
     * @param mixed|null $default
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->variables[$key] ?? $default;
    }

    /**
     * Set data for the template.
     *
     * @param array $data
     * @return self
     */
    public function setData(array $data): self
    {
        $this->variables = array_replace($this->variables, $data);

        return $this;
    }

    /**
     * Get the current data for the template.
     *
     * @return array
     */
    public function getData(): array
    {
        return $this->variables;
    }

    /**
     * Check if a template variable has been set.
     *
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool
    {
        return array_key_exists($key, $this->variables);
    }

    /**
     * Renders a view according to the request info.
     *
     * Template names are resolved using the configured template directories and template file extension.
     *
     * @param array|string|null $template Template name, relative to one of the template directories.
     * @param array $data Template data
     * @return string
     * @throws Exception
     * @throws RuntimeException
     */
    public function render(array|string|null $template = "", array $data = []): string
    {
        if (count(func_get_args()) === 1) {
            if (is_array($template)) {
                $data = $template;
                $template = null;
            }
        }

        if (!$template) {
            if (!$this->template) {
                throw new RuntimeException("No template specified");
            }

            $template = $this->template;
        }

        // Find the template file
        $templateFile = $this->resolve($template);

        if ($templateFile === false) {
            throw new RuntimeException("Template `$template` not found");
        }

        // Save the current layout, in case the template sets its own
        $currentLayout = $this->layout;

        // Make previous and received variables available
        $variables = array_merge($this->variables, $data);
        $output = $this->renderFile($templateFile, $variables);

        while ($this->layout) {
            $layoutFile = $this->resolve($this->layout);

            if ($layoutFile === false) {
                throw new RuntimeException("Layout `$this->layout` not found");
            }

            $this->layout = "";
            $this->outputStack->push($output);
            $output = $this->renderFile($layoutFile, []);
        }

        // Restore the previous layout
        $this->layout = $currentLayout;

        return $output;
    }

    /**
     * Check if a template file exists.
     *
     * @param string $template Base file name (without extension)
     * @return bool True if the file can be read, false otherwise
     */
    public function exists(string $template = ""): bool
    {
        if (!$template) {
            if (!$this->template) {
                throw new RuntimeException("No template specified");
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
    public function addFolder(string $folder): void
    {
        $this->folder($folder);
    }

    /**
     * Find a template in the folder stack.
     *
     * @param string $templateFile Template file name and extension
     * @return string|bool The location of the template, or false
     */
    protected function resolve(string $templateFile): bool|string
    {
        $templateFileName = str($templateFile)->ensureEnd($this->extension());

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
    public function folder(string $folder = ""): string|static
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
    public function template(string $template = ""): string|static
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
    public function extension(string $extension = ""): string|static
    {
        if ($extension) {
            $this->extension = (string) str($extension)->ensureStart(".");

            return $this;
        }

        return "." . ltrim($this->extension, ".");
    }

    /**
     * Set or get the layout to use.
     *
     * Use `$view->layout(false)` or `$view->layout("")` to disable layout rendering.
     *
     * @param bool|string|null $layout Name of the layout, or `false` to disable.
     * @return self|string Name of the layout
     */
    public function layout(bool|string $layout = null): string|static
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
    public function noLayout(): self
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
    public function title(string $title = null): string|static
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
    public function content(): string
    {
        return $this->outputStack->pop();
    }

    /**
     * Load and render another view into the current view.
     *
     * Elements only inherit view data set with __set(), accessible via $this->{key}.
     *
     * @param string $template The snippet to be loaded, relative to the templates folder
     * @param array $data Additional variables for use in the partial template
     * @return string
     * @throws Exception
     * @throws RuntimeException
     */
    public function insert(string $template, array $data = []): string
    {
        $templateFile = $this->resolve($template);

        if ($templateFile === false) {
            throw new RuntimeException("Partial template `$template` not found");
        }

        $preservedLayout = $this->layout;
        $this->layout = "";
        $output = $this->render($template, $data);
        $this->layout = $preservedLayout;

        // Render the element.
        return $output;
    }

    /**
     * Import and process a template file.
     *
     * This method encapsulates the replacement of template variables, avoiding the
     * creation of extra variables in its scope.
     *
     * @internal string $filename Template file name
     * @internal array $data Template data
     * @return string
     * @throws Exception
     */
    protected function renderFile(): string
    {
        $this->emit("view.render", func_get_args());

        extract(func_get_arg(1), EXTR_PREFIX_INVALID, "v_");
        ob_start();

        try {
            require func_get_arg(0);

            return ob_get_clean();
        } catch (Exception $e) {
            ob_end_clean();

            throw $e;
        }
    }

    /**
     * Checks whether a block has been defined.
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
    public function beginBlock(string $blockName, bool $replace = false): void
    {
        $this->blockStack->push([$blockName, $replace]);
        ob_start();
    }

    /**
     * Closes the current block.
     *
     * @return void
     */
    public function endBlock(): void
    {
        $output = ob_get_clean();

        [$blockName, $replace] = $this->blockStack->pop();

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
     * @param mixed $value
     * @return string
     */
    public function escape(mixed $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES);
    }
}
