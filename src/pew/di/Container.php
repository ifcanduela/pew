<?php

namespace pew\di;

use Pimple\Container as Pimple;
use Pimple\Exception\UnknownIdentifierException;
use Psr\Container\ContainerInterface;

class Container extends Pimple implements ContainerInterface
{
    /**
     * Get a value from the container.
     *
     * @param string $key
     * @return mixed
     * @throws UnknownIdentifierException
     */
    public function get($key)
    {
        return $this[$key];
    }

    /**
     * Set a value in the container.
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function set($key, $value)
    {
        $this[$key] = $value;
    }

    /**
     * Check if a key exists in the container.
     *
     * @param string $key
     * @return bool
     */
    public function has($key)
    {
        return isset($this[$key]);
    }

    /**
     * Import container definitions.
     *
     * @param array $definitions
     * @return void
     */
    public function import(array $definitions)
    {
        foreach ($definitions as $key => $value) {
            $this[$key] = $value;
        }
    }

    /**
     * Import container definitions from a file.
     *
     * @param string $filename
     * @return bool
     * @throws \RuntimeException When the file does not return an array
     */
    public function loadFile(string $filename)
    {
        if (file_exists($filename)) {
            $definitions = require $filename;

            if (!is_array($definitions)) {
                throw new \RuntimeException("Definitions file `{$filename}` must return an array");
            }

            $this->import($definitions);

            return true;
        }

        return false;
    }

    /**
     * Alias a key with another name.
     *
     * @param string $from
     * @param string $to
     * @return void
     */
    public function alias($from, $to)
    {
        $this[$from] = function (Container $c) use ($to)  {
            return $c[$to];
        };
    }
}
