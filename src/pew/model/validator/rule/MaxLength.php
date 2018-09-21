<?php

namespace pew\model\validator\rule;

class MaxLength extends Rule
{
    public $maxLength;

    public function __construct(int $maxLength)
    {
        $this->maxLength = $maxLength;
    }

    public function validateValue($value)
    {
        if ($value !== null) {
            $result = mb_strlen($value) <= $this->maxLength;

            if (!$result) {
                return false;
            }
        }

        return null;
    }
}
