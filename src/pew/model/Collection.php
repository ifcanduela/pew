<?php

namespace pew\model;

class Collection implements \ArrayAccess, \Countable, \IteratorAggregate, \JsonSerializable
{
    protected $items = [];

    /**
     * Create a collection.
     * 
     * @param array $items
     */
    public function __construct(array $items = [])
    {
        $this->items = $items
;    }

    /**
     * Create a collection.
     * 
     * @param array $items
     * @return static
     */
    public static function create(array $items = [])
    {
        return new static($items);
    }

    /**
     * ArrayAccess
     * 
     * @param mixed $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        return array_key_exists($offset, $this->items);
    }

    /**
     * ArrayAccess
     * 
     * @param mixed $offset
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return $this->items[$offset];
    }

    /**
     * ArrayAccess
     * 
     * @param mixed $offset
     * @param mixed $value
     * @return null
     */
    public function offsetSet($offset, $value = null)
    {
        if (is_null($offset)) {
            $this->items[] = $value;
        } else {
            $this->items[$offset] = $value;
        }
    }

    /**
     * ArrayAccess
     * 
     * @param mixed $offset
     * @return null
     */
    public function offsetUnset($offset)
    {
        unset($this->items[$offset]);
    }

    /**
     * Countable
     * 
     * @return int
     */
    public function count()
    {
        return count($this->items);
    }

    /**
     * IteratorAggregate
     *
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->items);
    }

    /**
     * JsonSerializable
     *
     * @return string
     */
    public function jsonSerialize()
    {
        return $this->items;
    }

    /**
     * @param int $chunkSize
     * @param bool $preserveKeys
     * @return static
     */
    public function chunk($chunkSize, $preserveKeys = false)
    {
        $items = array_chunk($this->items, $chunkSize, $preserveKeys);

        return new static(array_map(function ($chunk) {
            return new static($chunk);
        }, $items));
    }

    public function field($field)
    {
        $items = array_map(function ($item) use ($field) {
            return $item->$field ?? $item[$field] ?? null;
        }, $this->items);

        return new static($items);
    }

    /**
     * Filter the items in the collection.
     *
     * Callback signature is function($value, $key): bool
     * 
     * @param callable $callback
     * @param int $flag
     * @return static
     */
    public function filter($callback, $flag = ARRAY_FILTER_USE_BOTH)
    {
        $items = array_filter($this->items, $callback, $flag);

        return new static($items);
    }

    /**
     * Get the first item or items in the collection.
     * 
     * @param integer $count
     * @return static|mixed
     */
    public function first($count = 1)
    {
        if ($count === 1) {
            return $this->items[0];
        }

        return new static(array_slice($this->items, 0, $count));
    }

    /**
     * Group the items in the collection.
     *
     * Callback signature is function($value, $key): bool
     * 
     * @param string|callable $field
     * @return static
     */
    public function group($field)
    {
        $items = [];

        foreach ($this->items as $key => $value) {
            if (is_callable($field)) {
                $items[$field($value, $key)][] = $value;
            } else {
                $items[$value->$field][] = $value;
            }
        }

        return new static($items);
    }

    /**
     * Check if the key exists in the collection.
     * 
     * @param mixed $key
     * @return bool
     */
    public function hasKey($key)
    {
        return array_key_exists($key, $this->items);
    }

    /**
     * Check if the value exists in the collection.
     * 
     * @param mixed $item
     * @param bool $strict
     * @return bool
     */
    public function hasValue($item, $strict = false)
    {
        return in_array($item, $this->items, $strict);
    }

    /**
     * Join the items into a string.
     * 
     * @param string $glue
     * @param string|callable $field
     * @return string
     */
    public function implode($glue, $field = null)
    {
        if (is_callable($field)) {
            $items = array_map($field, $this->items);
        } elseif (is_string($field)) {
            $items = array_map(function ($item) use ($field) {
                return $item->$field ?? $item[$field] ?? null;
            }, $this->items);
        } else {
            $items = $this->items;
        }

        return implode($glue, $items);
    }

    /**
     * Create a collection indexed by a field.
     * 
     * @param string|callable $field
     * @return static
     */
    public function index($field)
    {
        $items = [];

        foreach ($this->items as $key => $value) {
            if (is_callable($field)) {
                $items[$field($key, $value)] = $value;
            } else {
                $items[$value->$field] = $value;
            }
        }

        return new static($items);
    }

    /**
     * Get the underlying items of the collection.
     * 
     * @return array
     */
    public function items()
    {
        return $this->items;
    }

    /**
     * Get the keys of the collection.
     * 
     * @return static
     */
    public function keys()
    {
        return new static(array_keys($this->items));
    }

