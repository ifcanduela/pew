<?php

declare(strict_types=1);

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
     * @return Collection
     */
    public function fetch(): Collection
    {
        return $this->finder->andWhere([$this->foreignKeyName => $this->keyValue])->all();
    }

    /**
     * Group related records by foreign key.
     *
     * Returns a list of records per foreign key.
     *
     * @param array $relatedKeys
     * @return Collection
     */
    public function find(array $relatedKeys): Collection
    {
        $related = $this->finder->andWhere([$this->foreignKeyName => ["IN", $relatedKeys]])->all();

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
