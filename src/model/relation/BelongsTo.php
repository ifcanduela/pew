<?php

declare(strict_types=1);

namespace pew\model\relation;

use pew\model\Collection;
use pew\model\Record;

/**
 * A many-to-one relationship.
 */
class BelongsTo extends Relationship
{
    /**
     * Get the related record or records.
     *
     * @return Record|array|null
     */
    public function fetch()
    {
        return $this->finder->andWhere([$this->foreignKeyName => $this->keyValue])->one();
    }

    /**
     * Find related records for a list of records.
     *
     * Returns one record per foreign key.
     *
     * @param array $relatedKeys
     * @return Collection
     */
    public function find(array $relatedKeys): Collection
    {
        $related = $this->finder->andWhere([$this->foreignKeyName => ["IN", $relatedKeys]])->all();

        return $related->index($this->foreignKeyName);
    }

    /**
     * {@inheritDoc}
     */
    public function getGroupingField(): string
    {
        return $this->localKeyName;
    }
}
