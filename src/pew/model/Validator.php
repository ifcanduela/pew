<?php

namespace pew\model;

use pew\model\validator\ArrayValidator;
use pew\model\validator\BooleanValidator;
use pew\model\validator\IntegerValidator;
use pew\model\validator\FloatValidator;
use pew\model\validator\StringValidator;
use pew\model\validator\ObjectValidator;

class Validator
{
    /**
     * Create a boolean validator.
     *
     * @return BooleanValidator
     */
    public static function boolean()
    {
        return new BooleanValidator();
    }

    /**
     * Create an numeric validator.
     *
     * @return FloatValidator
     */
    public function numeric()
    {
        return new FloatValidator();
    }

    /**
     * Create a float validator.
     *
     * @return FloatValidator
     */
    public static function float()
    {
        return new FloatValidator();
    }

    /**
     * Create an integer validator.
     *
     * @return IntegerValidator
     */
    public static function integer()
    {
        return new IntegerValidator();
    }

    /**
     * Create a string validator.
     *
     * @return StringValidator
     */
    public static function string(bool $strict = false)
    {
        return new StringValidator($strict);
    }

    /**
     * Create an array validator.
     *
     * @return ArrayValidator
     */
    public static function array(array $keyRules = [])
    {
        return new ArrayValidator($keyRules);
    }

    /**
     * Create an object validator.
     *
     * @return ObjectValidator
     */
    public static function object(array $propRules)
    {
        return new ObjectValidator($propRules);
    }
}
