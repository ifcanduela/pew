<?php

namespace pew\db;

use pew\Pew;
use pew\db\Database;
use pew\db\Record;
use pew\db\RecordCollection;
use pew\db\relationship\RelationshipInterface;
use pew\db\relationship\BelongsTo;
use pew\db\relationship\HasAndBelongsToMany;
use pew\db\relationship\HasMany;
use pew\db\relationship\HasOne;
use pew\libs\Str;

class TableNotSpecifiedException extends \LogicException {}
class TableNotFoundException extends \RuntimeException {}

/**
 * Table gateway class.
 *
 * @package pew\db
 * @author ifcanduela <ifcanduela@gmail.com>
 */
class Table implements TableInterface, \ArrayAccess, \IteratorAggregate, \JsonSerializable
{
    /**
     * Database abstraction instance.
     *
     * @var Database
     */
    public $db = null;

    /**
     * Database table for the subject of the model.
     *
     * @var string
     */
    protected $table = null;

    /**
     * Name of the primary key fields of the table the Model manages.
     *
     * @var string
     */
    protected $primary_key = null;

    /**
     * Miscellaneous table metadata.
     *
     * Holds table name, primary key name, column names, primary text column
     * name (either 'name' or 'title') and values.
     *
     * @var array
     */
    protected $table_data = [];

    /**
     * Current resultset.
     *
     * Holds an index for each record in the last resultset.
     *
     * @var array
     */
    protected $record = [];

    /**
     * Related model tables.
     *
     * Holds an index for each related model.
     *
     * @var array
     */
    protected $relationships = [];

    /**
     * Related models to eagerly load on find() operations.
     *
     * @var array
     */
    protected $eager_load = [];

    /**
     * An associative array of child tables.
     *
     * The simplest way of defining a relationship is as follows:
     *
     *     <code>public $has_many = ['comments' => 'user_id'];</code>
     * 
     * This field can also be used with aliases using the following format:
     *
     *     <code>public $has_many = ['user_comments' => ['comments', 'user_id']];</code>
     *
     * @var array
     */
    protected $has_many = [];

    /**
     * An associative array of parent tables.
     *
     * The simplest way of defining a relationship is as follows:
     *
     *     <code>public $belongs_to = ['users' => 'user_id'];</code>
     * 
     * This field can also be used with aliases using the following format:
     *
     *     <code>public $belongs_to = ['owner' => ['users', 'user_id'];</code>
     *
     * @var array
     */
    protected $belongs_to = [];

    /**
     * An associative array of sibling tables.
     *
     * The way of defining a sibling relationship is as follows:
     *
     *     <code>public $has_and_belongs_to_many = ['tags' => ['tagged_posts', 'post_id, 'tag_id', 'tags']];</code>
     *
     * @var array
     */
    protected $has_and_belongs_to_many = [];

    /**
     * An associative array of twin tables.
     *
     * The way of defining a twin relationship is as follows:
     *
     *     <code>public $has_one = ['profile' => ['profiles', 'profile_id']];</code>
     *
     * @var array
     */
    protected $has_one = [];

    /**
     * Fields to retrieve in SELECT statements.
     *
     * @var string
     */
    protected $fields = '*';

    /**
     * Conditions for the queries.
     *
     * @var string
     */
    protected $where = [];

    /**
     * Sorting order for the query results.
     *
     * @var string
     */
    protected $order_by = null;

    /**
     * Sorting order for the query results.
     *
     * @var string
     */
    protected $limit = null;

    /**
     * Grouping of fields for the query results.
     *
     * @var string
     */
    protected $group_by = null;

    /**
     * Conditions for the query result groups.
     *
     * @var string
     */
    protected $having = [];

    /**
     * SQL query clauses.
     * 
     * @var array
     */
    protected $clauses = [
        'fields' => '*',
        'where' => [],
        'group_by' => '',
        'having' => [],
        'limit' => '',
        'order_by' => '',
    ];

    /**
     * The constructor builds the model!.
     *
     * @param string $table Name of the table
     * @param Database $db Database instance to use
     */
    public function __construct($table = null, Database $db = null)
    {
        $this->init($table, $db);
    }

