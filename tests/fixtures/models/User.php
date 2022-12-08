<?php

namespace app\models;

use pew\model\Table;

/**
 * Class User
 *
 * @property string $username
 * @property string $project_id
 *
 * @package app\models
 */
class User extends \pew\Model
{
    public string $connectionName = 'test';
    public string $tableName = 'users';

    public function getProject()
    {
        return $this->belongsTo(Project::class);
    }

    public function getProfile()
    {
        return $this->hasOne(Profile::class);
    }

    public static function find(): Table
    {
        return parent::find()->where(["created_at" => ["IS NOT", null]]);
    }
}
