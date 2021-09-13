<?php declare(strict_types=1);

namespace pew\model;

/**
 * Simple key/value container.
 */
class Record
{
    public array $fields;

    /**
     * Initialize a basic record.
     *
     * @var array $fields
     */
    public function __construct(array $fields)
    {
        $this->fields = $fields;
    }

    /**
     * Retrieve all key/value pairs.
     *
     * @return array
     */
    public function all(): array
    {
        return $this->fields;
    }

    /**
     * Update values key/value pairs.
     *
     * @param array $fields
     * @return void
     */
    public function merge(array $fields)
    {
        foreach ($fields as $fieldName => $value) {
            if ($this->has($fieldName)) {
                $this->set($fieldName, $value);
            }
        }
    }

    /**
     * Retrieve a single value by key.
     *
     * @param string $fieldName
     * @return mixed
     */
    public function get(string $fieldName)
    {
        if ($this->has($fieldName)) {
            return $this->fields[$fieldName];
        }

        throw new \InvalidArgumentException("Field `{$fieldName}` not found in record");
    }

    /**
     * Set a value by key.
     *
     * @param string $fieldName
     * @param mixed $value
     * @return void
     */
    public function set(string $fieldName, $value)
    {
        if ($this->has($fieldName)) {
            $this->fields[$fieldName] = $value;
            return;
        }

        throw new \InvalidArgumentException("Field `{$fieldName}` not found in record");
    }

    /**
     * Check if a key is available.
     *
     * @param string $fieldName
     * @return boolean
     */
    public function has(string $fieldName): bool
    {
        return array_key_exists($fieldName, $this->fields);
    }

    /**
     * Check if a key is available and is not null.
     *
     * @param string $fieldName
     * @return boolean
     */
    public function isset(string $fieldName): bool
    {
        return $this->has($fieldName) && $this->get($fieldName) !== null;
    }

    /**
     * Set the value of a key to null.
     *
     * @param string $fieldName
     * @return void
     */
    public function unset(string $fieldName)
    {
        if ($this->has($fieldName)) {
            $this->fields[$fieldName] = null;
            return;
        }

        throw new \InvalidArgumentException("Field `{$fieldName}` not found in record");
    }

    /**
     * Set a value by key.
     *
     * @param string $prop
     * @param mixed $value
     * @return void
     */
    public function __set(string $prop, $value)
    {
        $this->set($prop, $value);
    }

    /**
     * Retrieve a value by key.
     *
     * @param string $prop
     * @return mixed
     */
    public function __get(string $prop)
    {
        $this->get($prop);
    }

    /**
     * Check if a key is available and its value is not null.
     *
     * @param string $prop
     * @return bool
     */
    public function __isset(string $prop)
    {
        return $this->isset($prop);
    }

    /**
     * Set the value of a key to null.
     *
     * @param string $prop
     * @return void
     */
    public function __unset(string $prop)
    {
        return $this->unset($prop);
    }
}
