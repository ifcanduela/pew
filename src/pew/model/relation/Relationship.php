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
     * Transition method calls to the Table object.
     *
     * @param string $method
     * @param array $arguments
     * @return self
     */
    public function __call($method, $arguments)
    {
        $this->finder->$method(...$arguments);
        return $this;
    }

    /**
     * Get the related record or records.
     *
     * @return mixed
     */
    abstract public function fetch();

    /**
     * Find all related records for a group of records.
     *
     * @param array $relatedKeys
     * @return mixed
     */
    abstract public function find(array $relatedKeys);

    /**
     * Get the field the records are grouped around.
     *
     * This field must be from the "near" table, the one that starts the relationship.
     *
     * @return string
     */
    abstract public function getGroupingField();
}
