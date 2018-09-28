<?php

namespace pew\model;

use pew\model\relation\BelongsTo;
use pew\model\relation\HasMany;
use pew\model\relation\HasOne;
use pew\model\relation\HasAndBelongsToMany;

use Stringy\Stringy as Str;

/**
 * Active Record-like class.
 */
class Record implements \JsonSerializable, \IteratorAggregate
{
    /** @var string Database table for the subject of the model. */
    protected $tableName;

    /** @var string Name of the primary key fields of the table the Model manages. */
    protected $primaryKey = "id";

    /** @var array List of class properties to serialize as JSON. */
    public $serialize = [];

    /** @var array List of database columns to exclude from JSON serialization. */
    public $doNotSerialize = [];

    /** @var array Cached results for model get methods. */
    protected $getterResults = [];

    /** @var array List of getter method names. */
    protected static $getterMethods = [];

    /** @var array List of setter method names. */
    protected static $setterMethods = [];

    /** @var array Current record data. */
    protected $record = [];

    /** @var Table Table data manager. */
    protected $tableManager;

    /** @var string Database connection name. */
    protected $connectionName = "default";

    /** @var bool Flag for new records. */
    public $isNew = false;

    /** @var array List of validation errors. */
    public $errors = [];

    /** @var string Name of the column holding the record creation timestamp. */
    public static $createdFieldName = "created";

    /** @var string Name of the column holding the record update  timestamp. */
    public static $updatedFieldName = "updated";

    /**
     * Create an empty, new record.
     */
    public function __construct()
    {
        if (!$this->tableName) {
            $this->tableName = Str::create(basename(get_class($this)))->underscored() . "s";
        }

        $this->tableManager = $this->table();
        $this->record = $this->columns();
        $this->isNew = true;
    }

    /**
     * Get the table manager.
     *
     * @return Table
     */
    public function table()
    {
        return TableFactory::create($this->tableName, static::class);
    }

    /**
     * Get the list of column names.
     *
     * @return array
     */
    public function columns()
    {
        static $columns;

        if (!$columns) {
            $columns = $this->tableManager->columnNames();
        }

        return $columns;
    }

    public function primaryKeyValue()
    {
        return $this->record[$this->primaryKey];
    }

    /**
     * Create a record from a array of keys and values.
     *
     * @param array $data
     * @return static
     */
    public static function fromArray(array $data)
    {
        $record = new static;

        $record->attributes($data);

        return $record;
    }

    /**
     * Create a collection of records from a SQL query.
     *
     * @param string $query
     * @param array $parameters
     * @return Collection
     */
    public static function fromQuery(string $query, array $parameters = [])
    {
        $record = new static();

        $result = $record->tableManager->query($query, $parameters);

        return new Collection(array_map(function ($r) {
            return static::fromArray($r);
        }, $result));
    }

    /**
     * Get an array representation of the record.
     *
     * @return array
     */
    public function toArray()
    {
        return array_merge($this->record, get_object_vars($this));
    }

    /**
     * Get or set the current record values.
     *
     * This method will only update values for fields set in the $attributes
     * argument.
     *
     * @param array $attributes Associative array of column names and values
     * @return self|array An associative array of current column names and values or the object itself
     */
    public function attributes(array $attributes = null)
    {
        if (!is_null($attributes)) {
            foreach ($attributes as $key => $value) {
                if ($this->hasAttribute($key)) {
                    $this->$key = $value;
                }
            }

            return $this;
        }

        $include = array_merge(get_object_vars($this), $this->record);

        $exclude = [
            "connectionName" => true,
            "createdFieldName" => true,
            "doNotSerialize" => true,
            "errors" => true,
            "getterMethods" => true,
            "getterResults" => true,
            "isNew" => true,
            "primaryKey" => true,
            "record" => true,
            "serialize" => true,
            "setterMethods" => true,
            "tableManager" => true,
            "tableName" => true,
            "updatedFieldName" => true,
        ];

        $record = array_diff_key($include, $exclude);

        return $record;
    }

    /**
     * Check if the model has an attribute by that name.
     *
     * @param string $key
     * @return boolean
     */
    public function hasAttribute($key)
    {
        return array_key_exists($key, $this->record) || property_exists($this, $key);
    }

