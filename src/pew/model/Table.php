<?php

namespace pew\model;

use ifcanduela\db\Database;
use pew\model\exception\TableNotSpecifiedException;
use pew\model\exception\TableNotFoundException;
use Stringy\StaticStringy as Str;

use ifcanduela\db\Query;

/**
 * Table gateway class.
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
    protected static $tableData = [];

    /**
     * Current resultset.
     *
     * Holds an index for each record in the last resultset.
     *
     * @var array
     */
    protected $record = [];

    /**
     * Class name for records in the table.
     *
     * @var string
     */
    protected $recordClass;

    /**
     * Method used to fetch related records.
     *
     * @var string
     */
    protected $fetchMethod = 'one';

    /**
     * Method used to fetch related records.
     *
     * @var string
     */
    protected $fetchCondition;

    /** @var \ifcanduela\db\Query */
    public $query;

    /**
     * The constructor builds the model!.
     *
     * @param string $table Name of the table
     * @param Database $db Database instance to use
     * @param string|null $recordClass
     */
    public function __construct(string $table, Database $db = null, string $recordClass = null)
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
    public function init(string $table, Database $db = null)
    {
        # get the Database class instance
        $this->db = is_null($db) ? pew('db') : $db;
        $this->table = $table;

        if (!isset(static::$tableData[$this->table])) {
            if (!$this->db->tableExists($this->table)) {
                throw new TableNotFoundException("Table {$this->table} for model " . get_class($this) . " not found.");
            }

            # some metadata about the table
            $primary_key = $this->db->getPrimaryKeys($this->table);
            $columns = $this->db->getColumnNames($this->table);
            static::$tableData[$this->table]['name'] = $this->table;
            static::$tableData[$this->table]['primary_key'] = $primary_key;
            static::$tableData[$this->table]['columns'] = $columns;
            static::$tableData[$this->table]['column_names'] = array_combine($columns, array_fill(0, count($columns), null));
        }
    }

    /**
     * Get the name of the primary key column.
     *
     * @return string
     */
    public function primaryKey()
    {
        return static::$tableData[$this->table]['primary_key'];
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
    public function columnNames($as_keys = true)
    {
        return $as_keys
            ? static::$tableData[$this->table]['column_names']
            : array_keys(static::$tableData[$this->table]['column_names']);
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
     * Get an empty record.
     *
     * @param array $attributes
     * @return Table A new record
     */
    public function create(array $attributes = [])
    {
        $class = '\\' . get_class($this);
        $blank = new $class($this->table, $this->db);
        $blank->attributes(array_merge($this->columnNames(), $attributes));

        return $blank;
    }

    /**
     * @return self
     */
    public function createSelect() {
        $this->query = Query::select();
        $this->query->from($this->table);

        return $this;
    }

    /**
     * @return self
     */
    public function createUpdate() {
        $this->query = Query::update();
        $this->query->table($this->table);

        return $this;
    }

    /**
     * @return self
     */
    public function createInsert() {
        $this->query = Query::insert();
        $this->query->into($this->table);

        return $this;
    }

    /**
     * @return self
     */
    public function createDelete()
    {
        $this->query = Query::delete();
        $this->query->table($this->table);

        return $this;
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
        $stm = $this->db->prepare($query);
        # run the prepared statement with the received keys and values
        $success = $stm->execute($data);

        if ($success) {
            if ($clause == 'SELECT') {
                # return an array of Models
                $result = $stm->fetchAll();

                return $result;
            }

            return $stm->rowCount();
        }

        throw new \RuntimeException("The $clause operation failed");
    }

    public function one()
    {
        $this->limit(1);
        $className = $this->recordClass;
        $model = null;

        $records = $this->db->run($this->query);

        if (isset($records[0])) {
            $model = $className::fromArray($records[0]);
        }

        return $model;
    }

    /**
     * Fetch a list of records.
     *
     * @return array
     */
    public function all()
    {
        $className = $this->recordClass;
        $models = [];
        $records = $this->db->run($this->query);

        foreach ($records as $record) {
            $models[] = $className::fromArray($record);
        }

        return $models;
    }

    /**
     * Count the rows that fit the criteria.
     *
     * @return int
     */
    public function count()
    {
        # query the database
        $this->query->columns('COUNT(*) as count');
        $result = $this->db->run($this->query);

        return $result[0]['count'];
    }

    /**
     * Saves a row to the table.
     *
     * If the $primary_key field is set, it performs an UPDATE. If not, it
     * INSERTs the data.
     *
     * @param Record|array $model An array or array-like object with column names and values
     * @return array The saved item on success, false otherwise
     */
    public function save(Record $model)
    {
        if (method_exists($model, 'beforeSave')) {
            $model->beforeSave();
        }

        $attributes = $model->attributes();
        $record = $result = [];

        foreach (static::$tableData[$this->table]['columns'] as $key) {
            if (array_key_exists($key, $attributes)) {
                $record[$key] = $attributes[$key];
            }
        }

        if (!$this->db->isWritable()) {
            throw new \RuntimeException("Database file is not writable.");
        }

        $primaryKeyName = $this->primaryKey();

        if (empty($record[$primaryKeyName])) {
            # unset the primary key, just in case
            unset($record[$primaryKeyName]);

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
            $query = Query::insert()->into($this->table)->values($record);
            $this->db->run($query);
            $id = $this->db->lastInsertId();
        } else {
            # set modification timestamp
            if ($this->hasColumn('modified')) {
                $record['modified'] = time();
            }

            if ($this->hasColumn('updated')) {
                $record['updated'] = time();
            }

            # if $id is set, perform an UPDATE
            $where = [$primaryKeyName => $record[$primaryKeyName]];
            $query = Query::update($this->table)->set($record)->where($where);
            $this->db->run($query);
            $id = $record[$primaryKeyName];
        }

        $model = $this->createSelect()->from($this->table)->where([$primaryKeyName => $id])->one();

        if (method_exists($model, 'afterSave')) {
            $model->afterSave();
        }

        return $model->attributes();
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
        $query = Query::delete($this->table);

        if (is_array($id)) {
            # use the $id as an array of conditions
            $query->where([$this->primaryKey() => $id]);
            return $this->db->run($query);
        } elseif ($id === true) {
            # this deletes everything in $this->table
            return $this->db->run($query);
        } else {
            return $this->db->run($this->query->where([$this->primaryKey() => $id]));
        }

        throw new \RuntimeException('Delete requires conditions or parameters');
    }

    /**
     * Returns the primary key value created in the last INSERT statement.
     *
     * @return mixed The primary key value of the last inserted row
     */
    public function lastInsertId()
    {
        return $this->db->lastInsertId();
    }

    /**
     * Start a PDO transaction.
     *
     * @return bool True on success, false on failure
     */
    public function begin()
    {
        return $this->db->beginTransaction();
    }

    /**
     * Commit a PDO transaction.
     *
     * @return bool True on success, false on failure
     */
    public function commit()
    {
        return $this->db->commit();
    }

    /**
     * Roll back a PDO transaction.
     *
     * @return bool True on success, false on failure
     */
    public function rollback()
    {
        return $this->db->rollback();
    }

    /**
     * Check if the field exists in the table.
     *
     * @param string $column_name
     * @return boolean
     */
    public function hasColumn(string $column_name)
    {
        return array_key_exists($column_name, static::$tableData[$this->table]['column_names']);
    }

    /**
     * Run a query.
     *
     * This method should be used after creating a query with createSelect(), createUpdate(),
     * createInsert() or createDelete().
     *
     * @return array
     */
    public function run()
    {
        return $this->db->run($this->query);
    }

    /**
     * Redirect method calls to the child Query object.
     *
     * @param string $method
     * @param array $arguments
     *
     * @return self
     */
    public function __call($method, $arguments)
    {
        if (!$this->query) {
            throw new \RuntimeException("Method '{$method}' called before initializing a query");
        }

        if (method_exists($this->query, $method)) {
            $this->query->$method(...$arguments);

            return $this;
        }

        throw new \BadMethodCallException("Invalid method '{$method}'");
    }

    /**
     * Set the fetch mode for related models.
     *
     * @return self
     */
    public function hasMany()
    {
        $this->fetchMethod = 'all';

        return $this;
    }

    /**
     * Set the fetch mode for related models.
     *
     * @return self
     */
    public function belongsTo()
    {
        $this->fetchMethod = 'one';

        return $this;
    }

    /**
     * Set a condition for fetching related models.
     *
     * This method is for internal use only.
     *
     * @param array $condition
     * @return self
     */
    public function fetchCondition(array $condition)
    {
        $this->fetchCondition = $condition;

        return $this;
    }

    /**
     * Fetch record for a defined relationship.
     *
     * This method is for internal use only.
     *
     * @return array|\pew\Model|null
     */
    public function fetch()
    {
        $this->andWhere($this->fetchCondition);

        if ($this->fetchMethod === 'all') {
            return $this->all();
        }

        return $this->one();
    }
}
