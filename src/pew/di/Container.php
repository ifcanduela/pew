<?php

namespace pew\di;

use Pimple\Container as Pimple;
use Psr\Container\ContainerInterface;

class Container implements ContainerInterface
{
    /** @var Pimple */
    protected $container;

    /**
     * Get a value from the container.
     *
     * @param string $key
     * @return mixed
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function get($key)
    {
        return $this->container[$key];
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
        $this->container[$key] = $value;
    }

    /**
     * Check if a key exists in the container.
     *
     * @param string $key
     * @return bool
     */
    public function has($key)
    {
        return isset($this->container[$key]);
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
            $this->container[$key] = $value;
        }
    }

    /**
     * Import container definitions from a file.
     *
     * @param string $filename
     * @return void
     */
    public function loadFile(string $filename)
    {
        if (file_exists($filename)) {
            $definitions = require $filename;

            if (!is_array($definitions)) {
                throw new \RuntimeException("Definitions file {$filename} must return an array");
            }

            $this->import($definitions);

            return true;
        }

        return false;
    }
}
