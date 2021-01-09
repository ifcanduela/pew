<?php declare(strict_types=1);

namespace pew\model\relation;

use pew\model\RecordCollection;
use pew\model\Record;

/**
 * A one-to-one relationship.
 */
class HasOne extends Relationship
{
    /**
     * Get a list of related records.
     *
     * @return Record|array|null
     */
    public function fetch()
    {
        return $this->finder->andWhere([$this->foreignKeyName => $this->keyValue])->one();
    }

    /**
     * Group related records by foreign key.
     *
     * Returns a list of records per foreign key.
     *
     * @param array $relatedKeys
     * @return RecordCollection
     */
    public function find(array $relatedKeys): RecordCollection
    {
        $related = $this->finder->andWhere([$this->localKeyName => ['IN', $relatedKeys]])->all();

        return $related->index($this->localKeyName);
    }

    /**
     * {@inheritDoc}
     */
    public function getGroupingField(): string
    {
        return $this->localKeyName;
    }
}