    /**
     * Initialize a model binding it to a database table.
     * 
     * @param string $table Name of the table
     * @param Database $db Database instance to use
     */
    public function init($table = null, Database $db = null)
    {
        # get the Database class instance
        $this->db = is_null($db) ? Pew::instance()->db : $db;

        if (!is_null($table)) {
            $this->table = $table;
        } elseif (Str::ends_with(get_class($this), '\\Model')) {
            # if this is an instance of the Model class, get the
            # table from the $table parameter
            throw new TableNotSpecifiedException('Model class must be attached to a database table.');
        } elseif (!$this->table) {
            # else, if $table is not set in the Model class file,
            # guess the table name
            $this->table = $this->table_name();
        }

        if (false === $this->db->table_exists($this->table)) {
            throw new TableNotFoundException("Table {$this->table} for model " . get_class($this) . " not found.");
        }

        # some metadata about the table
        $this->table_data['name'] = $this->table;
        $this->table_data['primary_key'] = $this->db->get_pk($this->table);
        $columns = $this->db->get_cols($this->table);
        $this->table_data['columns'] = $columns;
        $this->table_data['column_names'] = array_combine($columns, array_fill(0, count($columns), null));

        # initialize an empty record
        $this->record = $this->table_data['column_names'];

        if (!$this->primary_key) {
            $this->primary_key = $this->table_data['primary_key'];
        }

        foreach ($this->belongs_to as $alias => $info) {
            $this->attach(new BelongsTo($alias, $info));
        }

        foreach ($this->has_many as $alias => $info) {
            $this->attach(new HasMany($alias, $info));
        }
        
        foreach ($this->has_one as $alias => $info) {
            $this->attach(new HasOne($alias, $info));
        }

        foreach ($this->has_and_belongs_to_many as $alias => $info) {
            $this->attach(new HasAndBelongsToMany($alias, $info));
        }
    }

    /**
     * Auto-resolve the table name for the current model.
     * 
     * @return string
     */
    public function table_name()
    {
        if (!is_null($this->table)) {
            return $this->table;
        }

        $shortname = (new \ReflectionClass($this))->getShortName();
        $table_name = preg_replace('/Model$/', '', $shortname);

        return Str::underscores($table_name, true);
    }

    public function primary_key()
    {
        return $this->primary_key;
    }

    public function column_names()
    {
        return $this->table_data['column_names'];
    }

    /**
     * Get or set the table name for the model.
     * 
     * @param string $table Table name
     * @return string Table name
     */
    public function table($table = null)
    {
        if (!is_null($table)) {
            $this->table = $table;
        }

        return $this->table;
    }

    /**
     * Adds a has-many relationship to the model.
     *
     * @param type $alias The related table name or an alias if $foreign_key is an array
     * @param string|array $info The foreign key in this model's table,  or an 
     *     array with [table_name, FK_name]
     * @return Model The model object ($this)
     */
    public function add_child($alias, $info)
    {
        $this->attach(new HasMany($alias, $info));

        return $this;
    }

    /**
     * Adds a belongs-to relationship to the model.
     *
     * @param type $alias The related table name or an alias if $foreign_key is an array
     * @param string|array $info The foreign key in this model's table,  or an 
     *     array with [table_name, FK_name]
     * @return Model The model object ($this)
     */
    public function add_parent($alias, $info)
    {
        $this->attach(new BelongsTo($alias, $info));

        return $this;
    }

    /**
     * Adds a has-and-belongs-to-many relationship to the model.
     *
     * @param type $alias The related table name or an alias if $foreign_key is an array
     * @param string|array $info An array with [start_table_name, start_fk_name, end_fk_name, end_table_name]
     * @return Model The model object ($this)
     */
    public function add_sibling($alias, $info)
    {
        $this->attach(new HasAndBelongsToMany($alias, $info));

        return $this;
    }

    /**
     * Adds a ona-one relationship to the model.
     *
     * @param type $alias The related table name or an alias if $foreign_key is an array
     * @param string|array $info The foreign key in this model's table,  or an 
     *     array with [table_name, FK_name]
     * @return Model The model object ($this)
     */
    public function add_twin($alias, $info)
    {
        $this->attach(new HasOne($alias, $info));

        return $this;
    }

    /**
     * Configures related models.
     *
     * @param string $relationship_type Either 'child' or 'parent'
     * @param RelationshipInterface $alias Name of the relationship
     * @param string|array $fk The name of the FK or a array with [table_name, FK_name]
     * @return boolean false if the table does not exist, true otherwise
     */
    public function attach(RelationshipInterface $relationship)
    {
        $this->relationships[$relationship->alias()] = $relationship;
    }

    /**
     * Removes a has-many relationship from the model.
     *
     * @param string $alias The relationship alias
     * @return Model The model object
     */
    public function detach($alias)
    {
        if ($this->has_related($alias)) {
            unset($this->relationships[$alias]);
        }

        return $this;
    }

