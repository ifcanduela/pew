<?php

declare(strict_types=1);

namespace pew\model;

use ArrayIterator;
use BadMethodCallException;
use Exception;
use IteratorAggregate;
use JsonSerializable;
use PdoStatement;
use pew\model\relation\BelongsTo;
use pew\model\relation\HasAndBelongsToMany;
use pew\model\relation\HasMany;
use pew\model\relation\HasOne;
use pew\model\relation\Relationship;
use ReflectionClass;
use ReflectionException;
use ReflectionProperty;
use RuntimeException;

use function Symfony\Component\String\s;

/**
 * Active Record-like class.
 */
class ActiveRecord implements JsonSerializable, IteratorAggregate
{
    /** @var string Database table for the subject of the model. */
   public string $tableName = "";

    /** @var string Database connection name. */
    public string $connectionName;

    /** @var string|string[] Name of the primary key fields of the table the Model manages. */
    public string|array $primaryKey = "id";

    /** @var string[] List of class properties to serialize as JSON. */
    public array $serialize = [];

    /** @var string[] List of database columns to exclude from JSON serialization. */
    public array $doNotSerialize = [];

    /** @var array Cached results for model get methods. */
    protected array $getterResults = [];

    /** @var array[] List of getter method names. */
    protected static array $getterMethods = [];

    /** @var array[] List of setter method names. */
    protected static array $setterMethods = [];

    /** @var Record Current record data. */
    protected Record $record;

    /** @var string Name of the column holding the record creation timestamp. */
    public static string $createdFieldName = "created";

    /** @var string Name of the column holding the record update timestamp. */
    public static string $updatedFieldName = "updated";

    /** @var bool Flag to signify a record not retrieved from or yet stored into the database  */
    public bool $isNew = true;

    /**
     * Create a new record.
     */
    public function __construct(array $attributes = [])
    {
        // Update the table name if it's empty
        if (mb_strlen($this->tableName) === 0) {
            $this->tableName = $this->getTableManager()->tableName();
        }

        // Initialize the record fields
        $this->record = new Record($this->columns());

        // Fill any passed attributes
        $this->attributes($attributes);

        static::$getterMethods[static::class] ??= [];
        static::$setterMethods[static::class] ??= [];
    }

    /**
     * Get the table manager.
     *
     * @return ?Table
     */
    public function getTableManager(): ?Table
    {
        return TableManager::instance()->create(static::class);
    }

    /**
     * Get the list of column names.
     *
     * @return array
     */
    public function columns(): array
    {
        return $this->getTableManager()->columnNames();
    }

    /**
     * Get the value of the primary key for the current record.
     *
     * @return mixed
     */
    public function primaryKeyValue(): mixed
    {
        return $this->record->get($this->primaryKey);
    }

    /**
     * Create a record from an array of keys and values.
     *
     * @param array $data
     * @param bool $isNew
     * @return static
     */
    public static function fromArray(array $data, bool $isNew = true): ActiveRecord
    {
        $record = new static();

        foreach ($data as $field => $value) {
            if ($record->record->has($field)) {
                $record->record->set($field, $value);
            } elseif (property_exists($record, $field)) {
                $record->{$field} = $value;
            }
        }

        $record->isNew = $isNew;

        return $record;
    }

    /**
     * Create a collection of records from a SQL query.
     *
     * @param string $query
     * @param array $parameters
     * @return Collection
     */
    public static function fromQuery(string $query, array $parameters = []): Collection
    {
        $record = new static();

        $result = $record->getTableManager()->query($query, $parameters);

        return new Collection(array_map(fn ($r) => static::fromArray($r, false), $result));
    }

    /**
     * Get an array representation of the record.
     *
     * @return array
     */
    public function toArray(): array
    {
        return $this->attributes();
    }

    /**
     * Get or set the current record values.
     *
     * This method will only update values for fields set in the $attributes
     * argument.
     *
     * @param ?array $attributes Associative array of column names and values
     * @return self|array An associative array of current column names and values or the object itself
     */
    public function attributes(array $attributes = null): array|static
    {
        if (!is_null($attributes)) {
            foreach ($attributes as $key => $value) {
                if ($this->hasAttribute($key)) {
                    $this->{$key} = $value;
                }
            }

            return $this;
        }

        $include = array_merge(get_object_vars($this), $this->record->all());

        $reflectionClass = new ReflectionClass(__CLASS__);
        $modelProperties = array_map(fn (ReflectionProperty $reflectionProperty) => $reflectionProperty->name, $reflectionClass->getProperties());

        $exclude = array_flip($modelProperties);

        return array_diff_key($include, $exclude);
    }