    /**
     * Saves the record to the table.
     *
     * @return bool
     */
    public function save()
    {
        $result = $this->tableManager->save($this);

        if ($result) {
            $this->attributes($result);
            $this->isNew = false;

            return true;
        }

        return false;
    }

    /**
     * Deletes the current record.
     */
    public function delete()
    {
        $table = $this->tableManager->createDelete();

        $table->where([
            $table->primaryKey() => $this->record[$table->primaryKey()]
        ]);

        return $table->run();
    }

    /**
     * Update all records that match a condition.
     *
     * @param  array      $values
     * @param  array|null $condition
     * @return int Number of affected rows.
     */
    public static function updateAll(array $values, array $condition = null)
    {
        $record = new static();
        $table = $record->tableManager;
        $table->createUpdate();
        $table->set($values);

        if ($condition) {
            $table->where($condition);
        }

        $result = $table->run();

        return $result;
    }

    /**
     * Delete all records that match a condition.
     *
     * @param  array|null $condition
     * @return int Number of affected rows.
     */
    public static function deleteAll(array $condition = null)
    {
        $record = (new static);
        $table = $record->tableManager->createDelete();

        if ($condition) {
            $table->where($condition);
        }

        $result = $table->run();

        return $result;
    }

    /**
     * Allow iteration over the current record fields.
     *
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->record);
    }

    /**
     * JSON representation of the model object.
     *
     * @return object
     */
    public function jsonSerialize()
    {
        $include = $this->attributes();
        $exclude = array_flip($this->doNotSerialize);

        $record = array_diff_key($include, $exclude);

        foreach ($this->serialize as $key) {
            $record[$key] = $this->$key;
        }

        return (object) $record;
    }

    /**
     * Returns a Table object to perform queries against.
     *
     * @return Table
     */
    public static function find()
    {
        $record = new static();
        $table = $record->table();

        $table->createSelect()
            ->columns($record->tableName . ".*")
            ->from($record->tableName);

        return $table;
    }

    /**
     * Find one record by primary key.
     *
     * @param mixed $id A primary key value
     * @return static
     */
    public static function findOne($id)
    {
        $record = new static();
        $table = $record->table();

        $table->createSelect()
            ->columns($record->tableName . ".*")
            ->from($record->tableName);

        return $table->where([$table->primaryKey() => $id])->one();
    }

    /**
     * Returns a Table object to perform update queries against.
     *
     * @return Table
     */
    public static function update()
    {
        $record = new static();

        return $record->tableManager->createUpdate();
    }

    /**
     * Set a value in the registry.
     *
     * @param string $key Key for the value
     * @param mixed $value Value to store
     * @return self
     */
    public function __set($key, $value)
    {
        if (!array_key_exists($key, static::$setterMethods)) {
            $methodName = "set" . Str::create($key)->upperCamelize();
            static::$setterMethods[$key] = method_exists($this, $methodName) ? $methodName : false;
        }

        $methodName = static::$setterMethods[$key];

        if ($methodName) {
            $this->$methodName($value);

            return $this;
        } elseif (array_key_exists($key, $this->record)) {
            $this->record[$key] = $value;

            return $this;
        }

        throw new \RuntimeException("Record attribute {$key} does not exist");
    }

    /**
     * Get a stored value from the registry.
     *
     * @param mixed $key Key for the value
     *
     * @return mixed Stored value
     * @throws \Exception
     */
    public function __get($key)
    {
        $methodName = $this->hasRelationship($key);

        # check if the getter method exists
        if ($methodName) {
            # check if the getter method has been called before
            if (!$this->hasGetterResults($key)) {
                $fetch = $this->$methodName();

                if (method_exists($fetch, "fetch")) {
                    $fetch = $fetch->fetch();
                }

                $this->getterResults[$methodName] = $fetch;
            }

            return $this->getterResults[$methodName];
        }

        # the offset is a table field
        if (array_key_exists($key, $this->record)) {
            return $this->record[$key];
        }

        throw new \Exception("Field '{$key}' not found in class '" . get_class($this) . "'");
    }

