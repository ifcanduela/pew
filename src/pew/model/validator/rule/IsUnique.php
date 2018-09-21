<?php

namespace pew\model\validator\rule;

class IsUnique extends Rule
{
    public $modelClass;
    public $fieldName;

    public function __construct(string $modelClass, string $fieldName)
    {
        $this->modelClass = $modelClass;
        $this->fieldName = $fieldName;
    }

    public function validateValue($value)
    {
        if ($value !== null) {
            $modelClass = $this->modelClass;
            $result = $modelClass::find()->where([$this->fieldName => $value])->one();

            if ($result === null) {
                return false;
            }
        }

        return null;
    }
}
