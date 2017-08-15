<?php

namespace pew\libs;

use Symfony\Component\HttpFoundation\Session\Session as SymfonySession;
use ArrayAccess;

class Session extends SymfonySession implements ArrayAccess
{
    /**
     * Set a flash message.
     *
     * @param string $key
     * @param mixed $value
     */
    public function addFlash(string $key, $value)
    {
        return $this->getFlashBag()->add($key, $value);
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
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getFlash(string $key = null, $default = null)
    {
        if (null === $key) {
            return $this->getFlashBag()->all();
        } elseif ($this->getFlashBag()->has($key)) {
            return $this->getFlashBag()->get($key);
        }

        return $default;
    }

    public function offsetGet($key)
    {
        $sessionData = $this->all();
        $indexes = explode('.', $key);
        $first_index = array_shift($indexes);

        if (!$this->has($first_index)) {
            return null;
        }

        $value = $sessionData[$first_index];

        while (!empty($indexes)) {
            $index = array_shift($indexes);

            if (!isset($value[$index])) {
                return null;
            }

            $value = $value[$index];
        }

        return $value;
    }

    public function offsetSet($key, $value)
    {
        $this->set($key, $value);
    }

    public function offsetExists($key)
    {
        return $this->has($key);
    }

    public function offsetUnset($key)
    {
        return $this->remove($key);
    }
}
