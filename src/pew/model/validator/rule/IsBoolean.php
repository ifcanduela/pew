<?php

namespace pew\model\validator\rule;

class IsBoolean extends Rule
{
    public function validateValue($value)
    {
        if ($value !== null) {
            $result = is_bool($value);

            if ($result === false) {
                return false;
            }
        }

        return null;
    }
}
