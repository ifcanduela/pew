<?php declare(strict_types=1);

namespace pew\model;

use ArrayAccess;
use ArrayIterator;
use Countable;
use IteratorAggregate;
use JsonSerializable;
use RecursiveArrayIterator;
use RecursiveIteratorIterator;
use RuntimeException;

/**
 * Collection wraps an array and provides an object-oriented interface to the most common
 * array functions, and some extra functionality.
 */
class Collection implements ArrayAccess, Countable, IteratorAggregate, JsonSerializable
{
    /**
     *
     *
     * @var array Collection items
     */
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
     * Create a collection from an array.
     *
     * @param array $items
     * @return static
     */
    public static function fromArray(array $items)
    {
        return new static($items);
    }

    /**
     * Check if a key is set.
     *
     * @param mixed $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        return array_key_exists($offset, $this->items);
    }

    /**
     * Get an item by key.
     *
     * @param mixed $offset
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return $this->items[$offset];
    }

    /**
     * Set an item by key.
     *
     * @param mixed $offset
     * @param mixed $value
     * @return void
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
     * Remove an item by key.
     *
     * @param mixed $offset
     * @return void
     */
    public function offsetUnset($offset)
    {
        unset($this->items[$offset]);
    }

    /**
     * Get the number of items in the collection.
     *
     * @return int
     */
    public function count()
    {
        return count($this->items);
    }

    /**
     * Get an iterator for the collection.
     *
     * @return ArrayIterator
     */
    public function getIterator()
    {
        return new ArrayIterator($this->items);
    }

    /**
     * Specify data to serialize as JSON.
     *
     * @return array
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
     * @param array ...$array
     * @return static
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

        return new static(
            array_map(
                function ($chunk) {
                    return new static($chunk);
                },
                $items
            )
        );
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
        $items = array_map(
            function ($item) use ($field) {
                return $item->$field ?? $item[$field] ?? null;
            },
            $this->items
        );

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
                $items[$start] = $value($start);
            } else {
                $items[$start] = $value;
            }

            $start++;
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
     * Flatten the items in the collection into a single-dimensional array.
     *
     * @return static
     */
    public function flatten()
    {
        $it = new RecursiveIteratorIterator(new RecursiveArrayIterator($this->items));

        return new static(iterator_to_array($it, false));
    }

    /**
     * Group the items in the collection.
     *
     * String keys will be preserved, but numeric keys will not.
     *
     * Pass a function as `$field` to customize the group names. The signature
     * of the callback is function($value, $key): string|int
     *
     * $collection->group(function ($value, $key) {
     *     return strtolower($value->someFieldName);
     * });
     *
     * @param string|callable $field
     * @return static
     */
    public function group($field)
    {
        $items = [];

        foreach ($this->items as $key => $value) {
            if (is_callable($field)) {
                $k = $field($value, $key);
            } else {
                $k = $value->$field ?? $value[$field] ?? null;
            }

            if (!isset($items[$k])) {
                $items[$k] = new static();
            }

            if (is_string($key)) {
                $items[$k][$key] = $value;
            } else {
                $items[$k][] = $value;
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
            $items = array_map(
                function ($item) use ($field) {
                    return $item->$field ?? $item[$field] ?? null;
                },
                $this->items
            );
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
                $index = $field($value, $key);
                $items[$index] = $value;
            } else {
                $index = $value->$field ?? $value[$field] ?? null;

                if ($index) {
                    $items[$index] = $value;
                }
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
     * @param array $keys
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
     * @param array ...$array
     * @return static
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
     * @return static
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
        if (!$this->items) {
            return null;
        }

        $single = $count === 1;
        $items = [];

        while ($count--) {
            $i = array_rand($this->items);
            $items[] = $this->items[$i];
        }

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
     * @param bool $preserveKeys
     * @return static
     */
    public function slice($offset, $length = null, $preserveKeys = false)
    {
        return new static(array_slice($this->items, $offset, $length, $preserveKeys));
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
            usort(
                $items,
                function ($a, $b) use ($field) {
                    $_a = $a->$field ?? $a[$field] ?? null;
                    $_b = $b->$field ?? $b[$field] ?? null;

                    return $_a <=> $_b;
                }
            );
        } else {
            sort($items);
        }

        if ($reverse) {
            $items = array_reverse($items);
        }

        return new static($items);
    }

    /**
     * Convert the collection into a plain array.
     *
     * If the $callToArrayOnItems parameter is `true` and any item has a
     * `toArray` method, it will be called.
     *
     * @param bool $callToArrayOnItems
     * @return array
     */
    public function toArray(bool $callToArrayOnItems = false)
    {
        $keys = array_keys($this->items);

        $values = $callToArrayOnItems ? array_map(
            function ($item) {
                if (method_exists($item, "toArray")) {
                    return $item->toArray();
                }

                return $item;
            },
            $this->items
        ) : $this->items;

        return array_combine($keys, $values);
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
            throw new RuntimeException(json_last_error_msg());
        }

        return $encode;
    }

    /**
     * Add an item to the beginning of the collection.
     *
     * @param mixed $item
     * @return self
     */
    public function unshift($item)
    {
        array_unshift($this->items, $item);

        return $this;
    }

    /**
     * Get all items in order until a condition is met.
     *
     * The signature for the callable is ($key, $value, $index, $items)
     *
     * @param callable $condition
     * @return static
     */
    public function until(callable $condition)
    {
        $items = [];
        $index = 0;

        foreach ($this->items as $key => $value) {
            $items[$key] = $value;
            $result = $condition($key, $value, $index, $items);

            if ($result) {
                break;
            }
        }

        return new static($items);
    }

    /**
     * Get a new Collection with the values of the current one, discarding keys.
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
     * @param mixed ...$keys
     * @return static
     */
    public function without(...$keys)
    {
        $items = array_diff_key($this->items, array_flip($keys));

        return new static($items);
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
        $items = array_map(
            function (...$values) {
                return new static($values);
            },
            $this->items,
            ...$arrays
        );

        return new static($items);
    }
}
