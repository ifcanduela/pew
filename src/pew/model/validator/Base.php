<?php

namespace pew\model\validator;

use pew\model\validator\rule\Callback;
use pew\model\validator\rule\CanBeNull;
use pew\model\validator\rule\InList;
use pew\model\validator\rule\IsUnique;
use pew\model\validator\rule\NotNull;
use pew\model\validator\rule\Required;
use pew\model\validator\rule\Rule;

abstract class Base
{
    /** @var array */
    public $rules = [];

    /** @var Array */
    public $options = [];

    /**
     * Create a base validator.
     *
     * @param array $options
     */
    public function __construct(array $options = [])
    {
        $this->options = $options;
    }

    /**
     * Add a validation rule.
     *
     * @param Rule $rule [description]
     * @return self
     */
    public function addRule(Rule $rule)
    {
        $ruleClassName = get_class($rule);
        $this->rules[$ruleClassName] = $rule;

        return $this;
    }

    /**
     * Check if the validator has a rule.
     *
     * @param string $ruleClassName
     * @return boolean
     */
    public function hasRule(string $ruleClassName)
    {
        return array_key_exists($ruleClassName, $this->rules);
    }

    /**
     * Add a Callback rule.
     *
     * @param callable $callback
     * @return self
     */
    public function callback(callable $callback)
    {
        $this->addRule(new Callback($callback));

        return $this;
    }

    /**
     * Add a CanBeNull rule.
     *
     * @return self
     */
    public function canBeNull()
    {
        $this->addRule(new CanBeNull());

        return $this;
    }

    /**
     * Add an InList rule.
     *
     * @param array $list
     * @return self
     */
    public function inList(array $list)
    {
        $this->addRule(new InList($list));

        return $this;
    }

    /**
     * Add an IsUnique rule.
     *
     * @param string $modelClass
     * @return self
     */
    public function isUnique(string $modelClass)
    {
        $this->addRule(new IsUnique($modelClass, "id"));

        return $this;
    }

    /**
     * Add a NotNull rule.
     *
     * @return self
     */
    public function notNull()
    {
        $this->addRule(new NotNull());

        return $this;
    }

    /**
     * Add a Required rule.
     *
     * @return self
     */
    public function required()
    {
        $this->addRule(new Required());

        return $this;
    }

    /**
     * Validate all rules.
     *
     * @param mixed $value
     * @return bool
     */
    public function validate($value)
    {
        $results = [];

        foreach ($this->rules as $name => $rule) {
            $validationResult = $rule->validate($value);
            $results[$name] = $validationResult;

            if ($validationResult === false) {
                return false;
            }

            if ($validationResult === true) {
                return true;
            }
        }

        return true;
    }
}