    /**
     * Check if the model has an attribute by that name.
     *
     * @param string $key
     * @return boolean
     */
    public function hasAttribute(string $key): bool
    {
        return $this->record->has($key) || property_exists($this, $key);
    }

    /**
     * @throws Exception
     */
    public function getAttribute(string $key)
    {
        if ($this->record->has($key)) {
            return $this->record->get($key);
        }

        if (property_exists($this, $key)) {
            return $this->{$key};
        }

        throw new Exception("Model attribute `$key` not found");
    }

    /**
     * @throws Exception
     */
    public function setAttribute(string $key, $value): void
    {
        if ($this->record->has($key)) {
            $this->record->set($key, $value);

            return;
        }

        if (property_exists($this, $key)) {
            $this->{$key} = $value;

            return;
        }

        throw new Exception("Model attribute `$key` not found");
    }

    /**
     * Saves the record to the table.
     *
     * @return bool
     */
    public function save(): bool
    {
        $result = $this->getTableManager()->save($this);

        if ($result) {
            $this->attributes($result);
            $this->isNew = false;

            return true;
        }

        return false;
    }

    /**
     * Deletes the current record.
     *
     * @return PdoStatement|array|int
     */
    public function delete(): int|array|PdoStatement
    {
        $table = $this->getTableManager()->createDelete();

        $table->where([
            $table->primaryKey() => $this->primaryKeyValue(),
        ]);

        return $table->run();
    }

    /**
     * Update all records that match a condition.
     *
     * @param array $values
     * @param ?array $condition
     *
     * @return PdoStatement|array|int Number of affected rows.
     */
    public static function updateAll(array $values, array $condition = null): int|array|PdoStatement
    {
        $record = new static();
        $table = $record->getTableManager();
        $table->createUpdate();
        $table->set($values);

        if ($condition) {
            $table->where($condition);
        }

        return $table->run();
    }

    /**
     * Delete all records that match a condition.
     *
     * @param array|null $condition
     *
     * @return PdoStatement|array|int Number of affected rows.
     */
    public static function deleteAll(array $condition = null): int|array|PdoStatement
    {
        $record = (new static());
        $table = $record->getTableManager()->createDelete();

        if ($condition) {
            $table->where($condition);
        }

        return $table->run();
    }

    /**
     * Allow iteration over the current record fields.
     *
     * @return ArrayIterator
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->attributes());
    }

    /**
     * JSON representation of the model object.
     *
     * @return object
     */
    public function jsonSerialize(): object
    {
        $include = $this->attributes();
        $exclude = array_flip($this->doNotSerialize);

        $record = array_diff_key($include, $exclude);
        $fields = array_unique($this->serialize);

        foreach ($fields as $key) {
            $record[$key] = $this->{$key};
        }

        return (object) $record;
    }

    /**
     * Returns a Table object to perform queries against.
     *
     * @return Table
     */
    public static function find(): Table
    {
        $table = TableManager::instance()->create(static::class);

        $table->createSelect()
            ->columns($table->tableName() . ".*")
            ->from($table->tableName());

        return $table;
    }

    /**
     * Find one record by primary key.
     *
     * When an array is passed, it will be used for the query instead of the
     * primary key.
     *
     * @param mixed $id A primary key value, or an array of attributes
     * @return ActiveRecord|Record|null
     */
    public static function findOne(mixed $id): Record|ActiveRecord|null
    {
        $table = TableManager::instance()->create(static::class);

        $table->createSelect()
            ->columns($table->tableName() . ".*")
            ->from($table->tableName());

        $attributes = is_array($id) ? $id : [
            $table->primaryKey() => $id,
        ];

        return $table->where($attributes)->one();
    }

    /**
     * Find a record, or create if it does not exist.
     *
     * Use `$findAttributes` to search for a record. If it's not found, the
     * items in `$createAttributes` will be added to `$findAttributes`, and the
     * primary key will be removed from it, to create a new record.
     *
     * @param array $findAttributes Query attributes
     * @param array $createAttributes Additional attributes to save
     * @return static
     */
    public static function findOrCreate(array $findAttributes, array $createAttributes): self
    {
        $table = static::find();
        $record = $table->where($findAttributes)->one();

        if (!$record) {
            $attributes = array_merge($findAttributes, $createAttributes);
            unset($attributes[$table->primaryKey()]);

            $record = static::fromArray($attributes);
            $record->save();
        }

        return $record;
    }

    /**
     * Returns a Table object to perform update queries against.
     *
     * @return Table
     */
    public static function update(): Table
    {
        $table = TableManager::instance()->create(static::class);

        return $table->createUpdate();
    }

