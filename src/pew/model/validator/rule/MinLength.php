<?php

namespace pew\model\validator\rule;

class MinLength extends Rule
{
    public $minLength;

    public function __construct(int $minLength)
    {
        $this->minLength = $minLength;
    }

    public function validateValue($value)
    {
        if ($value !== null) {
            $result = $this->minLength <= mb_strlen($value);

            if (!$result) {
                return false;
            }
        }

        return null;
    }
}
