<?php

namespace pew\request;

/**
 * A shell class that centralizes information about the current request.
 */
class Request extends \Symfony\Component\HttpFoundation\Request
{
    /** @var string */
    public $appUrl;

    /**
     * Get the URL from which this request is executed, with scheme, server and base path.
     *
     * @return string
     */
    public function appUrl()
    {
        if (!$this->appUrl) {
            $appUrl = $this->getSchemeAndHttpHost() . $this->getBaseUrl();
            # find out the script filename
            $scriptFileName = preg_quote(pathinfo($this->getScriptName(), PATHINFO_BASENAME));
            # ensure the URL does not contain the script filename
            $this->appUrl = preg_replace("/{$scriptFileName}$/", "", $appUrl);
        }

        return $this->appUrl;
    }

    /**
     * Check if the current request uses the POST method.
     *
     * @return bool
     */
    public function isPost()
    {
        return $this->isMethod("POST");
    }

    /**
     * Check if the current request uses the GET method.
     *
     * @return bool
     */
    public function isGet()
    {
        return $this->isMethod("GET");
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
        $method = $this->request->get("_method") ?? $this->getMethod();

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
     * HTTP request and the suffix of the URL.
     *
     * @return bool
     */
    public function isJson()
    {
        # check if the requested URL ends in '.json' or '|json'
        if (preg_match('/[\.|]json$/', $this->getPathInfo())) {
            return true;
        }

        # check if the 'Accept' header contains 'json'
        return false !== strpos($this->headers->get('Accept'), 'json');
    }
}
