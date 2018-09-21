<?php

namespace pew\model\validator;

class ObjectValidator extends Base
{
    public $propRules = [];

    public function __construct(array $propRules, array $options = [])
    {
        parent::__construct($options);

        $this->addRule(new rule\IsObject());
        $this->propRules = $propRules;
    }

    public function validate($value)
    {
        $result = parent::validate($value);

        if ($result === false) {
            return false;
        }

        $aggregate = [];

        foreach ($this->propRules as $prop => $validator) {
            $aggregate[$prop] = $validator->validate($value->$prop ?? null);
        }

        return !in_array(false, $aggregate, true);
    }

    public function getErrors()
    {
        $failedRules = [];

        foreach ($this->propRules as $prop => $validator) {
            foreach ($validator->rules as $rule) {
                if ($rule->result === false) {
                    $failedRules[$prop][] = $rule;
                }
            }
        }

        return $failedRules;
    }
}
