<?php

namespace pew\model\relation;

use pew\model\Collection;

class HasAndBelongsToMany extends Relationship
{
    /** @var string Association table name */
    protected $through;

    /** @var array|string Join condition */
    protected $on;

    /**
     * Specify a association table for the relationship.
     *
     * @param string $table Name of the association table
     * @param array $on Condition to join the far table to the association table.
     * @return self
     */
    public function through($table, array $on)
    {
        $this->through = $table;
        $this->on = $on;

        return $this;
    }

    /**
     * Get a list of related records.
     *
     * @return array
     */
    public function fetch()
    {
        $fk = $this->through . "." . $this->foreignKeyName;

        return $this->finder->join($this->through, $this->on)->where([$fk => $this->keyValue])->all();
    }

    /**
     * Group related records by foreign key.
     *
     * Returns a list of records per foreign key.
     *
     * @param array $relatedKeys
     * @return array
     * @throws \Exception
     */
    public function find(array $relatedKeys)
    {
        $fk = $this->through . "." . $this->foreignKeyName;

        $this->finder
            ->columns($this->foreignKeyName, $this->finder->tableName() . ".*")
            ->join($this->through, $this->on)
            ->where([$fk => ["IN", $relatedKeys]]);
        $related = $this->finder->db->run($this->finder->query);
        $grouped = $this->groupRecords($related, $this->foreignKeyName);

        return $grouped;
    }

    /**
     * Group related records by foreign key.
     *
     * Returns one or mor records per foreign key.
     *
     * @param array $records
     * @param string $field
     * @return array
     */
    private function groupRecords($records, $field)
    {
        $recordClass = $this->finder->recordClass();
        $result = [];

        foreach ($records as $record) {
            $result[$record[$field]][] = $recordClass::fromArray($record);
        }

        return array_map(function ($items) {
            return new Collection($items);
        }, $result);
    }

    /**
     * {@inheritDoc}
     */
    public function getGroupingField()
    {
        return $this->localKeyName;
    }
}
