<?php

namespace pew\model;

use pew\libs\Database;
use pew\model\exception\TableNotSpecifiedException;
use pew\model\exception\TableNotFoundException;

use Stringy\StaticStringy;

/**
 * Table gateway class.
 *
 * @package pew\db
 * @author ifcanduela <ifcanduela@gmail.com>
 */
class Table
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
    protected $primaryKey = null;

    /**
     * Miscellaneous table metadata.
     *
     * Holds table name, primary key name, column names, primary text column
     * name (either 'name' or 'title') and values.
     *
     * @var array
     */
    protected $tableData = [];

    /**
     * Current resultset.
     *
     * Holds an index for each record in the last resultset.
     *
     * @var array
     */
    protected $record = [];

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

    protected $recordClass;

    /**
     * The constructor builds the model!.
     *
     * @param string $table Name of the table
     * @param Database $db Database instance to use
     */
    public function __construct($table = null, Database $db = null, $recordClass = null)
    {
        $this->recordClass = $recordClass;
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
        $this->db = is_null($db) ? pew('db') : $db;
        $this->table = $table ?: $this->table_name();

        if (!$this->db->table_exists($this->table)) {
            throw new TableNotFoundException("Table {$this->table} for model " . get_class($this) . " not found.");
        }

        # some metadata about the table
        $this->tableData['name'] = $this->table;
        $this->tableData['primary_key'] = $this->db->get_pk($this->table);
        $columns = $this->db->get_cols($this->table);
        $this->tableData['columns'] = $columns;
        $this->tableData['column_names'] = array_combine($columns, array_fill(0, count($columns), null));

        # initialize an empty record
        $this->record = $this->tableData['column_names'];

        if (!$this->primaryKey) {
            $this->primaryKey = $this->tableData['primary_key'];
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

        if (!$table_name) {
            throw new TableNotSpecifiedException("Model class must be attached to a database table.");
        }

        return Str::underscored($table_name, true);
    }

    /**
     * Get the name of the primary key column.
     * 
     * @return string
     */
    public function primaryKey()
    {
        return $this->primaryKey;
    }

    /**
     * Get the list of column names.
     *
     * If $as_keys is false, the column names will be returned as values in an 
     * array, otherwise they will be key names in an associative array.
     *
     * @param boolean $as_keys Return the column names as keys in an associative array.
     * @return array
     */
    public function column_names($as_keys = true)
    {
        return $as_keys 
            ? $this->tableData['column_names'] 
            : array_keys($this->tableData['column_names']);
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
     * Get an empty record.
     * 
     * @return Table A new record
     */
    public function create(array $attributes = [])
    {
        $class = '\\' . get_class($this);
        $blank = new $class($this->table, $this->db);
        $blank->attributes(array_merge($this->column_names(), $attributes));

        return $blank;
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

    public function one()
    {
        $this->limit(1);
        $className = $this->recordClass;

        $record = $this->db
            ->where($this->where())
            ->group_by($this->groupBy())
            ->having($this->having())
            ->order_by($this->orderBy())
            ->single($this->table, $this->clauses['fields']);
        $this->reset();

        $model = false;

        if ($record) {
            $model = new $className();
            $model->attributes($record);
        }

        return $model;
    }

    public function all(): array
    {
        $className = $this->recordClass;

        $records = $this->db
            ->where($this->where())
            ->group_by($this->groupBy())
            ->having($this->having())
            ->limit($this->limit())
            ->order_by($this->orderBy())
            ->select($this->table, $this->clauses['fields']);
        $this->reset();

        $models = [];

        foreach ($records as $record) {
            $model = new $className();
            $model->attributes($record);
            $models[] = $model;
        }

        return $models;
    }

    /**
     * Count the rows that fit the criteria.
     *
     * @param array $where An associative array with field name/field value pairs for the WHERE clause.
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
    public function save(Record $record)
    {
        if (method_exists($record, 'beforeSave')) {
            $data->beforeSave();
        }

        $record = $data->attributes();

        foreach ($this->tableData['columns'] as  $key) {
            if (array_key_exists($key, $data)) {
                $record[$key] = $data[$key];
            }
        }

        if (!$this->db->is_writable) {
            throw new \RuntimeException("Database file is not writable.");
        }
        
        if (isset($record[$this->tableData['primary_key']])) {
            # set modification timestamp
            if ($this->hasColumn('modified')) {
                $record['modified'] = time();
            }

            if ($this->hasColumn('updated')) {
                $record['updated'] = time();
            }

            # if $id is set, perform an UPDATE
            $result = $this->db->set($record)->where([$this->tableData['primary_key'] => $record[$this->tableData['primary_key']]])->update($this->table);
            $result = $this->db->where([$this->tableData['primary_key'] => $record[$this->tableData['primary_key']]])->single($this->table);
        } else {
            # set creation timestamp
            if ($this->hasColumn('created')) {
                $record['created'] = time();
            }

            # set modification timestamp
            if ($this->hasColumn('modified')) {
                $record['modified'] = time();
            }

            if ($this->hasColumn('updated')) {
                $record['updated'] = time();
            }

            # if $id is not set, perform an INSERT
            $result = $this->db->values($record)->insert($this->table);
            $result = $this->db->where([$this->tableData['primary_key'] => $result])->single($this->table);
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
            return $this->db->where([$this->tableData['primary_key'] => $id])->limit(1)->delete($this->table);
        } elseif ($this->clauses['where']) {
            # delete everything that matches the conditions
            return $this->db->where($this->clauses['where'])->delete($this->table);
        } else {
            # no valid configuration
            throw new \RuntimeException('Delete requires conditions or parameters');
        }
    }

    /**
     * Returns the primary key value created in the last INSERT statement.
     *
     * @return mixed The primaary key value of the last inserted row
     */
    public function lastInsertId()
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
    public function orderBy($order_by = null)
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
    public function groupBy($group_by = null)
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
     * Check if the field exists in the table.
     * 
     * @param string $column_name
     * @return boolean
     */
    public function hasColumn(string $column_name): bool
    {
        return in_array($column_name, $this->tableData['columns'], true);
    }
}
