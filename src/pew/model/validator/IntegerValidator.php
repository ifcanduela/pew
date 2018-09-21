<?php

namespace pew\model\validator;

class IntegerValidator extends FloatValidator
{
    public function __construct(array $options = [])
    {
        parent::__construct($options);
        $this->addRule(new rule\IsInteger());
    }
}
