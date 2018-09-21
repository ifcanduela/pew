<?php

namespace pew\model\validator\rule;

class IsEmail extends Rule
{
    public function validateValue($value)
    {
        if ($value !== null) {
            $result = filter_var($value, FILTER_VALIDATE_EMAIL);

            if ($result === false) {
                return false;
            }
        }

        return null;
    }
}
