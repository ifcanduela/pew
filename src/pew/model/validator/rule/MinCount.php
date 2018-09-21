<?php

namespace pew\model\validator\rule;

class MinCount extends Rule
{
    public $minCount;

    public function __construct(int $minCount)
    {
        $this->minCount = $minCount;
    }

    public function validateValue($value)
    {
        $result = count($value) >= $this->minCount;

        if ($result === false) {
            return false;
        }

        return null;
    }
}
