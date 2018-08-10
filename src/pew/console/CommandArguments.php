<?php

namespace pew\console;

use Stringy\Stringy as S;

class CommandArguments
{
    /** @var array Arguments preceded by a nametag (-n or --name) */
    protected $namedArguments = [];

    /** @var array Arguments given without a nametag */
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
     * @param array $commandLineArguments The list of command-line arguments
     */
    public function loadConsoleArguments(array $commandLineArguments)
    {
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
     * @param string|int $key The argument key to check
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
     * @param int $position Argument position
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
            $argumentName = S::create($key)->dasherize();

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
}
