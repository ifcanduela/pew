<?php

namespace pew\model\validator;

class ArrayValidator extends Base
{
    public $keyRules = [];

    public function __construct(array $keyRules = [], array $options = [])
    {
        parent::__construct($options);

        $this->addRule(new rule\IsArray());
        $this->keyRules = $keyRules;
    }

    public function contains($value)
    {
        $this->addRule(new rule\Contains($value));

        return $this;
    }

    public function count(int $count)
    {
        $this->addRule(new rule\Count($count));

        return $this;
    }

    public function minCount(int $minCount)
    {
        $this->addRule(new rule\MinCount($minCount));

        return $this;
    }

    public function maxCount(int $maxCount)
    {
        $this->addRule(new rule\MaxCount($maxCount));

        return $this;
    }

    public function validate($value)
    {
        $result = parent::validate($value);

        if ($result === false) {
            return false;
        }

        $aggregate = [];

        foreach ($this->keyRules as $key => $validator) {
            $aggregate[$key] = $validator->validate($value[$key] ?? null);
        }

        return !in_array(false, $aggregate, true);
    }
}
