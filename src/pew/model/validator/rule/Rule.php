<?php

namespace pew\model\validator\rule;

use Stringy\Stringy as s;

abstract class Rule
{
    public $value;
    public $result;
    public $ruleName;

    /**
     * Validate a value.
     *
     * This method should return one of the following values:
     * - `null` when the value does not fail the rule
     * - `true` when the value does not fail the rule and does not require
     *    further validation from other rules
     * - `false` when the value fails the rule
     *
     * @param mixed $value
     * @return bool|null
     */
    abstract protected function validateValue($value);

    /**
     * Validate a value.
     *
     * This method should return one of the following values:
     * - `null` when the value does not fail the rule
     * - `true` when the value does not fail the rule and does not require
     *    further validation from other rules
     * - `false` when the value fails the rule
     *
     * @param mixed $value
     * @return bool|null
     */
    public function validate($value)
    {
        $this->value = $value;
        $this->result = $this->validateValue($value);

        return $this->result;
    }

    public function getErrorMessage()
    {
        if ($this->result === false) {
            if (empty($this->ruleName)) {
                $className = (new \ReflectionClass($this))->getShortName();
                $this->ruleName = s::create($className)->underscored()->humanize()->toTitleCase();
            }

            return sprintf("The value %s failed rule %s", $this->value, $this->ruleName);
        }

        return null;
    }
}
