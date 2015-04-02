<?php

namespace pew\db;

use pew\Pew;
use pew\db\Database;
use pew\db\TableFactory;
use pew\db\relationship\RelationshipInterface;
use pew\db\relationship\BelongsTo;
use pew\db\relationship\HasAndBelongsToMany;
use pew\db\relationship\HasMany;
use pew\db\relationship\HasOne;
use pew\libs\Str;

/**
 * Active Record-like class.
 *
 * @package pew\db
 * @author ifcanduela <ifcanduela@gmail.com>
 */
class Record implements \ArrayAccess, \JsonSerializable
{
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
     * Table data manager.
     * 
     * @var Table
     */
    protected $manager;

    /**
     * The constructor builds the model!.
     *
     * @param string $table Name of the table
     * @param Database $db Database instance to use
     */
    public function __construct(array $record = [])
    {
        $this->manager = self::table();
        $this->record = $this->manager->column_names();
    }

    /**
     * Get the table manager.
     * 
     * @return Table
     */
    public static function table()
    {
        return Pew::instance()->model_factory->create($this->table);
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
            $base_fields = $this->manager->column_names();
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
        $data = $this->record;

        if (method_exists($this->manager, 'before_save')) {
            $data = $this->manager->before_save($data);
        }

        $result = $this->manager->save($this->record);
        var_dump($this->record, $result);
        $this->record = array_merge($this->record, $result);
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
     * Check if the field exists in the model.
     * 
     * @param string $field
     * @return boolean
     */
    public function has_field($field)
    {
        return array_key_exists($field, $this->record);
    }

    /**
     * Check if an column or related model exists.
     * 
     * @return bool True if the offset exists, false otherwise
     */
    public function offsetExists($offset)
    {
        $has_column = $this->has_field($offset);
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
        $this->manager = null;
        return array_keys(get_object_vars($this));
    }
}
