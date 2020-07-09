<?php declare(strict_types=1);

namespace pew\console;

class CommandDefinition
{
    public $name;
    public $className;
    public $description;

    public function __construct(string $name, string $className, string $description = "")
    {
        $this->name = $name;
        $this->className = $className;
        $this->description = $description;
    }
}
