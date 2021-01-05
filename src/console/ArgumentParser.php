<?php

namespace pew\console;

class ArgumentParser
{
    private string $currentName = "";

    private array $positionalArguments = [];

    private array $namedArguments = [];

    function parse(array $argumentList): void
    {
        foreach ($argumentList as $param) {
            if ($this->isNamedParameter($param)) {
                $this->addAndReset();

                if ($this->isLongParam($param)) {
                    # it's a long name
                    $this->currentName = substr($param, 2);

                    if (strpos($this->currentName, "=")) {
                        # the value is attached to the key
                        [$this->currentName, $value] = explode("=", $this->currentName, 2);
                        $this->addAndReset($value);
                    }
                } elseif ($this->isShortParam($param)) {
                    # it's a short param
                    $value = true;
                    $this->currentName = substr($param, 1);

                    if (strpos($param, "=")) {
                        # it's a short param with a value
                        [$this->currentName, $value] = explode("=", $this->currentName, 2);
                        $names = str_split($this->currentName, 1);
                    } else {
                        # it's a short param
                        $names = str_split($this->currentName, 1);
                    }

                    foreach ($names as $n) {
                        $this->addNamed($n, $value);
                    }

                    $this->currentName = "";
                }
            } else {
                if ($this->currentName) {
                    $this->addAndReset($param);
                } else {
                    $this->addPositional($param);
                }
            }
        }

        # handle any dangling token
        $this->addAndReset();
    }

    public function getPositionalArguments(): array
    {
        return $this->positionalArguments;
    }

    public function getNamedArguments(): array
    {
        return $this->namedArguments;
    }

    /**
     * Add a positional argument.\
     *
     * @param mixed $value
     * @return void
     */
    protected function addPositional($value): void
    {
        $this->positionalArguments[] = $value;
    }

    /**
     * Add a named argument.
     *
     * Boolean arguments with a `no-` prefix will have their name stripped of
     * the prefix and their value set to `false`.
     *
     * @param string $name
     * @param mixed $value
     * @return void
     */
    protected function addNamed(string $name, $value): void
    {
        if ($value === true && substr($name, 0, 3) === "no-") {
            $name = substr($name, 3);
            $value = false;
        }

        $this->namedArguments[$name] = $value;
    }

    protected function addAndReset($value = true)
    {
        if ($this->currentName) {
            # there's a named param without value
            $this->addNamed($this->currentName, $value);
            $this->currentName = "";
        }
    }

    protected function isNamedParameter(string $str): bool
    {
        return $str[0] === "-";
    }
    
    protected function isLongParam(string $str): bool
    {
        return substr($str, 0, 2) === "--";
    }

    protected function isShortParam(string $str): bool
    {
        $hyphenFirst = isset($str[0]) && ($str[0] === "-");
        $hyphenSecond = isset($str[1]) && ($str[1] === "-");

        return $hyphenFirst && !$hyphenSecond;
    }
}
