<?php

namespace app\models;

class User extends \pew\Model
{
    public $connectionName = 'test';
    public $tableName = 'users';

    public function getProject()
    {
        return $this->belongsTo(Project::class);
    }
}
