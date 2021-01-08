<?php declare(strict_types=1);

namespace pew\model\relation;

use Exception;
use pew\model\RecordCollection;

/**
 * A many-to-many relationship.
 */
class HasAndBelongsToMany extends Relationship
{
    /** @var string Association table name */
    protected string $through = "";

    /** @var array|string Join condition */
    protected $on;

    /**
     * Specify a association table for the relationship.
     *
     * @param string $table Name of the association table
     * @param array $on Condition to join the far table to the association table.
     * @return self
     */
    public function through(string $table, array $on): HasAndBelongsToMany
    {
        $this->through = $table;
        $this->on = $on;

        return $this;
    }

    /**
     * Get a list of related records.
     *
     * @return RecordCollection
     */
    public function fetch(): RecordCollection
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
     * @throws Exception
     */
    public function find(array $relatedKeys): array
    {
        $fk = $this->through . "." . $this->foreignKeyName;

        $this->finder
            ->columns($this->foreignKeyName, $this->finder->tableName() . ".*")
            ->join($this->through, $this->on)
            ->where([$fk => ["IN", $relatedKeys]]);
        $related = $this->finder->db->run($this->finder->query);

        return $this->groupRecords($related, $this->foreignKeyName);
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
    private function groupRecords(array $records, string $field): array
    {
        $recordClass = $this->finder->recordClass();
        $result = [];

        foreach ($records as $record) {
            $result[$record[$field]][] = $recordClass ? $recordClass::fromArray($record) : $record;
        }

        return array_map(function ($items) {
            return new RecordCollection($items);
        }, $result);
    }

    /**
     * {@inheritDoc}
     */
    public function getGroupingField(): string
    {
        return $this->localKeyName;
    }
}
