<?php

namespace pew\model\relation;

use pew\model\Table;

abstract class Relationship
{
    /** @var Table  */
    public $finder;
    /** @var string Name of the column in the table that starts the relationship */
    public $localKeyName;
    /** @var string Name of the column in the table with the related data */
    public $foreignKeyName;
    /** @var mixed Value of the column to match */
    public $keyValue;

    /**
     * Relationship constructor.
     *
     * @param Table $finder
     * @param string $localKeyName
     * @param string $foreignKeyName
     * @param mixed $keyValue
     */
    public function __construct(Table $finder, string $localKeyName, string $foreignKeyName, $keyValue)
    {
        $this->finder = $finder;
        $this->localKeyName = $localKeyName;
        $this->foreignKeyName = $foreignKeyName;
        $this->keyValue = $keyValue;
    }

    /**
     * Get the related record or records.
     *
     * @return mixed
     */
    public abstract function fetch();

    /**
     * Find all related records for a group of records.
     *
     * @param array $relatedKeys
     * @return mixed
     */
    public abstract function find(array $relatedKeys);

    /**
     * Get the field the records are grouped around.
     *
     * This field must be from the "near" table, the one that starts the relationship.
     * 
     * @return string
     */
    public abstract function getGroupingField();
}
