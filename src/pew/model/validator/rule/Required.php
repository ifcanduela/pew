<?php

namespace pew\model\validator\rule;

class Required extends Rule
{
    public function validateValue($value)
    {
        if ($value === null || $value === "" || $value === []) {
            return false;
        }

        return null;
    }
}
