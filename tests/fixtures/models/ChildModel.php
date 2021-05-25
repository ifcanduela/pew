<?php

namespace app\models;

class ChildModel extends \pew\Model
{
    public $connectionName = 'test';

    public function getTestValue()
    {
        return "ChildModel::\$value";
    }
}
