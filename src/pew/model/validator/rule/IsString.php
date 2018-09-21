<?php

namespace pew\model\validator\rule;

class IsString extends Rule
{
    public function validateValue($value)
    {
        if ($value !== null) {
            $result = is_string($value);

            if ($result === false) {
                return false;
            }
        }
var_dump(__METHOD__);
        return null;
    }
}
