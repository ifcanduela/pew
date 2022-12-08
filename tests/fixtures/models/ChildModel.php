<?php

namespace app\models;

class ChildModel extends \pew\Model
{
    public string $connectionName = 'test';

    public function getTestValue()
    {
        return "ChildModel::\$value";
    }
}
