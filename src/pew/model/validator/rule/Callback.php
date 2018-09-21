<?php

namespace pew\model\validator\rule;

class Callback extends Rule
{
    /** @var callback */
    public $callback;

    public function __construct(callable $callback)
    {
        $this->callback = $callback;
    }

    public function validateValue($value)
    {
        if ($value !== null) {
            $callback = $this->callback;

            return $callback($value);
        }

        return null;
    }
}
