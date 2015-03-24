<?php

namespace pew\db\relationship;

use \pew\libs\Registry;

class InvalidRelationshipDefinition extends \Exception {};

/**
 * Collects initial information about a generic table relationship.
 *
 * @package pew\db\relationship
 * @author ifcanduela <ifcanduela@gmail.com>
 */
abstract class Relationship implements RelationshipInterface
{
    /** @var string */
    protected $alias;
    
    /** @var string */
    protected $start_table = '';

    /** @var string */
    protected $mid_table = '';

    /** @var string */
    protected $end_table = '';

    /** @var string */
    protected $start_fk_name = '';

    /** @var string */
    protected $end_fk_name = '';

    /** @var Registry Supported SQL clauses */
    protected $clauses;

    /**
     * Populate relationship information.
     * 
     * @param string $key Table name or relationship alias
     * @param mixed $info Relationship definition info
     */
    public function __construct($alias, $info)
    {
        $this->alias = $alias;

        $this->init($alias, $info);

        $base_clauses = [
            'fields',
            'where',
            'group_by',
            'having',
            'limit',
            'order_by',
        ];

        $this->clauses = new Registry;
        $this->clauses->import($base_clauses);

        foreach ($base_clauses as $clause) {
            if (isSet($info[$clause])) {
                $this->clauses[$clause] = $info[$clause];
            }
        }
    }

    /**
     * Gets the relationship alias.
     * 
     * @return string
     */
    public function alias()
    {
        return $this->alias;
    }

    /**
     * Gets the related table name,
     * 
     * @return string
     */
    public function table()
    {
        return $this->end_table;
    }

    /**
     * Gets the relationship clauses.
     * 
     * @return array
     */
    public function clauses()
    {
        return $this->clauses->export();
    }
}