    /**
     * Checks if a relationship has been defined.
     * 
     * @param string $alias
     * @return boolean
     */
    public function has_related($alias)
    {
        return array_key_exists($alias, $this->relationships);
    }

    /**
     * Find by field name.
     *
     * This magic method manages find_by_<field name> and
     * find_all_by_<field_name> method calls.
     *
     * @param string $field Method to be called
     * @param array $arguments Argumments passed to the method
     * @return The return value of the method called
     */
    public function __call($field, $arguments)
    {
        $results = preg_match('/(find|find_all)_by_(.*)/', $field, $matches);

        if ($results) {
            $value = $arguments[0];
            list(, $method, $field) = $matches;

            return $this->$method([$field => $value]);
        } elseif ($model = Pew::instance()->model($field)) {
            return $model;
        }

        throw new \BadMethodCallException("Invalid method " . get_class($this) . "::$field() called.");
    }

    /**
     * Get an empty record.
     * 
     * @return Table A new record
     */
    public function create(array $attributes = [])
    {
        $class = '\\' . get_class($this);
        $blank = new $class($this->table, $this->db);
        $blank->attributes(array_merge($this->table_data['column_names'], $attributes));

        return $blank;
    }

    public function blank(array $attributes = [])
    {
        return $this->create($attributes);
    }

    /**
     * Get or set the current record values.
     *
     * This method will only update values for fields set in the $attributes 
     * argument.
     * 
     * @param array $attributes Associative array of column names and values
     * @return array An associative array of current column names and values
     */
    public function attributes(array $attributes = null)
    {
        if (!is_null($attributes)) {
            $base_fields = $this->table_data['column_names'];
            $this->record = array_intersect_key($attributes, $base_fields) + $base_fields;
        }
        
        return $this->record;
    }

    /**
     * Simple transitional function to run a query directly.
     *
     * This function interacts directly with the PDO abstraction layer of the
     * Database object. It invokes PDO::query() to run SELECT statements and
     * returns all rows, or invokes PDO::exec() for INSERT, UPDATE and DELETE
     * and return an integer with the number of affected rows.
     *
     * This function can run prepared statements using named placeholders (with
     * a colon) or anonymous placeholders (with a question mark).
     *
     * @param string $query The query to run
     * @param array $data Array of PDO placeholders and/or values
     * @return mixed An array of rows or an integer with the amount of affected rows
     */
    public function query($query, array $data = [])
    {
        # trim whitespace around the SQL
        $query = trim($query);
        # extract the SQL clause being used (SELECT, INSERT, etc...)
        $clause = strtoupper(strtok($query, ' '));

        # prepare the SQL query
        $stm = $this->db->pdo()->prepare($query);
        # run the prepared statement with the received keys and values
        $success = $stm->execute($data);
        
        if ($success) {
            if ($clause == 'SELECT') {
                # return an array of Models
                $result = $stm->fetchAll();

                foreach ($result as $key => $value) {
                    $result[$key] = clone $this;
                    $result[$key]->record = $value;
                }

                return $result;
            } else {
                # return number of affected rows
                return $stm->rowCount($query);
            }
        } else {
            throw new \RuntimeException("The $clause operation failed");
        }

        return false;
    }

    /**
     * Retrieve a single item from the model table using its primary key.
     *
     * @param int $id Value to match to the primary key of the model table, or
     *                an associative array with field name/ field value pairs.
     * @return Model A model instance
     */
    public function find($id)
    {
        # if $id is not numeric, use it as a conditions array
        if (is_array($id)) {
            $this->where($id);
        } else {
            $this->clauses['where'][$this->primary_key] = $id;
        }

        #query the database
        $result = $this->db
                        ->where($this->where())
                        ->group_by($this->group_by())
                        ->having($this->having())
                        ->limit($this->limit())
                        ->order_by($this->order_by())
                        ->single($this->table, $this->clauses['fields']);

        $this->reset();

        if ($result) {
            $this->record = array_merge($this->table_data['column_names'], (array) $result);
        } else {
            # if there was no result, return false
            $this->table_data['data'] = [];
            $result = $this->table_data['column_names'];
            return false;
        }

        foreach ($this->eager_load as $related_model) {
            $this->record[$related_model] = $this->offsetGet($related_model);
        }

        if (method_exists($this, 'after_find')) {
            $this->record = current($this->after_find([$result]));
        }

        return clone $this;
    }

