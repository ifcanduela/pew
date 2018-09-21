<?php

namespace pew\model\validator\rule;

class IsInteger extends Rule
{
    public function validateValue($value)
    {
        if ($value !== null) {
            $result = is_integer($value);

            if ($result === false) {
                return false;
            }
        }

        return null;
    }
}