    /**
     * Set a value in the registry.
     *
     * @param string $key Key for the value
     * @param mixed $value Value to store
     * @return self
     */
    public function __set(string $key, mixed $value)
    {
        $className = static::class;
        $propertyName = s($key)->camel()->toString();
        $columnName = s($key)->snake()->toString();

        if (
            !array_key_exists($className, static::$setterMethods) ||
            !array_key_exists($propertyName, static::$setterMethods[$className])
        ) {
            $methodName = "set" . ucfirst($propertyName);
            static::$setterMethods[$className][$propertyName] = method_exists($this, $methodName) ? $methodName : false;
        }

        $methodName = static::$setterMethods[$className][$propertyName] ?? null;

        if ($methodName) {
            $this->{$methodName}($value);
        } elseif ($this->record->has($columnName)) {
            $this->record->set($columnName, $value);
        } else {
            throw new RuntimeException("Record attribute `$key` does not exist in `$className`");
        }

        return $this;
    }

    /**
     * Get a stored value from the registry.
     *
     * @param mixed $key Key for the value
     *
     * @return mixed Stored value
     * @throws Exception
     */
    public function __get(string $key)
    {
        $methodName = $this->hasGetterMethod($key);

        // Check if the getter method exists
        if ($methodName) {
            // Check if the getter method has been called before
            if (!array_key_exists($methodName, $this->getterResults)) {
                $fetch = $this->{$methodName}();

                if ($fetch instanceof Relationship) {
                    $fetch = $fetch->fetch();
                }

                $this->getterResults[$methodName] = $fetch;
            }

            return $this->getterResults[$methodName];
        }

        // The offset is a table field
        if ($this->record->has($key)) {
            return $this->record->get($key);
        }

        $className = static::class;

        throw new Exception("Record attribute `$key` does not exist in `$className`");
    }

    /**
     * Check if a key is in use.
     *
     * @param mixed $key Key to check
     * @return bool True if the key has been set, false otherwise.
     */
    public function __isset(string $key): bool
    {
        return $this->hasAttribute($key) && $this->{$key} !== null;
    }

    /**
     * Remove a stored value from the registry.
     *
     * @param mixed $key Key to delete
     */
    public function __unset(mixed $key): void
    {
        if ($this->record->has($key)) {
            $this->record->unset($key);
        }
    }

    /**
     * Shortcuts for the find() method.
     *
     * @param string $method
     * @param array  $arguments
     * @return Collection<ActiveRecord|Record>|ActiveRecord|Record|null
     */
    public static function __callStatic(string $method, array $arguments)
    {
        $methodStr = s($method);

        if ($methodStr->startsWith("findAllBy")) {
            $field = (string) $methodStr->after("findAllBy")->snake();
            $value = array_shift($arguments);

            return static::find()->where([$field => $value])->all();
        }

        if ($methodStr->startsWith("findOneBy")) {
            $field = (string) $methodStr->after("findOneBy")->snake();
            $value = array_shift($arguments);

            return static::find()->where([$field => $value])->one();
        }

        throw new BadMethodCallException("Method `" . static::class . "::$method()` does not exist");
    }

    /**
     * Create a many-to-one relationship to another model.
     *
     * In a relationship like this:
     *
     * ```
     * NEAR TABLE | FAR TABLE
     * -----------------------
     * far_id     | id (PK)
     * ```
     *
     * The arguments should be as follows:
     *
     * - `$className`: model class for FAR TABLE
     * - `$localKeyName`: far_id in NEAR TABLE
     * - `$foreignKeyName`: id (PK) of FAR TABLE
     *
     * All arguments except `$className` can be guessed.
     *
     * @param string $className
     * @param ?string $localKeyName
     * @param ?string $foreignKeyName
     * @return BelongsTo
     * @throws ReflectionException
     */
    public function belongsTo(string $className, ?string $localKeyName = null, ?string $foreignKeyName = null): BelongsTo
    {
        if (!$localKeyName) {
            $reflectionClass = new ReflectionClass($className);
            $otherClass = $reflectionClass->getShortName();
            $localKeyName = s($otherClass)->snake() . "_id";
        }

        if (!$foreignKeyName) {
            $foreignKeyName = TableManager::instance()->create($className)->primaryKey();
        }

        $matchValue = $this->{$localKeyName};

        return new BelongsTo($className::find(), $localKeyName, $foreignKeyName, $matchValue);
    }

