<?php

namespace pew\model\validator\rule;

class NotNull extends Rule
{
    public function validateValue($value)
    {
        if ($value === null) {
            return false;
        }

        return null;
    }
}
