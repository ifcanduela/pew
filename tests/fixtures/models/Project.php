<?php

namespace app\models;

/**
 * Class User
 *
 * @property string $name
 *
 * @package app\models
 */
class Project extends \pew\Model
{
    public $connectionName = 'test';
    public $tableName = 'projects';
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
