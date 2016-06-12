<?php

namespace pew\model;

use pew\Pew;
use pew\model\relationship\RelationshipInterface;
use pew\model\relationship\BelongsTo;
use pew\model\relationship\HasAndBelongsToMany;
use pew\model\relationship\HasMany;
use pew\model\relationship\HasOne;
use Stringy\Stringy as Str;

/**
 * Active Record-like class.
 *
 * @package pew\db
 * @author ifcanduela <ifcanduela@gmail.com>
 */
class Record implements \ArrayAccess, \JsonSerializable
{
    public $serialize = [];

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
    protected $primary_key;

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
        return $this->tableManager->where([$this->primaryKey() => $this->id])->delete();
    }

    /**
     * Validate the data against a validator.
     * 
     * @param array $record Fields and values to validate
     * @param array $rules Validation configuration
     * @return array Validation errors
     */
    public function validate(array $rules = null)
    {
        if (is_null($rules)) {
            if (!property_exists($this, 'rules')) {
                throw new \RuntimeException("No valdation rules configured for model " . get_class($this));
            } else {
                $rules = $this->rules;
            }
        }

        $validator = Pew::instance()->library('Validator', [$rules]);
        $validator->validate($this->record);

        return $validator->errors();
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
     * Set a value in the registry.
     * 
     * @param string $key Key for the value
     * @param mixed Value to store
     */
    public function __set($key, $value)
    {
        $this->record[$key] = $value;
        // return $this->offsetSet($key, $value);
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
        $record = $this->attributes();

        foreach ($this->serialize as $key) {
            $record[$key] = $this->$key;
        }

        return (object) $record;
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

    public function __wakeup()
    {
        $this->tableManager = $this->table();
    }

    public static function find($id = null)
    {
        $record = new static();
        $table = $record->tableManager;
        
        if ($id) {
            return $table->where([$table->primaryKey() => $id])->one();
        }
        
        return $table;
    }

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
}
