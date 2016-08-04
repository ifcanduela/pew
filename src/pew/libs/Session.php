<?php

namespace pew\libs;

use Symfony\Component\HttpFoundation\Session\Session as SymfonySession;
use ArrayAccess;

class Session extends SymfonySession implements ArrayAccess
{
    public function addFlash($key, $value)
    {
        return $this->getFlashBag()->add($key, $value);
    }

    public function getFlash($key = null, $default = null)
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
                return $default;
            }

            $value = $value[$index];
        }

        return $value;
    }


    public function offsetSet($key, $value)
    {
        return $this->set($key, $value);
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
