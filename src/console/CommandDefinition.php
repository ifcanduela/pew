<?php declare(strict_types=1);

namespace pew\console;

/**
 * Simple data object for a command-line app endpoint.
 *
 * @package pew\console
 */
class CommandDefinition
{
    /** @var string */
    public string $name;

    /** @var string */
    public string $method;

    /** @var string */
    public string $className;

    /** @var string */
    public string $description;

    /**
     * CommandDefinition constructor.
     *
     * @param string $name
     * @param string $method
     * @param string $className
     * @param string $description
     */
    public function __construct(string $name, string $method, string $className, string $description = "")
    {
        $this->name = $name;
        $this->method = $method;
        $this->className = $className;
        $this->description = $description;
    }
}
