<?php

namespace pew\model\relation;

/**
 * A one-to-one relationship.
 */
class HasOne extends Relationship
{
    /**
     * Get a list of related records.
     *
     * @return array
     */
    public function fetch()
    {
        return $this->finder->where([$this->foreignKeyName => $this->keyValue])->one();
    }

    /**
     * Group related records by foreign key.
     *
     * Returns a list of records per foreign key.
     *
     * @param array $relatedKeys
     * @return array
     */
    public function find(array $relatedKeys)
    {
        $related = $this->finder->where([$this->localKeyName => ['IN', $relatedKeys]])->all();

        return $related->index($this->localKeyName);
    }

    /**
     * {@inheritDoc}
     */
    public function getGroupingField()
    {
        return $this->localKeyName;
    }
}
