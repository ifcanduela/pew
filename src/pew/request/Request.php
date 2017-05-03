<?php

namespace pew\request;

/**
 * A shell class that centralizes information about the current request.
 *
 * @author ifcanduela <ifcanduela@gmail.com>
 */
class Request extends \Symfony\Component\HttpFoundation\Request
{
    /**
     * Check if the current request uses the POST method.
     *
     * @return bool
     */
    public function isPost()
    {
        return $this->isMethod('POST');
    }

    /**
     * Check if the current request uses the GET method.
     *
     * @return bool
     */
    public function isGet()
    {
        return $this->isMethod('GET');
    }

    /**
     * Retrieve the HTTP method used to make the current request.
     *
     * This value will take into account a `_method` field passed in
     * the request body.
     *
     * @return string
     */
    public function method()
    {
        $method = $this->request->get('_method') ?? $this->getMethod();

        return strtoupper($method);
    }

    /**
     * Retrieve a key from the request body, or all values.
     *
     * If `$key` is `null`, all values will be returned in an array.
     *
     * @param string|null $key
     * @return string|array
     */
    public function post($key = null)
    {
        if (is_null($key)) {
            return $this->request->all();
        }

        return $this->request->get($key);
    }

    /**
     * Check if the request demands a JSON response.
     *
     * The check is performed against the `Accepts` header of the
     * HTTP request.
     *
     * @return bool
     */
    public function isJson()
    {
        return false !== strpos($this->headers->get('Accept'), 'json');
    }
}
