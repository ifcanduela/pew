<?php

namespace pew\db\relationship;

use pew\db\TableInterface;

/**
 * Collects information about a one-to-one relationship and resolves it.
 *
 * @package pew\db\relationship
 * @author ifcanduela <ifcanduela@gmail.com>
 */
class HasOne extends Relationship
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
        return $this->end_fk_name;
    }

    public function fetch(TableInterface $t, $key)
    {
        if (!is_null($key)) {
            $clauses = $this->clauses();
            $t->clauses($clauses);
            return $t->find($key);
        } else {
            return false;
        }
    }
}
