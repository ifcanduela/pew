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

    public function getProfile()
    {
        return $this->hasOne(Profile::class);
    }

    public static function find()
    {
        return parent::find()->where(["created_at" => ["IS NOT", null]]);
    }
}
