<?php

namespace pew\model\relation;

use pew\model\Collection;

/**
 * A meny-to-one relationship.
 */
class BelongsTo extends Relationship
{
    /**
     * Get the related record or records.
     *
     * @return mixed
     */
    public function fetch()
    {
        return $this->finder->where([$this->foreignKeyName => $this->keyValue])->one();
    }

    /**
     * Find related records for a list of records.
     *
     * Returns one record per foreign key.
     *
     * @param array $relatedKeys
     * @return Collection
     */
    public function find(array $relatedKeys)
    {
        $related = $this->finder->where([$this->foreignKeyName => ["IN", $relatedKeys]])->all();

        return $related->index($this->foreignKeyName);
    }

    /**
     * {@inheritDoc}
     */
    public function getGroupingField()
    {
        return $this->localKeyName;
    }
}
