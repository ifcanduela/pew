<?php

declare(strict_types=1);

namespace pew\lib;

use ArrayAccess;
use ArrayIterator;
use Countable;
use IteratorAggregate;
use JsonSerializable;
use RecursiveArrayIterator;
use RecursiveIteratorIterator;
use RuntimeException;

class Collection implements ArrayAccess, Countable, IteratorAggregate, JsonSerializable
{
    /** @var array Collection items */
    protected array $items = [];

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
    public static function create(array $items = []): Collection
    {
        return new static($items);
    }

    /**
     * Create a collection from an array.
     *
     * @param array $items
     * @return static
     */
    public static function fromArray(array $items): Collection
    {
        return new static($items);
    }

    /**
     * Check if a key is set.
     *
     * @param mixed $offset
     * @return bool
     */
    public function offsetExists(mixed $offset): bool
    {
        return array_key_exists($offset, $this->items);
    }

    /**
     * Get an item by key.
     *
     * @param mixed $offset
     * @return mixed
     */
    public function offsetGet(mixed $offset): mixed
    {
        return $this->items[$offset];
    }

    /**
     * Set an item by key.
     *
     * @param mixed $offset
     * @param mixed|null $value
     * @return void
     */
    public function offsetSet(mixed $offset, mixed $value = null): void
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
    public function offsetUnset(mixed $offset): void
    {
        unset($this->items[$offset]);
    }

    /**
     * Get the number of items in the collection.
     *
     * @return int
     */
    public function count(): int
    {
        return count($this->items);
    }

