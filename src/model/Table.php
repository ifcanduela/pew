<?php

declare(strict_types=1);

namespace pew\model;

use BadMethodCallException;
use Exception;
use Error;
use ifcanduela\db\Database;
use ifcanduela\db\Query;
use PDOException;
use PdoStatement;
use pew\model\exception\TableNotFoundException;
use pew\model\relation\HasAndBelongsToMany;
use pew\model\relation\HasMany;
use pew\model\relation\Relationship;
use RuntimeException;

use function pew\str;

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
 * @method Table having(array $conditions)
 * @method Table andHaving(array $conditions)
 * @method Table orHaving(array $conditions)
 * @method Table orderBy(string $fieldAndDirection)
 * @method Table limit(int $limit)
 * @method Table offset(int $offset)
 * @method Table set(array $values)
 * @method Table into(string $table)
 * @method Table values(array ...$values)
 */
class Table
{
    /**
     * Database abstraction instance.
     */
    public Database $db;

    /**
     * Database table for the subject of the model.
     */
    protected string $tableName = "";

    /**
     * Name of the primary key fields of the table the Model manages.
     */
    protected string $primaryKey = "";

    /**
     * Miscellaneous table metadata.
     *
     * Holds table name, primary key name, column names, primary text column
     * name (either 'name' or 'title') and values.
     */
    protected array $tableData = [];

    /**
     * Class name for records in the table.
     */
    protected string $recordClass = "";

    /**
     * Current selection query.
     */
    public Query $query;

    /**
     * List of relationships to fetch eagerly.
     */
    protected array $relationships = [];

    /**
     * List of column names for tables.
     */
    protected static array $columnCache = [];

    /**
     * List of primary key column names for tables.
     */
    protected static array $primaryKeyCache = [];

    /**
     * Create a table gateway object.
     *
     * @param string $tableName Name of the table
     * @param Database $db Database instance to use
     * @param string $recordClass
     */
    public function __construct(string $tableName, Database $db, string $recordClass = "")
    {
        $this->tableName = $tableName;
        $this->db = $db;
        $this->recordClass = $recordClass;

        $this->init();
        $this->createSelect();
    }

    /**
     * Initialize a database table gateway.
     *
     * @return void
     */
    public function init(): void
    {
        if (!$this->db->tableExists($this->tableName)) {
            throw new TableNotFoundException("Table `$this->tableName` not found");
        }

        // Some metadata about the table
        $this->tableData["name"] = $this->tableName;

        if (!isset($this->tableData["primary_key"])) {
            if (!isset(static::$primaryKeyCache[$this->tableName])) {
                static::$primaryKeyCache[$this->tableName] = $this->db->getPrimaryKeys($this->tableName);
            }

            $this->tableData["primary_key"] = static::$primaryKeyCache[$this->tableName];
        }

        if (!isset($this->tableData["columns"])) {
            if (!isset(static::$columnCache[$this->tableName])) {
                $columns = $this->db->getColumnNames($this->tableName);
                static::$columnCache[$this->tableName]["columns"] = $columns;
                static::$columnCache[$this->tableName]["column_names"] = array_combine($columns, array_fill(0, count($columns), null));
            }

            $tableData = static::$columnCache[$this->tableName];

            $this->tableData["columns"] = $tableData["columns"];
            $this->tableData["column_names"] = $tableData["column_names"];
        }
    }

    /**
     * Get or set the table name for the current model.
     *
     * @param string $tableName
     * @return string
     */
    public function tableName(string $tableName = ""): string
    {
        if (mb_strlen($tableName)) {
            $this->tableName = $tableName;
        }

        return $this->tableName;
    }

