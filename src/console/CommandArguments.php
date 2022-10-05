<?php

declare(strict_types=1);

namespace pew\console;

use function pew\slug;

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
    /** @var array */
    private array $named = [];

    /** @var array */
    private array $positional = [];

    /**
     * Create a command-line argument parser.
     *
     * This method accepts both an array with the command-line arguments. If
     * `$argv` is passed without preprocessing, the script name will be added
     * to the list of positional arguments, which may be undesirable.
     *
     * @param array $arguments
     */
    public function __construct(array $arguments = [])
    {
        $this->parse($arguments);
    }

    /**
     * Parse the list or arguments.
     *
     * @param array $arguments
     * @return void
     */
    public function parse(array $arguments = []): void
    {
        $ap = new ArgumentParser();
        $ap->parse($arguments);

        $this->positional = $ap->getPositionalArguments();
        $this->named = $ap->getNamedArguments();
    }

    /**
     * Check if a parameter exists.
     *
     * @param string|int $key
     * @return bool
     */
    public function has($key): bool
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
     * @param string|int|string[]|int[] $key
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
     * Get the value of a named argument.
     *
     * Camel cased property accessors will be converted to dashed-lowercase.
     *
     * @param string $property
     * @return mixed
     */
    public function __get(string $property)
    {
        $key = (string) slug($property);

        return $this->get($key);
    }
}
