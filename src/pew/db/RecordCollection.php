<?php

namespace pew\db;

use pew\Pew;

/**
 * Simple collection class.
 *
 * @package pew\db
 * @author ifcanduela <ifcanduela@gmail.com>
 */
class RecordCollection implements \ArrayAccess, \Countable, \Iterator, \JsonSerializable
{
    /**
     * Current list of records.
     * 
     * @var array
     */
    protected $records = [];

    /**
     * Current internal pointer.
     * 
     * @var integer
     */
    protected $current = 0;

    /**
     * Build and populate a colection.
     *
     * @param string $records
     */
    public function __construct(array $records = [])
    {
        $this->records = $records;
    }

    /**
     * Add a record at the end of the collection.
     * 
     * @param Record $record
     */
    public function append($record)
    {
        $this->records[] = $record;
    }

    /**
     * Retrieves the records in the collection.
     * 
     * @return array
     */
    public function as_array()
    {
        return $this->records;
    }

    /**
     * Serializes the collection as JSON.
     */
    public function jsonSerialize()
    {
        return $this->records;
    }

    /**
     * Returns the number of records in the collection.
     * 
     * @return integer
     */
    public function count()
    {
        return count($this->records);
    }

    /**
    * Returns the current record.
    *
    * @return Record
    */
    public function current()
    {
        return $this->records[$this->current];
    }

    /**
     * Gets the current internal pointer.
     * 
     * @return integer
     */
    public function key()
    {
        return $this->current;
    }

    /**
     * Moves the internal pointer to the next item in the collection.
     * 
     * @return integer
     */
    public function next()
    {
        return ++$this->current;
    }

    /**
     * Moves the internal pointer to the previous item in the collection.
     * 
     * @return integer
     */
    public function rewind()
    {
        $this->current = 0;
    }

    /**
     * Checks if the current item in the collection is valid.
     * 
     * @return bool
     */
    public function valid()
    {
        return $this->offsetExists($this->current);
    }

    /**
     * Check if the provided offset is valid.
     * 
     * @param integer $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        return isset($this->records[$offset]);
    }

    /**
     * Retrieves the specified item in the collection.
     * 
     * @param integer $offset
     * @return Record
     */
    public function offsetGet($offset)
    {
        if ($this->offsetExists($offset)) {
            return $this->records[$offset];
        }

        throw new \RuntimeException("Invalid collection index {$offset}");
    }

    /**
     * Assigns an item in the collection.
     * 
     * @param integer $offset
     * @param Record $value
     */
    public function offsetSet($offset, $value)
    {
        $this->records[$offset] = $value;
    }

    /**
     * Removes an item from the collection.
     * 
     * @param integer $offset
     */
    public function offsetUnset($offset)
    {
        if (isset($this->records[$offset])) {
            unset($this->records[$offset]);
        }
    }
}
