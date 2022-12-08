<?php

declare(strict_types=1);

namespace pew\di;

use ifcanduela\container\Container as BaseContainer;
use RuntimeException;

class Container extends BaseContainer
{
    /**
     * Import container definitions from a file.
     *
     * @param string $filename
     * @return bool
     * @throws RuntimeException When the file does not return an array
     */
    public function loadFile(string $filename): bool
    {
        if (is_readable($filename)) {
            $definitions = require $filename;

            if (!is_array($definitions)) {
                throw new RuntimeException("Definitions file `$filename` must return an array");
            }

            $this->merge($definitions);

            return true;
        }

        return false;
    }
}
