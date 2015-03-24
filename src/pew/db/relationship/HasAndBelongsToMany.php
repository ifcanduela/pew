<?php

namespace pew\db\relationship;

use pew\db\TableInterface;

/**
 * Collects information about a many-to-many relationship and resolves it.
 *
 * @package pew\db\relationship
 * @author ifcanduela <ifcanduela@gmail.com>
 */
class HasAndBelongsToMany extends Relationship
{
    public function init($alias, $info)
    {
        if (is_string($info)) {
            $this->end_table = $alias;
            $this->end_fk_name = $info;
        } elseif (!isSet($info[0]) || !isSet($info[1]) || !isSet($info[2]) || !isSet($info[3])) {
            throw new InvalidRelationshipDefinition("Relationship {$alias} is invalid");
        } else {
            $this->mid_table = $info[0];
            $this->start_fk_name = $info[1];
            $this->end_fk_name = $info[2];
            $this->end_table = $info[3];
        }
    }

    public function key()
    {
        return null;
    }

    /**
     * @todo: try to rewrite sql to fetch values in single query
     */
    public function fetch(TableInterface $t, $key)
    {
        $end_fk_values = $t->db->query("SELECT {$this->end_fk_name} FROM {$this->mid_table} WHERE {$this->start_fk_name} = ?", [
                $key
            ]);

        array_walk($end_fk_values, function (&$item) use ($t) {
            $item = $item[$this->end_fk_name];
        });

        if (count($end_fk_values)) {
            # get the relationship clauses
            $clauses = $this->clauses();
            # add a constraint for the relationship FK
            $clauses['where'][$t->primary_key()] = ['in', join(',', $end_fk_values)];
            # update the model clauses
            $t->clauses($clauses);
            # fetch the related records
            return $t->find_all();
        } else {
            return [];
        }
    }
}
