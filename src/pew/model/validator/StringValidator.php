<?php

namespace pew\model\validator;

class StringValidator extends Base
{
    public function __construct(bool $strict = false, array $options = [])
    {
        parent::__construct($options);

        if ($strict) {
            $this->strict();
        }
    }

    public function strict()
    {
        if (!$this->hasRule(rule\IsString::class)) {
            $this->addRule(new rule\IsString());
        }

        return $this;
    }

    public function minLength(int $minLength)
    {
        $this->addRule(new rule\MinLength($minLength));

        return $this;
    }

    public function maxLength(int $maxLength)
    {
        $this->addRule(new rule\MaxLength($maxLength));

        return $this;
    }

    public function pattern(string $regularExpression)
    {
        $this->addRule(new rule\RegularExpression($regularExpression));

        return $this;
    }

    public function between($minLength, $maxLength)
    {
        $this->addRule(new rule\MinLength($minLength));
        $this->addRule(new rule\MaxLength($maxLength));

        return $this;
    }

    public function isEmail()
    {
        $this->addRule(new rule\IsEmail());

        return $this;
    }
}
