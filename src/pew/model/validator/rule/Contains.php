<?php

namespace pew\model\validator\rule;

class Contains extends Rule
{
    /** @var array */
    public $values;

    public function __construct($values)
    {
        $this->values = is_array($values) ? $values : [$values];
    }

    public function validateValue($value)
    {
        if ($value !== null) {
            $intersect = array_intersect($value, $this->values);
            $result = count($intersect) === count($this->values);

            if ($result === false) {
                return false;
            }
        }

        return null;
    }
}
