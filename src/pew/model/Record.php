<?php

namespace pew\model;

use Stringy\Stringy as Str;

/**
 * Active Record-like class.
 *
 * @package pew\model
 * @author ifcanduela <ifcanduela@gmail.com>
 */
class Record implements \JsonSerializable
{
    /** @var string Database table for the subject of the model. */
    protected $tableName;

    /** @var string Name of the primary key fields of the table the Model manages. */
    protected $primaryKey;

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

    /** @var Table data manager. */
    protected $tableManager;

    /** @var Database connection name. */
    protected $connectionName = 'default';

    /** @var Flag for new records. */
    public $isNew = false;

    /** @var List of validation errors. */
    public $errors = [];

    /**
     * The constructor builds the model!.
     *
     * @param string $table Name of the table
     * @param Database $db Database instance to use
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

    public function columns()
    {
        static $columns;

        if (!$columns) {
            $columns = $this->tableManager->column_names();
        }

        return $columns;
    }

    public static function fromArray(array $data)
    {
        $record = new static;

        $record->attributes($data);

        return $record;
    }

    public static function fromQuery($query, $parameters)
    {
        $record = new static;

        $result = $record->table()->query($query, $parameters);

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
    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }

    /**
     * Get an array of all errors per field.
     *
     * @param string|null $field
     * @return array
     */
    public function getErrors(string $field = null): array
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
    public function getErrorsForField(string $field): array
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
     * @return null
     */
    public function save()
    {
        $result = $this->tableManager->save($this);
        $this->record = array_merge($this->record, $result);

        $this->isNew = false;
    }

    /**
     * Deletes the current record.
     */
    public function delete()
    {
        return $this->tableManager->delete($this->id);
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
     * @param mixed $id A primary key value
     * @return Table
     */
    public static function find($id = null)
    {
        $record = new static();
        $table = $record->tableManager;

        if ($id) {
            return $table->where([$table->primaryKey() => $id])->one();
        }

        return $table;
    }

    /**
     * Set a value in the registry.
     *
     * @param string $key Key for the value
     * @param mixed Value to store
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
     * @return mixed Stored value
     */
    public function __get($key)
    {
        if (!array_key_exists($key, static::$getterMethods)) {
            $methodName = 'get' . Str::create($key)->upperCamelize();
            static::$getterMethods[$key] = method_exists($this, $methodName) ? $methodName : false;
        }

        $methodName = static::$getterMethods[$key];

        if (method_exists($this, $methodName)) {
            if (!array_key_exists($methodName, $this->getterResults)) {
                $this->getterResults[$methodName] = $this->$methodName();
            }

            return $this->getterResults[$methodName];
        }

        # the offset is a table field
        if (isSet($this->record[$key])) {
            return $this->record[$key];
        }

        return null;
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
        $this->offsetUnset($key);
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
}
