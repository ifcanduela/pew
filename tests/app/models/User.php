<?php

namespace app\models;

class User extends \pew\Model
{
    public $tableName = 'users';

    public function getProject()
    {
        return $this->belongsTo(Project::class);
    }
}
