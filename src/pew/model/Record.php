<?php

namespace pew\model;

use Stringy\Stringy as Str;

/**
 * Active Record-like class.
 */
class Record implements \JsonSerializable, \IteratorAggregate
{
    /** @var string Database table for the subject of the model. */
    protected $tableName;

    /** @var string Name of the primary key fields of the table the Model manages. */
    protected $primaryKey = 'id';

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
    protected $connectionName = 'default';

    /** @var bool Flag for new records. */
    public $isNew = false;

    /** @var array List of validation errors. */
    public $errors = [];

    /**
     * Create an empty, new record.
     */
    public function __construct()
    {
        if (!$this->tableName) {
            throw new \RuntimeException("No table name configured for class " . static::class);
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
        static $table;

        if (!$table) {
            $table = TableFactory::create($this->tableName, static::class);
        }

        return $table;
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
     * @return array
     */
    public static function fromQuery(string $query, array $parameters = [])
    {
        $record = new static;

        $result = $record->tableManager->query($query, $parameters);

        return array_map(function ($r) {
            return static::fromArray($r);
        }, $result);
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
            foreach ($attributes as $key => $value) {
                if ($this->hasAttribute($key)) {
                    $this->$key = $value;
                }
            }
        }

        return $this->record;
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
     * Add an error for a field.
     *
     * @param string $field
     * @param string $message
     */
    public function addError(string $field, string $message)
    {
        $this->errors[] = (object) compact('field', 'message');
    }

    /**
     * Check if the record passed validation.
     *
     * @return boolean
     */
    public function hasErrors()
    {
        return !empty($this->errors);
    }

    /**
     * Get an array of all errors per field.
     *
     * @param string|null $field
     * @return array
     */
    public function getErrors(string $field = null)
    {
        if (isset($field)) {
            return $this->getErrorsForField($field);
        }

        return $this->errors;
    }

    /**
     * Get a list of all errors for a field.
     *
     * @param string $field
     * @return array
     */
    public function getErrorsForField(string $field)
    {
        $errors = [];

        foreach ($this->errors as $error) {
            if ($error->field == $field) {
                $errors[] = $error->message;
            }
        }

        return $errors;
    }

    /**
     * Saves the record to the table.
     *
     * @return Record
     */
    public function save()
    {
        $result = $this->tableManager->save($this);

        if ($result) {
            $this->attributes($result);
            $this->isNew = false;
        }

        return is_array($result);
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
        $record = (new static);
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

        return  $result;
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
        $record = array_diff_key(
            $this->attributes(),
            array_flip($this->doNotSerialize)
        );

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
        $table = $record->tableManager;

        $table->createSelect()
            ->columns($record->tableName . '.*')
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
        $table = $record->tableManager;

        $table->createSelect()
            ->columns($record->tableName . '.*')
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
            $methodName = 'set' . Str::create($key)->upperCamelize();
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
        # generate a getter method name if it does not yet exist
        if (!array_key_exists($key, static::$getterMethods)) {
            $methodName = 'get' . Str::create($key)->upperCamelize();
            static::$getterMethods[$key] = method_exists($this, $methodName) ? $methodName : false;
        }

        $methodName = static::$getterMethods[$key];

        # check if the getter method exists
        if (method_exists($this, $methodName)) {
            # check if the getter method has been called before
            if (!array_key_exists($methodName, $this->getterResults)) {
                $fetch = $this->$methodName();

                if ($fetch instanceof Table) {
                    $fetch = $fetch->fetch();
                }

                $this->getterResults[$key] = $fetch;
            }

            return $this->getterResults[$key];
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
        return isset($this->record[$key]);
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

        if ($methodStr->startsWith('findAllBy')) {
            $field = $methodStr->removeLeft('findAllBy')->underscored()->__toString();
            $value = array_shift($arguments);
            return static::find()->where([$field => $value])->all();
        }

        if ($methodStr->startsWith('findOneBy')) {
            $field = $methodStr->removeLeft('findOneBy')->underscored()->__toString();
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

    public function belongsTo(string $className, string $fkName = null)
    {
        if (!$fkName) {
            $otherClass = basename($className);
            $fkName = Str::create($otherClass)->underscored() . '_id';
        }

        return $className::find()
            ->fetchCondition(['id' => $this->$fkName])
            ->belongsTo();
    }

    public function hasMany(string $className, string $fkName = null)
    {
        if (!$fkName) {
            $thisClass = basename(get_class($this));
            $fkName = Str::create($thisClass)->underscored() . '_id';
        }

        $primaryKeyName = $this->tableManager->primaryKey();
        $primaryKeyValue = $this->record[$primaryKeyName];

        return $className::find()
            ->fetchCondition([$fkName => $primaryKeyValue])
            ->hasMany();
    }
}
