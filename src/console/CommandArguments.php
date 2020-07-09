<?php declare(strict_types=1);

namespace pew\console;

use Stringy\Stringy as S;

/**
 * Parse a list of command-line arguments into arrays.
 *
 * This class will transform a string like `--force my-command-name --message "Some text" -abc=1`
 * into two lists, one with named arguments and another one with positional arguments:
 *
 * "named" => [
 *     "force" => true,
 *     "message" => "Some text",
 *     "a" => 1,
 *     "b" => 1,
 *     "c" => 1,
 * ]
 *
 * "positional" => [
 *     0 => "my-command-name"
 * ]
 */
class CommandArguments
{
    const ESCAPE_CHAR = "\\";

    /** @var array */
    private $named = [];

    /** @var array */
    private $positional = [];

    /**
     * Create a command-line argument parser.
     *
     * This method accepts both a string or an array (for example, the `$argv` global
     * variable). If `$argv` is passed, the script name will be added to the list of
     * positional arguments.
     *
     * @param string|array $arguments
     */
    public function __construct($arguments = "")
    {
        $this->parse($arguments);
    }

    /**
     * Parse the list or arguments.
     *
     * This method accepts both a string or an array (for example, the `$argv` global
     * variable). If `$argv` is passed, the script name will be added to the list of
     * positional arguments.
     *
     * @param string|array $arguments
     * @return void
     */
    public function parse($arguments)
    {
        # make sure we have a string
        if (is_array($arguments)) {
            $arguments = implode(" ", $arguments);
        }

        # tokenize the string
        $input = $this->tokenize($arguments);

        # reset the argument list
        $this->positional = [];
        $this->named = [];

        $name = "";

        foreach ($input as $param) {
            if ($param[0] === "-") {
                if ($name) {
                    # there's a named param without value
                    $this->addNamed($name, true);
                    $name = null;
                }

                # it's a name
                if (isset($param[1])) {
                    if ($param[1] === "-") {
                        # it's a long name
                        $name = substr($param, 2);

                        if (strpos($name, "=")) {
                            # the value is attached to the key
                            [$name, $value] = explode("=", $name, 2);
                            $this->addNamed($name, $value);
                            $name = "";
                        }
                    } else {
                        # it's a short param
                        $value = true;
                        $name = substr($param, 1);

                        if (strpos($param, "=")) {
                            # it's a short param with a value
                            [$name, $value] = explode("=", $name, 2);
                            $names = str_split($name, 1);
                        } else {
                            # it's a short param
                            $names = str_split($name, 1);
                        }

                        foreach ($names as $name) {
                            $this->addNamed($name, $value);
                        }

                        $name = "";
                    }
                }
            } else {
                if ($name) {
                    $this->addNamed($name, $param);
                    $name = "";
                } else {
                    $this->addPositional($param);
                }
            }
        }

        # handle any dangling token
        if ($name) {
            $this->addNamed($name, true);
            $name = null;
        }
    }

    /**
     * Check if a parameter exists.
     *
     * @param string|int $key
     * @return bool
     */
    public function has($key)
    {
        if (array_key_exists($key, $this->named)) {
            return true;
        }

        if (array_key_exists($key, $this->positional)) {
            return true;
        }

        return false;
    }

    /**
     * Get the value of a parameter.
     *
     * @param string|int $key
     * @param mixed $defaultValue
     * @return mixed
     */
    public function get($key, $defaultValue = null)
    {
        if (!is_array($key)) {
            $key = [$key];
        }

        foreach ($key as $k) {
            if (array_key_exists($k, $this->named)) {
                return $this->named[$k];
            }

            if (array_key_exists($k, $this->positional)) {
                return $this->positional[$k];
            }
        }

        return $defaultValue;
    }

    /**
     * Get a positional arguments.
     *
     * @param int $index
     * @param mixed $defaultValue
     * @return mixed
     */
    public function at(int $index, $defaultValue = null)
    {
        if (array_key_exists($index, $this->positional)) {
            return $this->positional[$index];
        }

        return $defaultValue;
    }

    /**
     * Add a positional argument.\
     *
     * @param mixed $value
     */
    protected function addPositional($value)
    {
        $this->positional[] = $value;
    }

    /**
     * Add a named argument.
     *
     * Boolean arguments with a `no-` prefix will have their name stripped of
     * the prefix and their value set to `false`.
     *
     * @param string $name
     * @param mixed $value
     */
    protected function addNamed(string $name, $value)
    {
        if ($value === true && substr($name, 0, 3) === "no-") {
            $name = substr($name, 3);
            $value = false;
        }

        $this->named[$name] = $value;
    }

    /**
     * Split a command-line list of parameters into an array.
     *
     * This method will separate the parts of the command-line string, respecting
     * strings wrapped in single or double quotes.
     *
     * @param string $commandLine
     * @return array
     */
    protected function tokenize(string $commandLine)
    {
        $input = str_split($commandLine, 1);
        # add a terminating character
        $input[] = " ";
        $inputCount = count($input);

        $value = "";
        $quoteStack = [];
        $escapeChar = false;
        $tokens = [];

        for ($i = 0; $i < $inputCount; $i++) {
            $char = $input[$i];

            if ($char === static::ESCAPE_CHAR) {
                $escapeChar = true;
            } elseif ($escapeChar) {
                $value .= $char;
                $escapeChar = false;
            } else {
                if (in_array($char, ["\"", "'"])) {
                    if (isset($quoteStack[0]) && $quoteStack[0] === $char) {
                        # end of quoted chunk
                        $tokens[] = $value;
                        $value = "";
                        array_shift($quoteStack);
                    } else {
                        # beginning of quoted chunk
                        array_unshift($quoteStack, $char);
                    }
                } elseif ($char === " ") {
                    if (count($quoteStack)) {
                        $value .= $char;
                    } else {
                        $tokens[] = $value;
                        $value = "";
                    }
                } else {
                    $value .= $char;
                }
            }
        }

        return array_filter($tokens);
    }

    /**
     * Get the value of a named argument.
     *
     * Camel cased property accessors will be converted to dashed-lowercase.
     *
     * @param string $property
     * @return mixed
     */
    public function __get($property)
    {
        $key = S::create($property)->dasherize();

        return $this->get((string) $key);
    }
}
