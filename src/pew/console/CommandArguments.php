<?php

namespace pew\console;

class CommandArguments
{
    protected $namedArguments = [];
    protected $anonymousArguments = [];

    /**
     * Create a CommandArguments object.
     *
     * @param array $commandLineArguments The list of command-line arguments
     */
    public function __construct(array $commandLineArguments = [])
    {
        $this->loadConsoleArguments($commandLineArguments);
    }

    /**
     * Populate the CommandArguments object with data.
     *
     * @params array $commandLineArguments The list of command-line arguments
     * @return null
     */
    public function loadConsoleArguments(array $commandLineArguments)
    {
        $this->values = [];
        $argCount = count($commandLineArguments);
        $keyName = null;

        for ($i = 0; $i < $argCount; $i++) {
            $value = $commandLineArguments[$i];

            if ($value[0] !== '-') {
                if ($keyName) {
                    $this->namedArguments[$keyName] = $value;
                    $keyName = null;
                } else {
                    $this->anonymousArguments[] = $value;
                }
            } else {
                if ($keyName) {
                    $this->namedArguments[$keyName] = true;
                }
                $keyName = trim($value, '-');
            }
        }

        if ($keyName) {
            $this->namedArguments[$keyName] = true;
        }
    }

    /**
     * Check if an argument key (named or positional) exists.
     *
     * @params string|int $key The argument key to check
     * @return bool TRUE if the key exists, false otherwise.
     */
    public function has($key)
    {
        if (is_numeric($key)) {
            return array_key_exists($key, $this->anonymousArguments);
        }

        return array_key_exists($key, $this->namedArguments);
    }

    /**
     * Get the value of an anonymous argument.
     *
     * All named arguments are ignored when calculating the position. if the
     * argument position is empty, NULL is returned.
     *
     * @param int $key Argument position
     * @return string|bool|null The value of the argument, or NULL if it does not exist.
     */

    public function at($position)
    {
        if (!array_key_exists($position, $this->anonymousArguments)) {
            return null;
        }

        return $this->anonymousArguments[$position];
    }

    /**
     * Get the value of a named argument.
     *
     * This method accepts multiple arguments. The first argument with a value
     * in the list is used. If none of the keys has a value, NULL is returned.
     *
     * @param string $key Argument key name (camelCase)
     * @return string|bool|null The value of the argument, or NULL if it does not exist.
     */
    public function get($key)
    {
        $keyList = func_get_args();

        foreach ($keyList as $key) {
            $argumentName = static::camelToDashes($key);

            if (array_key_exists($argumentName, $this->namedArguments)) {
                return $this->namedArguments[$argumentName];
            }
        }

        return null;
    }

    /**
     * Get the value of a named argument.
     *
     * @param string $key Argument key name (camelCase)
     * @return string|bool|null The value of the argument, or NULL if it does not exist.
     */
    public function __get($key)
    {
        return $this->get($key);
    }

    /**
     * Convert a dash-delimited argument name to camel-case.
     *
     * @param string $dashes A dash-delimited string.
     * @return string A camel-case representation of the dash-delimited string.
     */
    public static function dashesToCamel($dashes)
    {
        $camel = preg_replace_callback('/\-(.)/', function ($match) {
                return strtoupper($match[1]);
            }, $dashes);

        return str_replace('-', '', $camel);
    }

    /**
     * Convert a camel-case argument name to dash-delimited.
     *
     * @param string $camel A camel-case string.
     * @return string A dash-delimited representation of the camel-case string.
     */
    public static function camelToDashes($camel)
    {
        $dashes = preg_replace_callback('/([a-z])([A-Z0-9])/', function ($match) {
                return $match[1] . '-' . $match[2];
            }, $camel);

        return strtolower(trim($dashes, '-'));
    }
}
