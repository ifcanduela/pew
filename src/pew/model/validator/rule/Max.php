<?php

namespace pew\model\validator\rule;

class Max extends Rule
{
    public $max;

    public function __construct(float $max)
    {
        $this->max = $max;
    }

    public function validateValue($value)
    {
        if ($value !== null) {
            $result = $this->max >= $value;

            if (!$result) {
                return false;
            }
        }

        return null;
    }
}
