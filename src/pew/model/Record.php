<?php

namespace pew\model;

use Stringy\Stringy as Str;

/**
 * Active Record-like class.
 *
 * @package pew\model
 * @author ifcanduela <ifcanduela@gmail.com>
 */
class Record implements \ArrayAccess, \JsonSerializable
{
    /**
     * List of class properties to serialize as JSON.
     * 
     * @var array
     */
    public $serialize = [];

    /**
     * List of database columns to exclude from JSON serialization.
     * 
     * @var array
     */
    public $doNotSerialize = [];

    /**
     * Cached results for model get methods.
     * 
     * @var array
     */
    protected $getterResults = [];

    /**
     * Database table for the subject of the model.
     *
     * @var string
     */
    protected $tableName;

    /**
     * Name of the primary key fields of the table the Model manages.
     *
     * @var string
     */
    protected $primaryKey;

    /**
     * Current resultset.
     *
     * Holds an index for each record in the last resultset.
     *
     * @var array
     */
    protected $record = [];

    /**
     * Table data manager.
     * 
     * @var Table
     */
    protected $tableManager;

    /**
     * Database connection name.
     * 
     * @var Table
     */
    protected $connectionName = 'default';

    /**
     * Flag for new records.
     * 
     * @var boolean
     */
    public $isNew = false;

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

        foreach ($data as $key => $value) {
            $record->$key = $data[$key];
        }

        return $record;
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
            $base_fields = $this->tableManager->column_names();
            $this->record = array_intersect_key($attributes, $base_fields) + $base_fields;
        }
        
        return $this->record;
    }

    /**
     * Saves the record to the table.
     *
     * @return null
     */
    public function save()
    {
        $result = $this->tableManager->save($this->record);
        $this->record = array_merge($this->record, $result->attributes());

        $this->isNew = false;
    }

    /**
     * Deletes the current record.
     */
    public function delete()
    {
        return $this->tableManager->where([$this->primaryKey => $this->id])->delete();
    }

    /**
     * Check if an column or related model exists.
     * 
     * @return bool True if the offset exists, false otherwise
     */
    public function offsetExists($offset)
    {
        return array_key_exists($offset, $this->record);
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
        unset($this->record[$id]);
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
            return $table->where([$table->primaryKey => $id])->one();
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
        $this->record[$key] = $value;
    }

    /**
     * Get a stored value from the registry.
     * 
     * @param mixed $key Key for the value
     * @return mixed Stored value
     */
    public function __get($key)
    {
        $methodName = 'get' . Str::create($key)->upperCamelize();

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
