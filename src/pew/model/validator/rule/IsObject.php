<?php

namespace pew\model\validator\rule;

class IsObject extends Rule
{
    public function validateValue($value)
    {
        if ($value !== null) {
            $result = is_object($value);

            if ($result === false) {
                return false;
            }
        }

        return null;
    }
}