    /**
     * Get the name of the primary key column.
     *
     * @return string
     */
    public function primaryKey(): string
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
    public function columnNames(bool $asKeys = true): array
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
    public function createSelect(): static
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
    public function createUpdate(): static
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
    public function createInsert(): static
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
    public function createDelete(): static
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
     * @return array|int An array of rows or an integer with the amount of affected rows
     * @throws PDOException When the query fails
     */
    public function query(string $query, array $data = []): int|array
    {
        // Trim whitespace around the SQL
        $query = trim($query);
        // Extract the SQL clause being used (SELECT, INSERT, etc...)
        $clause = mb_strtoupper(strtok($query, " "));

        // Prepare the SQL query
        $stm = $this->db->prepare($query);

        // Run the prepared statement with the received keys and values
        $stm->execute($data);

        if ($clause == "SELECT") {
            // Return an array of Models
            return $stm->fetchAll();
        }

        return $stm->rowCount();
    }

    /**
     * Set or get the class of the records managed by the table.
     *
     * @param string|null $recordClass
     * @return string
     */
    public function recordClass(string $recordClass = null): string
    {
        if ($recordClass) {
            $this->recordClass = $recordClass;
        }

        return $this->recordClass;
    }

    /**
     * Retrieve one record matching the query.
     *
     * If a class for the retrieved record cannot be found, an array will be returned.
     *
     * @return ActiveRecord|Record|array|null
     */
    public function one(): ActiveRecord|Record|array|null
    {
        $this->limit(1);
        $result = $this->all();

        return $result->count() ? $result->first() : null;
    }

