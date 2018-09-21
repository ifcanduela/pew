<?php

namespace pew\model\validator\rule;

class CanBeNull extends Rule
{
    public function validateValue($value)
    {
        if ($value === null) {
            return true;
        }

        return null;
    }
}
