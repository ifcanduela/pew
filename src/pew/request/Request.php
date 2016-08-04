<?php

namespace pew\request;

use pew\libs\Env;
use pew\router\Route;

/**
 * A shell class that centralizes information about the current request.
 *
 * @author ifcanduela <ifcanduela@gmail.com>
 */
class Request extends \Symfony\Component\HttpFoundation\Request
{
    public function isPost()
    {
        return $this->isMethod('POST');
    }

    public function isGet()
    {
        return $this->isMethod('GET');
    }

    public function post($key = null)
    {
        if (is_null($key)) {
            return $this->request->all();
        }

        return $this->request->get($key);
    }

    public function isJson()
    {
        return false !== strpos($this->headers->get('Accept'), 'json');
    }
}
