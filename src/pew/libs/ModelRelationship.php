<?php

namespace pew\libs;

/**
 * Gathers information about a model relationship.
 *
 * @package pew/libs
 * @author ifcanduela <ifcanduela@gmail.com>
 */
class ModelRelationship extends \pew\libs\Registry
{
    /** @var string Relationship alias */
    private $alias;

    /** @var array Supported SQL clauses */
    private $clauses = [
        'fields',
        'where',
        'group_by',
        'having',
        'limit',
        'order_by',
    ];

    /**
     * Build a relationship.
     * 
     * @param string $key Table name or relationship alias
     * @param mixed $info Relationship definition info
     */
    public function __construct($key, $info)
    {
        $this->alias = $key;
        $this->init($key, $info);
    }

    /**
     * Populate relationship information.
     * 
     * @param string $key Table name or relationship alias
     * @param mixed $info Relationship definition info
     */
    public function init($key, $info)
    {
        if (is_string($info)) {
            # If $info is a string, ir should contain the FK
            $table = $key;
            $foreign_key = $info;
        } else {
            # If $info is an array, if contains either the table name and FK, or only the FK
            if (isSet($info[1])) {
                list($table, $foreign_key) = $info;
            } else {
                $table = $key;
                $foreign_key = $info[0];
            }
        }

        $this->table = $table;
        $this->foreign_key = $foreign_key;

        foreach ($this->clauses as $clause) {
            if (isSet($info[$clause])) {
                $this[$clause] = $info[$clause];
            }
        }
    }

    /**
     * Get the relationship alias.
     * 
     * @return string Relationship alias
     */
    public function alias()
    {
        return $this->alias;
    }

    /**
     * Get the relationship clauses.
     * 
     * @return array Defined clauses
     */
    public function clauses()
    {
        return $this->export();
    }
}
