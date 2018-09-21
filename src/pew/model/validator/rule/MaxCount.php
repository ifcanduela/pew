<?php

namespace pew\model\validator\rule;

class MaxCount extends Rule
{
    public $maxCount;

    public function __construct(int $maxCount)
    {
        $this->maxCount = $maxCount;
    }

    public function validateValue($value)
    {
        $result = count($value) <= $this->maxCount;

        if ($result === false) {
            return false;
        }

        return null;
    }
}
