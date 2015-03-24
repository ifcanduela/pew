<?php

namespace pew\db\relationship;

use pew\db\TableInterface;

/**
 * Collects information about a one-to-many relationship and resolves it.
 *
 * @package pew\db\relationship
 * @author ifcanduela <ifcanduela@gmail.com>
 */
class HasMany extends Relationship
{
    public function init($alias, $info)
    {
        if (is_string($info)) {
            $this->end_table = $alias;
            $this->end_fk_name = $info;
        } elseif (!isSet($info[0]) || !isSet($info[1])) {
            throw new InvalidRelationshipDefinition("Relationship {$alias} is invalid");
        } else {
            $this->end_table = $info[0];
            $this->end_fk_name = $info[1];
        }
    }

    public function key()
    {
        return null;
    }

    public function fetch(TableInterface $t, $key)
    {
        if (!is_null($key)) {
            $clauses = $this->clauses();
            $clauses['where'][$this->end_fk_name] = $key;
            $t->clauses($clauses);
            
            return $t->find_all();
        } else {
            return false;
        }
    }
}
