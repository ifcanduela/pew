<?php

namespace pew\model\validator\rule;

class Count extends Rule
{
    public $count;

    public function __construct(int $count)
    {
        $this->count = $count;
    }

    public function validateValue($value)
    {
        if ($value !== null) {
            $result = count($value) === $this->count;

            if ($result === false) {
                return false;
            }
        }

        return null;
    }
}
