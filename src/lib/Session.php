<?php

declare(strict_types=1);

namespace pew\lib;

use ArrayAccess;
use ReturnTypeWillChange;
use Symfony\Component\HttpFoundation\Session\Session as SymfonySession;

/**
 * Wrapper around Symfony Session.
 */
class Session extends SymfonySession implements ArrayAccess
{
    /**
     * Set a flash message.
     *
     * @param string $key
     * @param mixed  $value
     * @return void
     */
    public function addFlash(string $key, mixed $value): void
    {
        $this->getFlashBag()->add($key, $value);
    }

    /**
     * Retrieve flash messages.
     *
     * 1. Without arguments, this method will return all existing flash messages in an
     *    associative array.
     * 2. With a single argument, a flash message matching the $key will be returned,
     *    or NULL if it's not available.
     * 3. The third arguments is a default value to return in case the key provided in
     *    case 2 is not available.
     *
     * @param string|null $key
     * @param mixed|null $default
     * @return mixed
     */
    public function getFlash(string $key = null, mixed $default = null): mixed
    {
        if (null === $key) {
            return $this->getFlashBag()->all();
        } elseif ($this->getFlashBag()->has($key)) {
            return $this->getFlashBag()->get($key);
        }

        return $default;
    }

    /**
     * Get a session variable.
     *
     * @param string $offset
     * @return mixed
     */
    #[ReturnTypeWillChange]
    public function offsetGet($offset): mixed
    {
        $sessionData = $this->all();
        $indexes = explode(".", $offset);
        $firstIndex = array_shift($indexes);

        if (!$this->has($firstIndex)) {
            return null;
        }

        $value = $sessionData[$firstIndex];

        while (!empty($indexes)) {
            $index = array_shift($indexes);

            if (!isset($value[$index])) {
                return null;
            }

            $value = $value[$index];
        }

        return $value;
    }

    /**
     * Set a session variable.
     *
     * @param string $offset
     * @param mixed $value
     * @return void
     */
    #[ReturnTypeWillChange]
    public function offsetSet($offset, mixed $value): void
    {
        $this->set($offset, $value);
    }

    /**
     * Check a session variable.
     *
     * @param string $offset
     * @return bool
     */
    #[ReturnTypeWillChange]
    public function offsetExists($offset): bool
    {
        return $this->has($offset);
    }

    /**
     * Unset a session variable.
     *
     * @param string $offset
     * @return void
     */
    #[ReturnTypeWillChange]
    public function offsetUnset($offset): void
    {
        $this->remove($offset);
    }
}
