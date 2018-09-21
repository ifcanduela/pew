<?php

namespace pew\model\validator\rule;

class InList extends Rule
{
    /** @var array */
    public $list;

    public function __construct(array $list)
    {
        $this->list = $list;
    }

    public function validateValue($value)
    {
        if ($value !== null) {
            $result = in_array($value, $this->list, true);

            if ($result === false) {
                return false;
            }
        }

        return null;
    }
}
