<?php

namespace pew\model\validator\rule;

class Min extends Rule
{
    public $min;

    public function __construct(float $min)
    {
        $this->min = $min;
    }

    public function validateValue($value)
    {
        if ($value !== null) {
            $result = $this->min <= $value;

            if (!$result) {
                return false;
            }
        }

        return null;
    }
}
