<?php

namespace pew\libs;

/**
 * ArgumentResolver class.
 */
class ArgumentResolver
{
    protected $argument_lists = [];

    public function prepend_list(array $list)
    {
        array_unshift($this->argument_lists, $list);
    }

    public function append_list(array $list)
    {
        $this->argument_lists[] = $list;
    }

    /**
     * Resolves a class constructor using stored values.
     *
     *  For the moment only the constructor argument name is taken into
     *  account to resolve argument.
     * 
     * @param ReflectionClass $class
     * @return object
     */
    public function resolve_constructor(\ReflectionClass $class)
    {
        $constructor = $class->getConstructor();

        if (is_null($constructor)) {
            return $class->newInstance();
        }

        $arguments = $constructor->getParameters();

        if (!$arguments) {
            return $class->newInstance();
        }

        $args_array = [];

        foreach ($arguments as $arg) {
            $value = $this->find_value($arg);
            $args_array[] = $value;
        }

        return $args_array;
    }

    /**
     * Resolves a function or method call using stored values.
     *
     *  For the moment only the constructor argument name is taken into
     *  account to resolve argument.
     * 
     * @param ReflectionFunctionAbstract $callback
     * @return array
     */
    public function resolve_call(\ReflectionFunctionAbstract $function)
    {
        $parameters = $function->getParameters();
        $args = [];

        foreach ($parameters as $p) {
            $value = $this->find_value($p);
            $args[] = $value;
        }

        return $args;
    }

    public function find_value(\ReflectionParameter $arg)
    {
        foreach ($this->argument_lists as $list) {
            if (array_key_exists($arg->name, $list)) {
                return $list[$arg->name];
            }
        }

        if ($arg->isDefaultValueAvailable()) {
            return $arg->getDefaultValue();
        } elseif ($arg->isOptional()) {
            return null;
        }

        throw new \Exception("Argument {$arg->name} not found");
    }
}