    /**
     * Get an iterator for the collection.
     *
     * @return ArrayIterator
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->items);
    }

    /**
     * Specify data to serialize as JSON.
     *
     * @return array
     */
    public function jsonSerialize(): array
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
    public function append(array ...$array): Collection
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
    public function chunk(int $chunkSize, bool $preserveKeys = false): Collection
    {
        $items = array_chunk($this->items, $chunkSize, $preserveKeys);

        return new static(
            array_map(
                fn ($chunk) => new static($chunk),
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
    public function field(string $field): Collection
    {
        $items = array_map(
            fn ($item) => $item->{$field} ?? $item[$field] ?? null,
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
    public function fill(int $count, mixed $value): Collection
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
     * @param callable|null $callback
     * @param int $flag
     * @return static
     */
    public function filter(callable $callback = null, int $flag = ARRAY_FILTER_USE_BOTH): Collection
    {
        $items = array_filter($this->items, $callback, $flag);

        return new static($items);
    }

    /**
     * Get the first item or items in the collection.
     *
     * @param int $count
     * @return mixed
     */
    public function first(int $count = 1): mixed
    {
        $count = max(1, $count);

        if ($count === 1) {
            return $this->items[0];
        }

        return new static(array_slice($this->items, 0, $count));
    }

    /**
     * Flatten the items in the collection into a one-dimensional array.
     *
     * @return static
     */
    public function flatten(): Collection
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
     * @param callable|string $field
     * @return static
     */
    public function group(callable|string $field): Collection
    {
        $items = [];

        foreach ($this->items as $key => $value) {
            if (is_callable($field)) {
                $k = $field($value, $key);
            } else {
                $k = $value->{$field} ?? $value[$field] ?? null;
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
    public function hasKey(mixed $key): bool
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
    public function hasValue(mixed $item, bool $strict = false): bool
    {
        return in_array($item, $this->items, $strict);
    }

    /**
     * Join the items into a string.
     *
     * @param string $glue
     * @param callable|string|null $field
     * @return string
     */
    public function implode(string $glue, callable|string $field = null): string
    {
        if (is_callable($field)) {
            $items = array_map($field, $this->items);
        } elseif (is_string($field)) {
            $items = array_map(
                fn ($item) => $item->{$field} ?? $item[$field] ?? null,
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
     * @param callable|string $field
     * @return static
     */
    public function index(callable|string $field): Collection
    {
        $items = [];
        $isCallable = is_callable($field);

        foreach ($this->items as $key => $value) {
            if ($isCallable) {
                $index = $field($value, $key);
                $items[$index] = $value;
            } else {
                $index = $value->{$field} ?? $value[$field] ?? null;

                if ($index) {
                    $items[$index] = $value;
                }
            }
        }

        return new static($items);
    }

    /**
     * Find the index of the first element in the collection that satisfies a condition.
     *
     * @param callable $callback
     * @return int|string|null
     */
    public function findIndex(callable $callback): int|string|null
    {
        foreach ($this->items as $key => $value) {
            if ($callback($value, $key)) {
                return $key;
            }
        }

        return null;
    }

    /**
     * Find the index of the first element in the collection that satisfies a condition.
     *
     * @param callable $callback
     * @return int|string|null
     */
    public function findKey(callable $callback): int|string|null
    {
        return $this->findIndex($callback);
    }

    /**
     * Find the first value in the collection that satisfies a condition.
     *
     * @param callable $callback
     * @return mixed
     */
    public function findValue(callable $callback): mixed
    {
        foreach ($this->items as $key => $value) {
            if ($callback($value, $key)) {
                return $value;
            }
        }

        return null;
    }

    /**
     * Get the underlying items of the collection.
     *
     * @return array
     */
    public function items(): array
    {
        return $this->items;
    }

    /**
     * Get the keys of the collection.
     *
     * @return static
     */
    public function keys(): Collection
    {
        return new static(array_keys($this->items));
    }

    /**
     * Get the last item or items in the collection.
     *
     * @param int $count
     * @return mixed
     */
    public function last(int $count = 1): mixed
    {
        $count = max(1, $count);

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
    public function map(callable $callback, ...$arrays): Collection
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
    public function only(...$keys): Collection
    {
        $items = array_intersect_key($this->items, array_flip($keys));

        return new static($items);
    }

    /**
     * Remove an item from the end of the collection.
     *
     * @return mixed
     */
    public function pop(): mixed
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
    public function prepend(array ...$array): Collection
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
    public function push(mixed $item): Collection
    {
        $this->items[] = $item;

        return $this;
    }

    /**
     * Take one or more random items from the collection.
     *
     * Returns null if the collection is empty. Return a Collection with the
     * random items if `$count` is more than 1, otherwise will return a single
     * random item.
     *
     * @param int $count
     * @return mixed|Collection
     */
    public function random(int $count = 1): mixed
    {
        if (!$this->items) {
            return null;
        }

        $count = max(1, $count);

        $returnFirstItem = $count === 1;
        $items = [];

        while ($count--) {
            $i = array_rand($this->items);
            $items[] = $this->items[$i];
        }

        if ($returnFirstItem && $items) {
            return $items[0];
        }

        return new static($items);
    }

    /**
     * Reduce the collection to a single value.
     *
     * @param callable $callback
     * @param mixed|null $initial
     * @return mixed
     */
    public function reduce(callable $callback, mixed $initial = null): mixed
    {
        return array_reduce($this->items, $callback, $initial);
    }

    /**
     * Reverse the order of the items in the collection.
     *
     * @param bool $preserveKeys
     * @return static
     */
    public function reverse(bool $preserveKeys = false): Collection
    {
        return new static(array_reverse($this->items, $preserveKeys));
    }

    /**
     * Remove an item from the beginning of the collection.
     *
     * @return mixed
     */
    public function shift(): mixed
    {
        return array_shift($this->items);
    }

    /**
     * Randomize the order of the items in the collection.
     *
     * @return static
     */
    public function shuffle(): Collection
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
    public function slice(int $offset, int $length, bool $preserveKeys = false): Collection
    {
        return new static(array_slice($this->items, $offset, $length, $preserveKeys));
    }

    /**
     * Insert or replace a sequence of items in the collection.
     *
     * @param int $offset
     * @param int $length
     * @param mixed $replacement
     * @return static
     */
    public function splice(int $offset, int $length, mixed $replacement = []): Collection
    {
        $items = $this->items;
        array_splice($items, $offset, $length, $replacement);

        return new static($items);
    }

    /**
     * Sort the items in the collection.
     *
     * @param callable|string|null $field
     * @return static
     */
    public function sort(callable|string $field = null): Collection
    {
        $items = $this->items;

        if (is_callable($field)) {
            usort($items, $field);
        } elseif ($field) {
            usort(
                $items,
                function ($a, $b) use ($field) {
                    $_a = $a->{$field} ?? $a[$field] ?? null;
                    $_b = $b->{$field} ?? $b[$field] ?? null;

                    return $_a <=> $_b;
                }
            );
        } else {
            sort($items);
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
    public function toArray(bool $callToArrayOnItems = false): array
    {
        $keys = array_keys($this->items);

        $values = $callToArrayOnItems ? array_map(
            function ($item) {
                if (is_object($item)) {
                    if (method_exists($item, "toArray")) {
                        return $item->toArray();
                    }

                    return get_object_vars($item);
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
    public function toJson(int $options = 0, int $depth = 512): string
    {
        $encode = json_encode($this->items, $options, $depth);

        if (false === $encode) {
            throw new RuntimeException("JSON encoding error: " . json_last_error_msg());
        }

        return $encode;
    }

    /**
     * Add an item to the beginning of the collection.
     *
     * @param mixed $item
     * @return self
     */
    public function unshift(mixed $item): Collection
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
    public function until(callable $condition): Collection
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
    public function values(): Collection
    {
        return new static(array_values($this->items));
    }

    /**
     * Apply a callback to each item in the collection.
     *
     * @param callable $callback
     * @param mixed|null $userData
     * @return static
     */
    public function walk(callable $callback, mixed $userData = null): Collection
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
    public function without(...$keys): Collection
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
    public function zip(...$arrays): Collection
    {
        $items = array_map(
            fn (...$values) => new static($values),
            $this->items,
            ...$arrays
        );

        return new static($items);
    }
}
