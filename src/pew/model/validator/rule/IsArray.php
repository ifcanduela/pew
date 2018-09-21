<?php

namespace pew\model\validator\rule;

class IsArray extends Rule
{
    public function validateValue($value)
    {
        if ($value !== null) {
            $isArrayLike = $value instanceof \ArrayAccess && $value instanceof \Traversable;
            $isArray = is_array($value);
            $result = $isArray || $isArrayLike;

            if ($result === false) {
                return false;
            }
        }

        return null;
    }
}
