<?php

namespace pew\model\validator\rule;

class Positive extends Rule
{
    public function validateValue($value)
    {
        if ($value !== null) {
            if ($value <= 0) {
                return false;
            }
        }

        return null;
    }
}
