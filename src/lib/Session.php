<?php declare(strict_types=1);

namespace pew\lib;

use ArrayAccess;
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
    public function addFlash(string $key, $value)
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

    /**
     * Get a session variable.
     *
     * @param string $key
     * @return mixed
     */
    #[\ReturnTypeWillChange]
    public function offsetGet($key)
    {
        $sessionData = $this->all();
        $indexes = explode(".", $key);
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
     * @param string $key
     * @param mixed $value
     * @return void
     */
    #[\ReturnTypeWillChange]
    public function offsetSet($key, $value)
    {
        $this->set($key, $value);
    }

    /**
     * Check a session variable.
     *
     * @param string $key
     * @return bool
     */
    #[\ReturnTypeWillChange]
    public function offsetExists($key)
    {
        return $this->has($key);
    }

    /**
     * Unset a session variable.
     *
     * @param string $key
     * @return void
     */
    #[\ReturnTypeWillChange]
    public function offsetUnset($key)
    {
        $this->remove($key);
    }
}
