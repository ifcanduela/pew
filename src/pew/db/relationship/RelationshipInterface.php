<?php

namespace pew\db\relationship;

use pew\db\TableInterface;

/**
 * Interface for the different types of relationship.
 *
 * To be implemented by HasOne, HasMany, HasAndBelongsToMane and BelongsTo
 * relationship types.
 *
 * @package pew\db\relationship
 * @author ifcanduela <ifcanduela@gmail.com>
 */
interface RelationshipInterface
{
    /**
     * Initializes the relationship information (alias, key names and table names).
     * 
     * @param atring $alias Table name or alias
     * @param array |string$info Foreign key name or array of relationship information
     */
    public function init($alias, $info);

    /**
     * Retrieves the relationshi alias.
     * 
     * @return string
     */
    public function alias();

    /**
     * Retrieves the related table name.
     * 
     * @return string
     */
    public function table();

    /**
     * Retrieves the name of the foreign key.
     * 
     * @return string
     */
    public function key();

    /**
     * Resolves the relationship.
     * 
     * @param TableInterface $related_table
     * @param mixed $key Value of the foreign key.
     * @return mixed The result of the relationship
     */
    public function fetch(TableInterface $related_table, $key);
}
