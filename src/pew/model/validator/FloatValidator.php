<?php

namespace pew\model\validator;

class FloatValidator extends Base
{
    public function __construct(array $options = [])
    {
        parent::__construct($options);
        $this->addRule(new rule\IsNumeric());
    }

    public function positive()
    {
        $this->addRule(new rule\Positive());

        return $this;
    }

    public function negative()
    {
        $this->addRule(new rule\Negative());

        return $this;
    }

    public function nonNegative()
    {
        $this->addRule(new rule\NonNegative());

        return $this;
    }

    public function min(float $min)
    {
        $this->addRule(new rule\Min($min));

        return $this;
    }

    public function max(float $max)
    {
        $this->addRule(new rule\Max($max));

        return $this;
    }

    public function between(float $min, float $max)
    {
        $this->addRule(new rule\Min($min));
        $this->addRule(new rule\Max($max));

        return $this;
    }
}
