<?php

namespace pew\model\validator;

class BooleanValidator extends Base
{
    public function __construct(array $options = [])
    {
        parent::__construct($options);
        $this->addRule(new rule\IsBoolean());
    }
}
