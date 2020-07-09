<?php declare(strict_types=1);

namespace pew\model\relation;

use pew\model\Collection;
use pew\model\Record;

/**
 * A one-to-one relationship.
 */
class HasOne extends Relationship
{
    /**
     * Get a list of related records.
     *
     * @return Record
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
     * @return Collection
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