    /**
     * Fetch a list of records.
     *
     * If a class for the retrieved records cannot be found, arrays will be returned.
     *
     * @return Collection<Record|ActiveRecord>
     */
    public function all(): Collection
    {
        $className = $this->recordClass;
        $models = [];
        $records = $this->db->run($this->query);

        foreach ($records as $record) {
            $models[] = $className ? $className::fromArray($record, false) : $record;
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
    public function count(): int
    {
        // Clone the current query
        $query = clone $this->query;
        // Replace the column list with COUNT(*)
        $query->columns("COUNT(*) as row_count");
        // Remove limit and offset
        $query->limit(0, 0);
        // Query the database
        $result = $this->db->run($query);

        return (int) $result[0]["row_count"];
    }

    /**
     * Saves a row to the table.
     *
     * If the $primary_key field is set, it performs an UPDATE. If not, it
     * INSERTs the data.
     *
     * @param ActiveRecord $model
     * @return array The record attributes on success, false otherwise
     */
    public function save(ActiveRecord $model): array
    {
        if (method_exists($model, "beforeSave")) {
            $model->beforeSave();
        }

        $attributes = $model->attributes();
        $record = [];

        foreach ($this->tableData["columns"] as $key) {
            if (array_key_exists($key, $attributes)) {
                $record[$key] = $attributes[$key];
            }
        }

        if (!$this->db->isWritable()) {
            throw new RuntimeException("Database file is not writable.");
        }

        $primaryKeyName = $this->primaryKey();

        if ($model->isNew) {
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
     * @param ?string $timestampField The name of the column that stores the creation timestamp
     * @return false|string The primary key value of the inserted item.
     */
    protected function insertRecord(array $record, string $timestampField = null): bool|string
    {
        // Set creation timestamp
        if ($timestampField && $this->hasColumn($timestampField)) {
            $record[$timestampField] = time();
        }

        $query = Query::insert()->into($this->tableName)->values($record);
        $this->db->run($query);

        return $this->db->lastInsertId();
    }

    /**
     * Updates a record in the table.
     *
     * @param array $record An array or array-like object with column names and values
     * @param ?string $timestampField The name of the column that stores the update timestamp
     * @return mixed The primary key value of the updated item.
     */
    protected function updateRecord(array $record, string $timestampField = null): mixed
    {
        $primaryKeyName = $this->primaryKey();

        // Set modification timestamp
        if ($timestampField && $this->hasColumn($timestampField)) {
            $record[$timestampField] = time();
        }

        // If $id is set, perform an UPDATE
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
     * @param mixed|null $id The value of the PK field of the row to delete, null to
     *                  use the model's $where conditions, or boolean true to
     *                  delete every record in the table
     *
     * @return PdoStatement|array|int True on success, false otherwise
     */
    public function deleteRecord(mixed $id = null): int|array|PdoStatement
    {
        $query = Query::delete($this->tableName);

        if (is_array($id)) {
            // Use the $id as an array of conditions
            $query->where([$this->primaryKey() => $id]);

            return $this->db->run($query);
        } elseif ($id === true) {
            // This deletes everything in $this->table
            return $this->db->run($query);
        } elseif ($id !== null) {
            return $this->db->run($this->query->where([$this->primaryKey() => $id]));
        }

        throw new RuntimeException("Delete requires conditions or parameters");
    }

    /**
     * Returns the primary key value created in the last INSERT statement.
     *
     * @return false|string The primary key value of the last inserted row
     */
    public function lastInsertId(): false|string
    {
        return $this->db->lastInsertId();
    }

    /**
     * Start a PDO transaction.
     *
     * @return bool True on success, false on failure
     */
    public function begin(): bool
    {
        return $this->db->beginTransaction();
    }

    /**
     * Commit a PDO transaction.
     *
     * @return bool True on success, false on failure
     */
    public function commit(): bool
    {
        return $this->db->commit();
    }

    /**
     * Roll back a PDO transaction.
     *
     * @return bool True on success, false on failure
     */
    public function rollback(): bool
    {
        return $this->db->rollback();
    }

    /**
     * Check if the field exists in the table.
     *
     * @param string $columnName
     * @return boolean
     */
    public function hasColumn(string $columnName): bool
    {
        return array_key_exists($columnName, $this->tableData["column_names"]);
    }

    /**
     * Run a query.
     *
     * This method should be used after creating a query with createSelect(), createUpdate(),
     * createInsert() or createDelete().
     *
     * @return array|int|PdoStatement
     */
    public function run(): int|array|PdoStatement
    {
        return $this->db->run($this->query);
    }

    /**
     * Specify relationships to eager-load.
     *
     * @param string|string[] ...$relationships
     * @return self
     */
    public function with(...$relationships): Table
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
    protected function loadRelationships(array $models): void
    {
        static $depth = 0;

        $depth++;

        if ($models && $depth < 5) {
            $className = get_class($models[0]);
            $ref = new $className();

            foreach ($this->relationships as $relationshipFieldName) {
                $this->attachField($relationshipFieldName, $ref, $models);
            }
        }

        $depth--;
    }

    /**
     * Resolve a related field.
     *
     * @param $relationshipFieldName
     * @param $ref
     * @param array $models
     */
    protected function attachField($relationshipFieldName, $ref, array $models): void
    {
        $getterMethodName = "get" . str($relationshipFieldName)->camel()->title();

        try {
            /** @var Relationship $relationship */
            $relationship = $ref->{$getterMethodName}();
        } catch (Exception|Error) {
        }

        if (isset($relationship) && $relationship instanceof Relationship) {
            $groupingField = $relationship->getGroupingField();
            $relatedKeys = array_map(fn ($r) => $r->{$groupingField}, $models);

            $grouped = $relationship->find($relatedKeys);

            foreach ($models as $model) {
                $model->serialize[] = $relationshipFieldName;
                $keyValue = $model->{$groupingField};

                if (isset($grouped[$keyValue])) {
                    $model->attachRelated($getterMethodName, $grouped[$keyValue]);
                } else {
                    $isMultiple = $relationship instanceof HasMany || $relationship instanceof HasAndBelongsToMany;
                    $model->attachRelated($getterMethodName, $isMultiple ? new Collection([]) : null);
                }
            }
        } else {
            foreach ($models as $model) {
                $model->serialize[] = $relationshipFieldName;
            }
        }
    }

    /**
     * Redirect method calls to the child Query object.
     *
     * @param string $method
     * @param array $arguments
     * @return self
     */
    public function __call(string $method, array $arguments)
    {
        if (!isset($this->query)) {
            throw new RuntimeException("Method `$method` called before initializing a query");
        }

        if (is_callable([$this->query, $method])) {
            $this->query->{$method}(...$arguments);

            return $this;
        }

        throw new BadMethodCallException("Invalid method `$method`");
    }
}