    /**
     * Get the last item or items in the collection.
     * 
     * @param int $count
     * @return static|mixed
     */
    public function last($count = 1)
    {
        if ($count === 1) {
            return $this->items[count($this->items) - 1];
        }

        return new static(array_slice($this->items, count($this->items) - $count));
    }

    /**
     * Map the items into other items.
     * 
     * @param callable $callback
     * @param array[] $arrays
     * @return static
     */
    public function map(callable $callback, ...$arrays)
    {
        $items = array_map($callback, $this->items, ...$arrays);

        return new static($items);
    }

    /**
     * Get the items that match a set of keys.
     * 
     * @param int|string $keys
     * @return static
     */
    public function only(...$keys)
    {
        $items = array_intersect_key($this->items, array_flip($keys));

        return new static($items);
    }

    /**
     * @return mixed
     */
    public function pop()
    {
        return array_pop($this->items);
    }

    /**
     * @param mixed $item
     * @return null
     */
    public function push($item)
    {
        array_push($this->items, $item);

        return $this;
    }

    /**
     * @param int $count
     * @return static|mixed
     */
    public function random($count = 1)
    {
        $single = $count === 1;
        $items = [];
        $max = count($this->items) - 1;

        do {
            $items[] = $this->items[mt_rand(0, $max)];
        } while (--$count);

        if ($single) {
            return $items[0];
        }

        return new static($items);
    }

    /**
     * @param callable $callback
     * @param mixed $initial
     * @return mixed
     */
    public function reduce(callable $callback, $initial = null)
    {
        return array_reduce($this->items, $callback, $initial);
    }

    /**
     * @param bool $preserveKeys
     * @return static
     */
    public function reverse($preserveKeys = false)
    {
        return new static(array_reverse($this->items, $preserveKeys));
    }

    /**
     * @return mixed
     */
    public function shift()
    {
        return array_shift($this->items);
    }

    /**
     * @return static
     */
    public function shuffle()
    {
        $items = $this->items;

        shuffle($items);

        return new static($items);
    }

    /**
     * @param int $offset
     * @param int $length
     * @return static
     */
    public function slice($offset, $length = null)
    {
        return new static(array_slice($this->items, $offset, $length));
    }

    /**
     * @param int $offset
     * @param int $length
     * @param array $replacement
     * @return static
     */
    public function splice($offset, $length, $replacement = [])
    {
        $items = $this->items;
        array_splice($items, $offset, $length, $replacement);

        return new static($items);
    }

    /**
     * @param string|callable|null $field
     * @return static
     */
    public function sort($field = null)
    {
        $collection = new Collection($this->items);

        if (is_callable($field)) {
            usort($collection->items, $field);
        } elseif ($field) {
            usort($collection->items, function ($a, $b) use ($field) {
                return $a->$field < $b->$field;
            });
        } else {
            sort($collection->items);
        }

        return $collection;
    }

    /**
     * 
     * @return array
     */
    public function toArray()
    {
        $items = array_map(function ($item) {
            if (method_exists($item, 'toArray')) {
                return $item->toArray();
            }

            return $item;
        }, $this->items);

        return $items;
    }

    /**
     * Convert the collection to a JSON string.
     *
     * @param int $options
     * @param int $depth
     * @return string
     */
    public function toJson($options = null, $depth = 512)
    {   
        $encode = json_encode($this->items, $options, $depth);

        if (false === $encode) {
            throw new \RuntimeException(json_last_error_msg());
        }

        return $encode;
    }

    /**
     * Add an item to the beginning of the collection.
     * 
     * @param mixed $item
     * @return null
     */
    public function unshift($item)
    {
        array_unshift($this->items, $item);

        return $this;
    }

    /**
     * Get the value of the items in the collection.
     * 
     * @return static
     */
    public function values()
    {
        return new static(array_values($this->items));
    }

    /**
     * Apply a callback to each item in the collection.
     * 
     * @param callable $callback
     * @param mixed $userData
     * @return self
     */
    public function walk($callback, $userData = null)
    {
        array_walk($this->items, $callback, $userData);

        return $this;
    }

    /**
     * Get the items that don't match a set of keys.
     * 
     * @param string|int $keys
     * @return static
     */
    public function without(...$keys)
    {
        $items = array_diff_key($this->items, array_flip($keys));

        return new static($items);
    }

    /**
     * @param string $className
     * @return static
     */
    public function wrap($className)
    {
        return new static(array_map(function ($item) use ($className) {
            return $className::fromArray($item);
        }, $this->items));
    }

    /**
     * @param array $arrays
     * @return static
     */
    public function zip(...$arrays)
    {
        $items = array_map(function (...$values) {
            return $values;
        }, $this->items, ...$arrays);

        return new static($items);
    }

    public function __clone()
    {
        return new static($this->items);
    }
}
