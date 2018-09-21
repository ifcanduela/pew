<?php

namespace pew\model\validator\rule;

class IsNumeric extends Rule
{
    public function validateValue($value)
    {
        if ($value !== null) {
            $result = is_numeric($value);

            if ($result === false) {
                return false;
            }
        }

        return null;
    }
}
