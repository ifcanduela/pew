<?php

declare(strict_types=1);

namespace pew\request;

/**
 * A shell class that centralizes information about the current request.
 */
class Request extends \Symfony\Component\HttpFoundation\Request
{
    /** @var string|null */
    protected ?string $appUrl;

    /** @var bool|null */
    protected ?bool $acceptsJson;

    /**
     * Request constructor.
     *
     * @param array $query
     * @param array $request
     * @param array $attributes
     * @param array $cookies
     * @param array $files
     * @param array $server
     * @param null $content
     */
    public function __construct(array $query = [], array $request = [], array $attributes = [], array $cookies = [], array $files = [], array $server = [], $content = null)
    {
        parent::__construct($query, $request, $attributes, $cookies, $files, $server, $content);

        if ($this->isPost()) {
            // Check for a JSON request body
            $bodyIsJson = str_starts_with($this->headers->get("Content-Type", ""), "application/json");

            if ($bodyIsJson) {
                // Decode the JSON body and replace the POST parameter bag
                $this->request->replace($this->toArray());
            }
        }
    }

    /**
     * Get the URL from which this request is executed, with scheme, server and base path.
     *
     * @return string
     */
    public function appUrl(): string
    {
        if (!isset($this->appUrl)) {
            $appUrl = $this->getSchemeAndHttpHost() . $this->getBaseUrl();
            // Find out the script filename
            $scriptFileName = preg_quote(pathinfo($this->getScriptName(), PATHINFO_BASENAME));
            // Ensure the URL does not contain the script filename
            $this->appUrl = preg_replace("/$scriptFileName$/", "", $appUrl);
        }

        return $this->appUrl;
    }

    /**
     * Check if the current request uses the POST method.
     *
     * @return bool
     */
    public function isPost(): bool
    {
        return $this->isMethod("POST");
    }

    /**
     * Check if the current request uses the GET method.
     *
     * @return bool
     */
    public function isGet(): bool
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
    public function method(): string
    {
        $method = $this->request->get("_method") ?? $this->getMethod();

        return mb_strtoupper($method);
    }

    /**
     * Retrieve a key from the request body, or all values.
     *
     * If `$key` is `null`, all values will be returned in an array.
     *
     * @param string|null $key
     * @param mixed|null $default
     * @return mixed
     */
    public function post(string $key = null, mixed $default = null): mixed
    {
        if (is_null($key)) {
            return $this->request->all();
        }

        return $this->request->get($key, $default);
    }

    /**
     * Check if the request demands a JSON response.
     *
     * The check is performed against the `Accepts` header of the HTTP request
     * and the suffix of the URL.
     *
     * @return bool
     */
    public function isJson(): bool
    {
        return $this->acceptsJson();
    }

    /**
     * Flag the request as accepting JSON.
     *
     * @return void
     */
    public function forceJsonResponse(): void
    {
        $this->acceptsJson = true;
    }

    /**
     * Check if the request expects a JSON response.
     *
     * The check is performed against the `Accepts` header of the HTTP request
     * and the suffix of the URL.
     *
     * @return bool
     */
    public function acceptsJson(): bool
    {
        if (!isset($this->acceptsJson)) {
            $this->acceptsJson = false;

            // Check if the requested URL ends in '.json' or '|json'
            if (preg_match('/[\.|]json$/', $this->getPathInfo())) {
                $this->acceptsJson = true;
            } else {
                // Search for an 'Accept' header containing 'application/json'
                foreach ($this->getAcceptableContentTypes() as $contentType) {
                    if ($contentType === "application/json") {
                        $this->acceptsJson = true;

                        break;
                    }
                }
            }
        }

        return $this->acceptsJson;
    }
}