    /**
     * Retrieve all items from a table.
     *
     * @param array $where An associative array with WHERE conditions.
     * @return Model[] An array with the resulting records
     */
    public function find_all($where = null)
    {
        # if conditions are provided, overwrite the previous model conditions
        if (is_array($where)) {
            $this->where($where);
        }

        # query the database
        $result = $this->db
                    ->where($this->where())
                    ->group_by($this->group_by())
                    ->having($this->having())
                    ->limit($this->limit())
                    ->order_by($this->order_by())
                    ->select($this->table, $this->clauses['fields']);

        $this->reset();

        if ($result) {
            foreach ($result as $key => $value) {
                $result[$key] = clone $this;
                $result[$key]->record = $value;

                foreach ($this->eager_load as $related_model) {
                    $result[$key]->record[$related_model] = $result[$key]->offsetGet($related_model);
                }
            }
        } else {
            # return an empty array if there was no result
            $result = [];
        }

        if (method_exists($this, 'after_find')) {
            $result = $this->after_find($result);
        }

        return $result;
    }

    /**
     * Count the rows that fit the criteria.
     *
     * @param array $where An associative array with field name/field value
     *                   pairs for the WHERE clause.
     * @return int
     */
    public function count($where = null)
    {
        # if conditions are provided, overwrite the previous model conditions
        if (is_array($where)) {
            $this->clauses['where'] = $where;
        }

        # query the database
        $result = $this->db
                    ->from($this->table)
                    ->where($this->clauses['where'])
                    ->group_by($this->clauses['group_by'])
                    ->having($this->clauses['having'])
                    ->limit(1)
                    ->cell('count(*)');

        return $result;
    }

    /**
     * Saves a row to the table.
     *
     * If the $primary_key field is set, it performs an UPDATE. If not, it
     * INSERTs the data.
     *
     * @param array $data An associative array with database fields and values
     * @return Model The saved item on success, false otherwise
     */
    public function save($data = null)
    {
        if (is_null($data)) {
            $data = $this->record;
        }

        if (method_exists($this, 'before_save')) {
            $data = $this->before_save($data);
        }

        $record = [];

        foreach ($this->table_data['columns'] as  $key) {
            if (array_key_exists($key, $data)) {
                $record[$key] = $data[$key];
            }
        }

        if (!$this->db->is_writable) {
            throw new \RuntimeException("Database file is not writable.");
        }
        
        if (isset($record[$this->primary_key])) {
            # set modification timestamp
            if ($this->has_column('modified')) {
                $record['modified'] = time();
            }

            # if $id is set, perform an UPDATE
            $result = $this->db->set($record)->where([$this->primary_key => $record[$this->primary_key]])->update($this->table);
            $result = $this->db->where([$this->primary_key => $record[$this->primary_key]])->single($this->table);
        } else {
            # set creation timestamp
            if ($this->has_column('created')) {
                $record['created'] = time();
            }

            # set modification timestamp
            if ($this->has_column('modified')) {
                $record['modified'] = time();
            }

            # if $id is not set, perform an INSERT
            $result = $this->db->values($record)->insert($this->table);
            $result = $this->db->where([$this->primary_key => $result])->single($this->table);
        }

        if (method_exists($this, 'after_save')) {
            $result = $this->after_save($result);
        }

        $this->record = array_merge($this->record, $result);
        $model = $this->create($this->record);

        return $model;
    }

    /**
     * Deletes one or more rows from the table.
     *
     * If the $primary_key field is set, it deletes the corresponding row. If
     * not, the $where field is used to delete conditionally. If $id is boolean
     * true, it clears the full table.
     *
     * @param mixed $id The value of the PK field of the row to delete, null to
     *                  use the model's $where conditions, or boolean true to
     *                  delete every record in the table
     * @return bool True on success, false other wise
     */
    public function delete($id = null)
    {
        if (is_array($id)) {
            # use the $id as an array of conditions
            return $this->db->where($id)->delete($this->table);
        } elseif ($id === true) {
            # this deletes everything in $this->table
            return $this->db->delete($this->table);
        } elseif (!is_null($id) && !is_bool($id)) {
            # delete the item as received, ignoring previous conditions
            return $this->db->where([$this->primary_key => $id])->limit(1)->delete($this->table);
        } elseif ($this->clauses['where']) {
            # delete everything that matches the conditions
            return $this->db->where($this->clauses['where'])->delete($this->table);
        } else {
            # no valid configuration
            throw new \RuntimeException('Delete requires conditions or parameters');
        }
    }