    /**
     * Create a one-to-many relationship to another model.
     *
     * In a relationship like this:
     *
     * ```
     * NEAR TABLE | FAR TABLE
     * ----------------------
     * id (PK)    | near_id
     * ```
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
     * @param ?string $foreignKeyName
     * @param ?string $localKeyName
     * @return HasMany
     */
    public function hasMany(string $className, ?string $foreignKeyName = null, ?string $localKeyName = null): HasMany
    {
        if (!$foreignKeyName) {
            $reflectionClass = new ReflectionClass($this);
            $thisClass = $reflectionClass->getShortName();
            $foreignKeyName = s($thisClass)->snake() . "_id";
        }

        if (!$localKeyName) {
            $localKeyName = $this->getTableManager()->primaryKey();
        }

        $matchValue = $this->primaryKeyValue();

        return new HasMany($className::find(), $localKeyName, $foreignKeyName, $matchValue);
    }

    /**
     * Create a one-to-many relationship to another model.
     *
     * In a relationship like this:
     *
     * ```
     * NEAR TABLE | FAR TABLE
     * ----------------------
     * id (PK)    | near_id
     * ```
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
     * @param ?string $foreignKeyName
     * @param ?string $localKeyName
     * @return HasOne
     */
    public function hasOne(string $className, ?string $foreignKeyName = null, ?string $localKeyName = null): HasOne
    {
        if (!$foreignKeyName) {
            $reflectionClass = new ReflectionClass($this);
            $thisClass = $reflectionClass->getShortName();
            $foreignKeyName = s($thisClass)->snake() . "_id";
        }

        if (!$localKeyName) {
            $localKeyName = $this->getTableManager()->primaryKey();
        }

        $matchValue = $this->primaryKeyValue();

        return new HasOne($className::find(), $localKeyName, $foreignKeyName, $matchValue);
    }

    /**
     * Create a many-to-many relationship to another model.
     *
     * In a relationship like this:
     *
     * ```
     * NEAR TABLE | ASSOCIATION TABLE  | FAR TABLE
     * -------------------------------------------
     * id (PK)    | near_id <=> far_id | id (PK)
     * ```
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
     * @param ?string $associationTableName
     * @param ?string $nearKeyName
     * @param ?string $nearForeignKeyName
     * @param ?string $farForeignKeyName
     * @param ?string $farKeyName
     * @return HasAndBelongsToMany
     * @throws ReflectionException
     */
    public function hasAndBelongsToMany(
        string $className,
        ?string $associationTableName = null,
        ?string $nearKeyName = null,
        ?string $nearForeignKeyName = null,
        ?string $farForeignKeyName = null,
        ?string $farKeyName = null
    ): HasAndBelongsToMany {
        $nearTableName = $this->tableName;
        $farTableName = (new $className())->tableName;

        if (!$associationTableName) {
            $tableNames = [
                $nearTableName,
                $farTableName,
            ];
            sort($tableNames);

            $associationTableName = join("_", $tableNames);
        }

        if (!$nearKeyName) {
            $nearKeyName = $this->getTableManager()->primaryKey();
        }

        if (!$nearForeignKeyName) {
            $reflectionClass = new ReflectionClass($this);
            $thisClass = $reflectionClass->getShortName();
            $nearForeignKeyName = s($thisClass)->snake() . "_id";
        }

        if (!$farForeignKeyName) {
            $reflectionClass = new ReflectionClass($className);
            $farClass = $reflectionClass->getShortName();
            $farForeignKeyName = s($farClass)->snake() . "_id";
        }

        if (!$farKeyName) {
            $farKeyName = (new $className())->getTableManager()->primaryKey();
        }

        $on = ["$associationTableName.$farForeignKeyName" => "$farTableName.$farKeyName"];

        return (new HasAndBelongsToMany($className::find(), $nearKeyName, $nearForeignKeyName, $this->primaryKeyValue()))
            ->through($associationTableName, $on);
    }

    /**
     * Check if a model has a relationship method for a field.
     *
     * @param string $key
     * @return bool|string False if the relationship does not exist, the getter name otherwise.
     */
    private function hasGetterMethod(string $key): bool|string
    {
        // Generate a getter method name if it does not yet exist
        if (!isset(static::$getterMethods[static::class][$key])) {
            $methodName = "get" . s($key)->camel()->title();
            static::$getterMethods[static::class][$key] = method_exists($this, $methodName) ? $methodName : false;
        }

        return static::$getterMethods[static::class][$key] ?? false;
    }

    /**
     * Check is there are results for a given magic property.
     *
     * @param string $key
     * @return bool
     */
    private function hasGetterResults(string $key): bool
    {
        $methodName = "get" . s($key)->camel()->title();

        return array_key_exists($methodName, $this->getterResults);
    }

    /**
     * @param string $getter Name of the getter method
     * @param Collection|ActiveRecord|array $values Value of the related property
     * @return void
     */
    public function attachRelated(string $getter, Collection|ActiveRecord|array $values): void
    {
        $this->getterResults[$getter] = $values;
    }
}
