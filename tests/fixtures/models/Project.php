<?php

namespace app\models;

/**
 * Class Project
 *
 * @property string $name
 *
 * @package app\models
 */
class Project extends \pew\Model
{
    public string $connectionName = 'test';
    public string $tableName = 'projects';
    public $extraField = 'extraValue';
    private $privateField = 'privateValue';

    public function getUsers()
    {
        return $this->hasMany(User::class);
    }

    public function getExplicitUsers()
    {
        return User::find()->where(['project_id' => $this->id])->all();
    }

    public function setPrivateField($value)
    {
        $this->privateField = $value;
    }

    public function getPrivateField()
    {
        return $this->privateField;
    }

    public function getTags()
    {
        return $this->hasAndBelongsToMany(Tag::class);
    }
}