    /**
     * Set which related models to retrieve eagerly in a find() operaton.
     *
     * @param $related_model One or more related models
     * @return Model The model
     */
    public function with()
    {
        $self = $this;

        $this->eager_load = array_filter(func_get_args(), function($with) use ($self) {
            return $self->has_related($with);
        });

        return $this;
    }

    /**
     * Validate the data against a validator.
     * 
     * @param array $record Fields and values to validate
     * @param array $rules Validation configuration
     * @return array Validation errors
     */
    public function validate($record = null, array $rules = null)
    {
        if (is_null($rules)) {
            if (!property_exists($this, 'rules')) {
                throw new \RuntimeException("No valdation rules configured for model " . get_class($this));
            } else {
                $rules = $this->rules;
            }
        }

        if (is_null($record)) {
            $record = $this->record;
        }

        $validator = Pew::instance()->library('Validator', [$rules]);
        $validator->validate($record);

        return $validator->errors();
    }

    /**
     * Returns the primary key value created in the last INSERT statement.
     *
     * @return mixed The primaary key value of the last inserted row
     */
    public function last_insert_id()
    {
        return $this->db->pdo()->LastInsertId();
    }

    /**
     * State which fields to retrieve with find() and find_all().
     *
     * @param string $fields A comma-separated list of table columns
     * @return Model a reference to the same object, for method chaining
     */
    public function select($fields)
    {
        $this->clauses['fields'] = $fields;
        $this->db->fields($fields);

        return $this;
    }

    /**
     * State the conditions of the records to fetch with find() and find_all().
     *
     * @param array $conditions Field and value pairs
     * @return Model a reference to the same object, for method chaining
     */
    public function where($conditions = null)
    {
        if (!is_null($conditions)) {
            $this->clauses['where'] = $conditions;
            $this->db->where($conditions);

            return $this;
        } else {
            if (isset($this->clauses['where'])) {
                return $this->clauses['where'];
            } else {
                return $this->where;
            }
        }
    }

    /**
     * Specify the maximum amount of records to retrieve, and an optional
     * starting offset.
     *
     * @param int $count Number of items to return
     * @param int $start First item to return
     * @return Model a reference to the same object, for method chaining
     */
    public function limit($count = null, $start = 0)
    {
        if (is_numeric($count)) {
            if (isset($start) && is_numeric($start)) {
                $this->clauses['limit'] = "$start, $count";
            } else {
                $this->clauses['limit'] = $count;
            }

            return $this;
        } else {
            if (isset($this->clauses['limit'])) {
                return $this->clauses['limit'];
            } else {
                return $this->limit;
            }
        }
    }

    /**
     * Set the record sorting for results.
     *
     * @param mixed $order_by Order-by SQL clauses[multiple]
     * @return Model a reference to the same object, for method chaining
     */
    public function order_by($order_by = null)
    {
        if (!is_null($order_by)) {
            $this->clauses['order_by'] = $order_by;
            return $this;
        } else {
            if (isset($this->clauses['order_by'])) {
                return $this->clauses['order_by'];
            } else {
                return $this->order_by;
            }
        }
    }

    /**
     * This function is a shortcut to enable method chaining with the Group By
     * SQL clause.
     *
     * @param string $groups Grouping column names
     * @return Model a reference to the same object, for method chaining
     * @todo: Make this work
     */
    public function group_by($group_by = null)
    {
        if (!is_null($group_by)) {
            $this->clauses['group_by']= $group_by;
            return $this;
        } else {
            if (isset($this->clauses['group_by'])) {
                return $this->clauses['group_by'];
            } else {
                $this->group_by;
            }
        }
    }

    /**
     * This function is a shortcut to enable method chaining with the Having SQL
     * clause.
     *
     * @param string $conditions SQL conditions for the groups
     * @return Model a reference to the same object, for method chaining
     * @todo: Make this work
     */
    public function having($having = null)
    {
        if (!is_null($having)) {
            $this->clauses['having']= $having;
            return $this;
        } else {
            if (isset($this->clauses['having'])) {
                return $this->clauses['having'];
            } else {
                $this->having;
            }
        }
    }

    /**
     * Start a PDO transaction.
     * 
     * @return bool True on success, false on failure
     */
    public function begin()
    {
        return $this->db->pdo()->beginTransaction();
    }

    /**
     * Commit a PDO transaction.
     * 
     * @return bool True on success, false on failure
     */
    public function commit()
    {
        return $this->db->pdo()->commit();
    }

