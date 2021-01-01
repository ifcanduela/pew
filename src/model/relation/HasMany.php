<?php declare(strict_types=1);

namespace pew\model\relation;

use pew\model\RecordCollection;

/**
 * A one-to-many relationship.
 */
class HasMany extends Relationship
{
    /**
     * Get a list of related records.
     *
     * @return RecordCollection
     */
    public function fetch(): RecordCollection
    {
        return $this->finder->where([$this->foreignKeyName => $this->keyValue])->all();
    }

    /**
     * Group related records by foreign key.
     *
     * Returns a list of records per foreign key.
     *
     * @param array $relatedKeys
     * @return RecordCollection
     */
    public function find(array $relatedKeys)
    {
        $related = $this->finder->where([$this->foreignKeyName => ["IN", $relatedKeys]])->all();

        return $related->group($this->foreignKeyName);
    }

    /**
     * {@inheritDoc}
     */
    public function getGroupingField(): string
    {
        return $this->localKeyName;
    }
}
