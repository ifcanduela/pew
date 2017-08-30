<?php

namespace app\models;

class Project extends \pew\Model
{
    public $tableName = 'projects';

    public $extraField = 'extraValue';

    public function getUsers()
    {
        return $this->hasMany(User::class);
    }

    public function getExplicitUsers()
    {
        return User::find()->where(['project_id' => $this->id])->all();
    }
}