    /**
     * Roll back a PDO transaction.
     * 
     * @return bool True on success, false on failure
     */
    public function rollback()
    {
        return $this->db->pdo()->rollback();
    }

    /**
     * Reset the SQL clauses.
     * 
     * @return Model The model instance
     */
    protected function reset()
    {
        $this->clauses['fields'] = $this->fields;
        $this->clauses['where'] = $this->where;
        $this->clauses['order_by'] = $this->order_by;
        $this->clauses['group_by'] = $this->group_by;
        $this->clauses['having'] = $this->having;
        $this->clauses['limit'] = $this->limit;

        return $this;
    }

    /**
     * Get or set several SQL clauses.
     *
     * Accepted clauses are:
     *     - fields: comma-separated list of fields
     *     - where: array of conditions
     *     - group_by: comma-separated list of fields
     *     - having: array of conditions
     *     - order_by: comma-separated list of fields
     *     - limit: count, offset
     *
     * @param array $clauses
     */
    public function clauses(array $clauses = null)
    {
        if (!is_null($clauses)) {
            foreach ($clauses as $key => $value) {
                if (array_key_exists($key, $this->clauses)) {
                    $this->clauses[$key] = $value;
                }
            }
        }

        return $this->clauses;
    }

    /**
     * Check if the field exists in the model.
     * 
     * @param string $column_name
     * @return boolean
     */
    public function has_column($column_name)
    {
        return array_key_exists($column_name, $this->record);
    }

    /**
     * Check if an column or related model exists.
     * 
     * @return bool True if the offset exists, false otherwise
     */
    public function offsetExists($offset)
    {
        $has_column = $this->has_column($offset);
        $has_related = $this->has_related($offset);
        
        return $has_column || $has_related;
    }

    /**
     * Get a record field value or related model.
     * 
     * @return mixed The value at the offset.
     * @throws \InvalidArgumentException When the offset does not exist
     */
    public function offsetGet($offset)
    {
        # check if the offset is a table field or a relationship alias
        if (!$this->offsetExists($offset)) {
            throw new \InvalidArgumentException("Invalid model field " . get_class($this) . '::' . $offset);
        }

        # the offset is a table field
        if (isSet($this->record[$offset])) {
            return $this->record[$offset];
        }

        # the offset is a relationship
        if (isSet($this->relationships[$offset])) {
            $relationship = $this->relationships[$offset];
            # get the name of the FK -- it will be this table's PK in a belongs-to relationship
            $fk = $this->record[$relationship->key() ?: $this->primary_key()];
            
            if (!is_null($fk)) {
                $related_model = Pew::instance()->model($relationship->table());
                # let the relationship resolve the call to the related table
                $this->record[$offset] = $relationship->fetch($related_model, $fk);
            } else {
                $this->record[$offset] = null;
            }

            return $this->record[$offset];
        }

        return null;
    }

    /**
     * Set the value of a record column.
     * 
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        $this->record[$offset] = $value;
    }

    /**
     * Remove an offset.
     *
     * ArrayAccess implementation.
     *
     * @param string $offset The offset to remove
     */
    public function offsetUnset($offset)
    {
        unset($this->record[$offset]);
    }

    /**
     * Set a value in the registry.
     * 
     * @param string $key Key for the value
     * @param mixed Value to store
     */
    public function __set($key, $value)
    {
        return $this->offsetSet($key, $value);
    }

    /**
     * Get a stored value from the registry.
     * 
     * @param mixed $key Key for the value
     * @return mixed Stored value
     */
    public function __get($key)
    {
        return $this->offsetGet($key);
    }

    /**
     * Check if a key is in use.
     * 
     * @param mixed $key Key to check
     * @return bool True if the key has been set, false otherwise.
     */
    public function __isset($key)
    {
        return $this->offsetExists($key);
    }

    /**
     * Remove a stored value from the registry.
     * 
     * @param mixed $key Key to delete
     */
    public function __unset($key)
    {
        $this->offsetUnset($key);
    }

    /**
     * Allow iteration over the current record fields.
     * 
     * @return ArrayIterator
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->record);
    }

    /**
     * JSON representation of the model object.
     * 
     * @return array
     */
    public function jsonSerialize()
    {
        return $this->attributes();
    }

    /**
     * Disconnect the PDO instance before serialization.
     * 
     * @return array
     */
    public function __sleep()
    {
        $this->db = null;
        return array_keys(get_object_vars($this));
    }
}