    /**
     * Check if a key is in use.
     *
     * @param mixed $key Key to check
     * @return bool True if the key has been set, false otherwise.
     */
    public function __isset($key)
    {
        return $this->hasAttribute($key);
    }

    /**
     * Remove a stored value from the registry.
     *
     * @param mixed $key Key to delete
     */
    public function __unset($key)
    {
        unset($this->record[$key]);
    }

    /**
     * Shortcuts for the find() method.
     *
     * @param string $method
     * @param array $arguments
     * @return mixed
     */
    public static function __callStatic($method, $arguments)
    {
        $methodStr = Str::create($method);

        if ($methodStr->startsWith("findAllBy")) {
            $field = $methodStr->removeLeft("findAllBy")->underscored()->__toString();
            $value = array_shift($arguments);
            return static::find()->where([$field => $value])->all();
        }

        if ($methodStr->startsWith("findOneBy")) {
            $field = $methodStr->removeLeft("findOneBy")->underscored()->__toString();
            $value = array_shift($arguments);
            return static::find()->where([$field => $value])->one();
        }

        throw new \BadMethodCallException("Method " . static::class . "::{$method}() does not exist");
    }

    /**
     * Disconnect the PDO instance before serialization.
     *
     * @return array
     */
    public function __sleep()
    {
        $this->tableManager = null;

        return array_keys(get_object_vars($this));
    }

    /**
     * Restore the database manager after deserialization.
     */
    public function __wakeup()
    {
        $this->tableManager = $this->table();
    }

    /**
     * Create a many-to-one relationship to another model.
     *
     * In a relationship like this:
     *
     * NEAR TABLE | FAR TABLE
     * -----------------------
     * far_id     | id (PK)
     *
     * The arguments should be as follows:
     *
     * - `$className`: model class for FAR TABLE
     * - `$localKeyName`: far_id in NEAR TABLE
     * - `$foreignKeyName`: id (PK) of FAR TABLE
     *
     * All arguments except `$className` can be guessed.
     *
     * @param  string      $className
     * @param  string|null $localKeyName
     * @param  string|null $foreignKeyName
     * @return BelongsTo
     */
    public function belongsTo(string $className, string $localKeyName = null, string $foreignKeyName = null)
    {
        if (!$localKeyName) {
            $otherClass = basename($className);
            $localKeyName = Str::create($otherClass)->underscored() . "_id";
        }

        if (!$foreignKeyName) {
            $foreignKeyName = (new $className())->tableManager->primaryKey();
        }

        $matchValue = $this->$localKeyName;

        return new BelongsTo($className::find(), $localKeyName, $foreignKeyName, $matchValue);
    }

    /**
     * Create a one-to-many relationship to another model.
     *
     * In a relationship like this:
     *
     * NEAR TABLE | FAR TABLE
     * ----------------------
     * id (PK)    | near_id
     *
     * The arguments should be as follows:
     *
     * - `$className`: model class for FAR TABLE
     * - `$foreignKeyName`: near_id of FAR TABLE
     * - `$localKeyName`: id (PK) in NEAR TABLE
     *
     * All arguments except `$className` can be guessed.
     *
     * @param string $className
     * @param string $foreignKeyName
     * @param string $localKeyName
     * @return HasMany
     */
    public function hasMany(string $className, string $foreignKeyName = null, string $localKeyName = null)
    {
        if (!$foreignKeyName) {
            $thisClass = basename(get_class($this));
            $foreignKeyName = Str::create($thisClass)->underscored() . "_id";
        }

        if (!$localKeyName) {
            $localKeyName = $this->tableManager->primaryKey();
        }

        $matchValue = $this->primaryKeyValue();

        return new HasMany($className::find(), $localKeyName, $foreignKeyName, $matchValue);
    }

