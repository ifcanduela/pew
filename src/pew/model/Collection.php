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
        $this->items = $items;
    }

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
     * Appends all items of an array to the end of the collection.
     *
     * If more than one array is passed, their order is retained.
     *
     * @param array $array
     */
    public function append(array ...$array)
    {
        $items = array_merge($this->items, ...$array);

        return new static($items);
    }

    /**
     * Split the collection into chunks.
     *
     * Each chunk will be itself a collection.
     *
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

    /**
     * Create a new collection from a property or array index from every
     * item in the collection.
     *
     * @param string $field
     * @return static
     */
    public function field($field)
    {
        $items = array_map(function ($item) use ($field) {
            return $item->$field ?? $item[$field] ?? null;
        }, $this->items);

        return new static($items);
    }

    /**
     * Adds items to the collection until a certain element count is reached.
     *
     * If a callable is supplied as `$value`, it will be called to use its return value as fill item.
     * 
     * @param int $count
     * @param callable|mixed $value
     * @return static
     */
    public function fill($count, $value)
    {
        $items = $this->items;
        $start = count($items);

        while ($start < $count) {
            if (is_callable($value)) {
                $items[$start] = $value();
            } else {
                $items[$start] = $value;
            }

            $count++;
        }

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
     * Remove an item from the end of the collection.
     *
     * @return mixed
     */
    public function pop()
    {
        return array_pop($this->items);
    }

    /**
     * Prepends all items of an array to the beginning of the collection.
     *
     * If more than one array is passed, their order is retained.
     *
     * @param array $array
     */
    public function prepend(array ...$array)
    {
        $array[] = $this->items;
        $items = array_merge(...$array);

        return new static($items);
    }

    /**
     * Add an item to the end of the collection.
     *
     * @param mixed $item
     * @return null
     */
    public function push($item)
    {
        array_push($this->items, $item);

        return $this;
    }

    /**
     * Take one or more random items from the collection.
     *
     * @param int $count
     * @return static|mixed
     */
    public function random($count = 1)
    {
        $single = $count === 1;
        $pool = $this->items;
        shuffle($pool);

        do {
            $items[] = array_pop($pool);
        } while (--$count);

        if ($single && $items) {
            return $items[0];
        }

        return new static($items);
    }

    /**
     * Reduce the collection to a single value.
     *
     * @param callable $callback
     * @param mixed $initial
     * @return mixed
     */
    public function reduce(callable $callback, $initial = null)
    {
        return array_reduce($this->items, $callback, $initial);
    }

    /**
     * Reverse the order of the items in the collection.
     *
     * @param bool $preserveKeys
     * @return static
     */
    public function reverse($preserveKeys = false)
    {
        return new static(array_reverse($this->items, $preserveKeys));
    }

    /**
     * Remove an item from the beginning of the collection.
     *
     * @return mixed
     */
    public function shift()
    {
        return array_shift($this->items);
    }

    /**
     * Randomize the order of the items in the collection.
     *
     * @return static
     */
    public function shuffle()
    {
        $items = $this->items;

        shuffle($items);

        return new static($items);
    }

    /**
     * Take a sequence of items from the collection.
     *
     * @param int $offset
     * @param int $length
     * @return static
     */
    public function slice($offset, $length = null)
    {
        return new static(array_slice($this->items, $offset, $length));
    }

    /**
     * Insert or replace a sequence of items in the collection.
     *
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
     * Sort the items in the collection.
     *
     * @param string|callable|null $field
     * @param bool $reverse
     * @return static
     */
    public function sort($field = null, $reverse = false)
    {
        $items = $this->items;

        if (is_callable($field)) {
            usort($items, $field);
        } elseif ($field) {
            usort($items, function ($a, $b) use ($field) {
                $_a = $a->$field ?? $a[$field] ?? null;
                $_b = $a->$field ?? $a[$field] ?? null;

                return $_a <=> $_b;
            });
        } else {
            sort($items);
        }

        if ($reverse) {
            array_reverse($items);
        }

        return new static($items);
    }

    /**
     * Return the items of the collection.
     *
     * If any item has a `toArray` method, it will be called.
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
     * Get all items in order until a condition is met.
     *
     * The signature for the callable is ($key, $value, $index)
     * 
     * @param callable $condition 
     * @return static
     */
    public function until(callable $condition)
    {
        $items = [];
        $index = 0;

        foreach ($this->items as $key => $value) {
            $result = $condition($key, $value, $index);

            if ($result) {
                $items[$key] = $value;
            } else {
                break;
            }
        }

        return new static($items);
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
     * Aggregate items from one or more arrays into a collection of arrays.
     *
     * This method will take the items of the collection, turn each into an array,
     * and append the corresponding items from each of the provided arrays to the
     * new array item.
     *
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

    /**
     * Clone the collection.
     *
     * @return static
     */
    public function __clone()
    {
        $items = $this->items;

        return new static($items);
    }
}