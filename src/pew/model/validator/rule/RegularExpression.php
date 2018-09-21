<?php

namespace pew\model\validator\rule;

class RegularExpression extends Rule
{
    /** @var string] */
    public $regularExpression;

    public function __construct(string $regularExpression)
    {
        $this->regularExpression = $regularExpression;
    }

    public function validateValue($value)
    {
        if ($value !== null) {
            $result = preg_match($this->regularExpression, $value);

            if (!$result) {
                return false;
            }
        }

        return null;
    }
}