    /**
     * Create a one-to-many relationship to another model.
     *
     * In a relationship like this:
     *
     * NEAR TABLE | FAR TABLE
     * ----------------------
     * id (PK)    | near_id
     *
     * The arguments should be as follows:
     *
     * - `$className`: model class for FAR TABLE
     * - `$foreignKeyName`: near_id of FAR TABLE
     * - `$localKeyName`: id (PK) in NEAR TABLE
     *
     * All arguments except `$className` can be guessed.
     *
     * @param string $className
     * @param string $foreignKeyName
     * @param string $localKeyName
     * @return HasMany
     */
    public function hasOne(string $className, string $foreignKeyName = null, string $localKeyName = null)
    {
        if (!$foreignKeyName) {
            $thisClass = basename(get_class($this));
            $foreignKeyName = Str::create($thisClass)->underscored() . "_id";
        }

        if (!$localKeyName) {
            $localKeyName = $this->tableManager->primaryKey();
        }

        $matchValue = $this->primaryKeyValue();

        return new HasOne($className::find(), $localKeyName, $foreignKeyName, $matchValue);
    }

    /**
     * Create a many-to-many relationship to another model.
     *
     * In a relationship like this:
     *
     * NEAR TABLE | ASSOCIATION TABLE  | FAR TABLE
     * -------------------------------------------
     * id (PK)    | near_id <=> far_id | id (PK)
     *
     * The arguments should be as follows:
     *
     * - `$className`: model class for FAR TABLE
     * - `$associationTableName`: name of ASSOCIATION TABLE
     * - `$nearKeyName`: id (PK) in NEAR TABLE
     * - `$nearForeignKeyName`: near_id in ASSOCIATION TABLE
     * - `$farForeignKeyName`: far_id in ASSOCIATION TABLE
     * - `$farKeyName`: id (PK) in FAR TABLE
     * - `$foreignKeyName`: `near_id` of ASSOCIATION TABLE
     *
     * All arguments except `$className` can be guessed.
     *
     * @param string $className
     * @param string|null $associationTableName
     * @param string|null $nearKeyName
     * @param string|null $nearForeignKeyName
     * @param string|null $farForeignKeyName
     * @param string|null $farKeyName
     * @return HasAndBelongsToMany
     */
    public function hasAndBelongsToMany(
        string $className,
        string $associationTableName = null,
        string $nearKeyName = null,
        string $nearForeignKeyName = null,
        string $farForeignKeyName = null,
        string $farKeyName = null
    )
    {
        $nearTableName = $this->tableName;
        $farTableName = (new $className)->tableName;

        if (!$associationTableName) {
            $tableNames = [
                $nearTableName,
                $farTableName,
            ];
            sort($tableNames);

            $associationTableName = join("_", $tableNames);
        }

        if (!$nearKeyName) {
            $nearKeyName = $this->tableManager->primaryKey();
        }

        if (!$nearForeignKeyName) {
            $thisClass = basename(get_class($this));
            $nearForeignKeyName = Str::create($thisClass)->underscored() . "_id";
        }

        if (!$farForeignKeyName) {
            $farClass = basename($className);
            $farForeignKeyName = Str::create($farClass)->underscored() . "_id";
        }

        if (!$farKeyName) {
            $farKeyName = (new $className)->tableManager->primaryKey();
        }

        $on = ["{$associationTableName}.{$farForeignKeyName}" => "{$farTableName}.{$farKeyName}"];

        return (new HasAndBelongsToMany($className::find(), $nearKeyName, $nearForeignKeyName, $this->primaryKeyValue()))
            ->through($associationTableName, $on);
    }

    /**
     * Check if a model has a relationship method for a field.
     *
     * @param  string       $key
     * @return string|bool  False if the relationship does not exist, the getter name otherwise.
     */
    public function hasRelationship($key)
    {
        # generate a getter method name if it does not yet exist
        if (!array_key_exists($key, static::$getterMethods)) {
            $methodName = "get" . Str::create($key)->upperCamelize();
            static::$getterMethods[$key] = method_exists($this, $methodName) ? $methodName : false;
        }

        return static::$getterMethods[$key];
    }

    /**
     * Check is there are results for a given magic property.
     *
     * @param string $key
     * @return bool
     */
    protected function hasGetterResults($key)
    {
        $methodName = "get" . Str::create($key)->upperCamelize();

        return array_key_exists($methodName, $this->getterResults);
    }

    /**
     * @param string       $getter Name of the getter method
     * @param array|Record $values Value of the related property
     */
    public function attachRelated($getter, $values)
    {
        $this->getterResults[$getter] = $values;
    }
}
