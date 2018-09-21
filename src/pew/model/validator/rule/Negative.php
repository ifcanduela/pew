<?php

namespace pew\model\validator\rule;

class Negative extends Rule
{
    public function validateValue($value)
    {
        if ($value !== null) {
            if ($value >= 0) {
                return false;
            }
        }

        return null;
    }
}
