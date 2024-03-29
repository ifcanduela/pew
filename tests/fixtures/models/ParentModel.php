<?php

namespace app\models;

class ParentModel extends \pew\Model
{
    public string $connectionName = 'test';

    public function getChild()
    {
        return new ChildModel();
    }

    public function getTestValue()
    {
        return $this->child->testValue;
    }
}
