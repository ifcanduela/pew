<?php

namespace pew\model;

use pew\model\exception\TableNotSpecifiedException;
use pew\model\exception\TableNotFoundException;
use pew\model\relation\Relationship;

use ifcanduela\db\Database;
use ifcanduela\db\Query;
use ReflectionClass;
use Stringy\Stringy as Str;

/**
 * Table gateway class.
 *
 * @method Table columns(string ...$columns)
 * @method Table from(string ...$columns)
 * @method Table where(array $conditions)
 * @method Table andWhere(array $conditions)
 * @method Table orWhere(array $conditions)
 * @method Table join(string $through, array $on)
 * @method Table innerJoin(string $through, array $on)
 * @method Table leftJoin(string $through, array $on)
 * @method Table leftOuterJoin(string $through, array $on)
 * @method Table rightJoin(string $through, array $on)
 * @method Table outerJoin(string $through, array $on)
 * @method Table fullOuterJoin(string $through, array $on)
 * @method Table groupBy(string ...$columns)
 * @method Table having(array $conditionss)
 * @method Table andHaving(array $conditionss)
 * @method Table orHaving(array $conditionss)
 * @method Table orderBy(string $fieldAndDirection)
 * @method Table limit(int $limit)
 * @method Table offset(int $offset)
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
    protected $tableName = null;

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
     * Class name for records in the table.
     *
     * @var string
     */
    protected $recordClass;

    /**
     * Current selection query.
     *
     * @var \ifcanduela\db\Query
     */
    public $query;

    /**
     * List of relationships to fetch eagerly.
     *
     * @var array
     */
    protected $relationships = [];

    /**
     * Create a table gateway object.
     *
     * @param string $tableName Name of the table
     * @param Database $db Database instance to use
     * @param string|null $recordClass
     */
    public function __construct(string $tableName, Database $db, string $recordClass = null)
    {
        $this->tableName = $tableName;
        $this->db = $db;
        $this->recordClass = $recordClass;

        $this->init();
    }

    /**
     * Initialize a database table gateway.
     */
    public function init()
    {
        if (!$this->db->tableExists($this->tableName)) {
            throw new TableNotFoundException("Table {$this->tableName} for model {$this->recordClass} not found.");
        }

        # some metadata about the table
        $this->tableData["name"] = $this->tableName;

        if (!isset($this->tableData["primary_key"])) {
            $primaryKey = $this->db->getPrimaryKeys($this->tableName);
            $this->tableData["primary_key"] = $primaryKey;
        }

        if (!isset($this->tableData["columns"])) {
            $columns = $this->db->getColumnNames($this->tableName);
            $this->tableData["columns"] = $columns;
            $this->tableData["column_names"] = array_combine($columns, array_fill(0, count($columns), null));
        }
    }

    /**
     * Auto-resolve the table name for the current model.
     *
     * @param string $tableName
     * @return string
     */
    public function tableName(string $tableName = null)
    {
        if (isset($tableName)) {
            $this->tableName = $tableName;
        }

        return $this->tableName;
    }

    /**
     * Get the name of the primary key column.
     *
     * @return string
     */
    public function primaryKey()
    {
        return $this->tableData["primary_key"];
    }

    /**
     * Get the list of column names.
     *
     * If $as_keys is false, the column names will be returned as values in an
     * array, otherwise they will be key names in an associative array.
     *
     * @param boolean $asKeys Return the column names as keys in an associative array.
     * @return array
     */
    public function columnNames($asKeys = true)
    {
        return $asKeys
            ? $this->tableData["column_names"]
            : array_keys($this->tableData["column_names"]);
    }

    /**
     * Initialize a SELECT query.
     *
     * @return self
     */
    public function createSelect()
    {
        $this->query = Query::select();
        $this->query->from($this->tableName);

        return $this;
    }

    /**
     * Initialize an UPDATE query.
     *
     * @return self
     */
    public function createUpdate()
    {
        $this->query = Query::update();
        $this->query->table($this->tableName);

        return $this;
    }

    /**
     * Initialize an INSERT query.
     *
     * @return self
     */
    public function createInsert()
    {
        $this->query = Query::insert();
        $this->query->into($this->tableName);

        return $this;
    }

    /**
     * Initialize a DELETE query.
     *
     * @return self
     */
    public function createDelete()
    {
        $this->query = Query::delete();
        $this->query->table($this->tableName);

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
        $clause = strtoupper(strtok($query, " "));

        # prepare the SQL query
        $stm = $this->db->prepare($query);
        # run the prepared statement with the received keys and values
        $success = $stm->execute($data);

        if ($success) {
            if ($clause == "SELECT") {
                # return an array of Models
                $result = $stm->fetchAll();

                return $result;
            }

            return $stm->rowCount();
        }

        throw new \RuntimeException("The $clause operation failed");
    }

    /**
     * Set or get the class of the records managed by the table.
     *
     * @param string|null $recordClass
     * @return string|null
     */
    public function recordClass(string $recordClass = null)
    {
        if ($recordClass) {
            $this->recordClass = $recordClass;
        }

        return $this->recordClass;
    }

    /**
     * Retrieve one record matching the query.
     *
     * @return Record|null
     */
    public function one()
    {
        $this->limit(1);
        $className = $this->recordClass;
        $model = null;

        $records = $this->db->run($this->query);

        if (isset($records[0])) {
            $model = $className::fromArray($records[0]);
        }

        if ($this->relationships) {
            $this->loadRelationships([$model]);
        }

        return $model;
    }

    /**
     * Fetch a list of records.
     *
     * @return Collection
     */
    public function all()
    {
        $className = $this->recordClass;
        $models = [];
        $records = $this->db->run($this->query);

        foreach ($records as $record) {
            $models[] = $className::fromArray($record);
        }

        if ($this->relationships) {
            $this->loadRelationships($models);
        }

        return new Collection($models);
    }

    /**
     * Count the rows that fit the criteria.
     *
     * @return int
     */
    public function count()
    {
        # query the database
        $this->query->columns("COUNT(*) as count");
        $result = $this->db->run($this->query);

        return $result[0]["count"];
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
        if (method_exists($model, "beforeSave")) {
            $model->beforeSave();
        }

        $attributes = $model->attributes();
        $record = $result = [];

        foreach ($this->tableData["columns"] as $key) {
            if (array_key_exists($key, $attributes)) {
                $record[$key] = $attributes[$key];
            }
        }

        if (!$this->db->isWritable()) {
            throw new \RuntimeException("Database file is not writable.");
        }

        $primaryKeyName = $this->primaryKey();

        if (empty($record[$primaryKeyName])) {
            $id = $this->insertRecord($record, $model::$createdFieldName);
        } else {
            $id = $this->updateRecord($record, $model::$updatedFieldName);
        }

        if (method_exists($model, "afterSave")) {
            $model->afterSave();
        }

        $model = $this->createSelect()->from($this->tableName)->where([$primaryKeyName => $id])->one();


        return $model->attributes();
    }

    /**
     * Inserts a record into the table.
     *
     * @param array $record An array or array-like object with column names and values
     * @param string $fieldName The name of the column that stores the creation timestamp
     * @return mixed The primary key value of the inserted item.
     */
    protected function insertRecord(array $record, string $fieldName)
    {
        $primaryKeyName = $this->primaryKey();

        # unset the primary key, just in case
        unset($record[$primaryKeyName]);

        # set creation timestamp
        if ($this->hasColumn($fieldName)) {
            $record[$fieldName] = time();
        }

        $query = Query::insert()->into($this->tableName)->values($record);
        $this->db->run($query);

        return $this->db->lastInsertId();
    }

    /**
     * Updates a record in the table.
     *
     * @param array $record An array or array-like object with column names and values
     * @param string $fieldName The name of the column that stores the update timestamp
     * @return mixed The primary key value of the updated item.
     */
    protected function updateRecord(array $record, string $fieldName)
    {
        $primaryKeyName = $this->primaryKey();

        # set modification timestamp
        if ($this->hasColumn($fieldName)) {
            $record[$fieldName] = time();
        }

        # if $id is set, perform an UPDATE
        $where = [$primaryKeyName => $record[$primaryKeyName]];
        $query = Query::update($this->tableName)->set($record)->where($where);
        $this->db->run($query);

        return $record[$primaryKeyName];
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
        $query = Query::delete($this->tableName);

        if (is_array($id)) {
            # use the $id as an array of conditions
            $query->where([$this->primaryKey() => $id]);
            return $this->db->run($query);
        } elseif ($id === true) {
            # this deletes everything in $this->table
            return $this->db->run($query);
        } elseif ($id !== null) {
            return $this->db->run($this->query->where([$this->primaryKey() => $id]));
        }

        throw new \RuntimeException("Delete requires conditions or parameters");
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
     * @param string $columnName
     * @return boolean
     */
    public function hasColumn(string $columnName)
    {
        return array_key_exists($columnName, $this->tableData["column_names"]);
    }

    /**
     * Run a query.
     *
     * This method should be used after creating a query with createSelect(), createUpdate(),
     * createInsert() or createDelete().
     *
     * @return array|int|\PdoStatement
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
     * Specify relationships to eager-load.
     *
     * @param string|string[] ...$relationships
     * @return self
     */
    public function with(...$relationships)
    {
        $this->relationships = $relationships;

        return $this;
    }

    /**
     * Eager-load relationships.
     *
     * @param array $models
     * @return void
     */
    protected function loadRelationships(array $models)
    {
        static $depth = 0;

        $depth++;

        if ($models && $depth < 5) {
            $className = get_class($models[0]);
            $ref = new $className;

            foreach ($this->relationships as $relationshipFieldName) {
                $getterMethodName = "get" . Str::create($relationshipFieldName)->uppercamelize();

                /** @var Relationship $relationship */
                $relationship = $ref->$getterMethodName();
                $groupingField = $relationship->getGroupingField();

                if ($relationship instanceof Relationship) {
                    $relatedKeys = array_map(function ($r) use ($groupingField) {
                        return $r->$groupingField;
                    }, $models);

                    $grouped = $relationship->find($relatedKeys);

                    foreach ($models as $model) {
                        $keyValue = $model->{$groupingField};

                        if (isset($grouped[$keyValue])) {
                            $model->attachRelated($getterMethodName, $grouped[$keyValue]);
                        }
                    }
                }
            }
        }

        $depth--;
    }
}
