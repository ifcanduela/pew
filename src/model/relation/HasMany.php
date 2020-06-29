<?php

namespace pew\model\relation;

use pew\model\Collection;

/**
 * A one-to-many relationship.
 */
class HasMany extends Relationship
{
    /**
     * Get a list of related records.
     *
     * @return \pew\model\Collection
     */
    public function fetch()
    {
        return $this->finder->where([$this->foreignKeyName => $this->keyValue])->all();
    }

    /**
     * Group related records by foreign key.
     *
     * Returns a list of records per foreign key.
     *
     * @param array $relatedKeys
     * @return Collection
     */
    public function find(array $relatedKeys)
    {
        $related = $this->finder->where([$this->foreignKeyName => ["IN", $relatedKeys]])->all();

        return $related->group($this->foreignKeyName);
    }

    /**
     * {@inheritDoc}
     */
    public function getGroupingField()
    {
        return $this->localKeyName;
    }
}
