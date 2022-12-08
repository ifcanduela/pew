<?php

declare(strict_types=1);

namespace pew\lib;

use pew\request\Request;

use function pew\str;

/**
 * Class to manipulate URLs.
 */
class Url
{
    /** @var ?Request */
    protected static ?Request $staticRequest = null;

    /** @var ?Request */
    private ?Request $request = null;

    /** @var string */
    private string $scheme = "http";

    /** @var string */
    private string $user = "";

    /** @var string */
    private string $password = "";

    /** @var string */
    private string $host = "";

    /** @var int */
    private int $port = 80;

    /** @var array */
    private array $path = [];

    /** @var array */
    private array $query = [];

    /** @var string */
    private string $fragment = "";

    /**
     * Create a URL object.
     *
     * @param string $path
     * @param ?Request $request
     */
    public function __construct(string $path = "", ?Request $request = null)
    {
        $this->setRequest($request ?? static::getStaticRequest());
        $this->init($path);
    }

    /**
     * Initialize the URL components.
     *
     * @param string $path
     * @return void
     */
    public function init(string $path = ""): void
    {
        $parts = parse_url($path);

        $this->setScheme($parts["scheme"] ?? $this->request->getScheme());
        $this->setAuth(
            $parts["user"] ?? $this->request->getUser(),
            $parts["pass"] ?? $this->request->getPassword()
        );
        $this->setHost($parts["host"] ?? $this->request->getHost());
        $this->setPath($parts["path"] ?? $this->request->getPathInfo());
        $this->setPort($parts["port"] ?? $this->request->getPort() ?? 80);
        $this->setFragment($parts["fragment"] ?? "");

        $queryString = $parts["query"] ?? $this->request->getQueryString();
        parse_str($queryString ?? "", $queryParams);
        $this->setQuery($queryParams);
    }

    /**
     * Get an initialized request.
     *
     * @return Request
     */
    private static function getStaticRequest(): Request
    {
        if (!static::$staticRequest) {
            static::$staticRequest = Request::createFromGlobals();
        }

        return static::$staticRequest;
    }

    /**
     * Get the current request URL.
     *
     * @return Url
     */
    public static function here(): Url
    {
        $url = new static();

        $url->setPath($url->request->getPathInfo());
        $url->setQuery($url->request->query->all());

        return $url;
    }

    /**
     * Get the base URL.
     *
     * @return Url
     */
    public static function base(): Url
    {
        $url = new static();
        $url->setPath("/");
        $url->setQuery([]);
        $url->setFragment("");

        return $url;
    }

    /**
     * Get a URL to a path.
     *
     * @param string ...$path
     * @return Url
     */
    public static function to(string ...$path): Url
    {
        $url = new static();

        $path = rtrim("/" . implode("/", array_filter($path)), "/");
        $path = preg_replace('~\/+~', "/", $path);

        $url->setPath($path);
        $url->setQuery([]);

        return $url;
    }

    /**
     * Set the request to be used by the URL object.
     *
     * @param Request $request
     * @return void
     */
    public function setRequest(Request $request): void
    {
        $this->request = $request;
    }

    /**
     * Get the Request.
     *
     * @return Request
     */
    public function getRequest(): Request
    {
        return $this->request;
    }

    /**
     * Set the URL scheme.
     *
     * @param string $scheme
     * @return Url
     */
    public function setScheme(string $scheme): Url
    {
        $this->scheme = (string) str($scheme)->before("://");

        return $this;
    }

    /**
     * Get the URL scheme.
     *
     * This value will always include :// at the end.
     *
     * @param bool $withSeparator
     * @return string
     */
    public function getScheme(bool $withSeparator = true): string
    {
        if (!$this->host) {
            return "";
        }

        $scheme = str($this->scheme ?: "http");

        return (string) ($withSeparator ? $scheme->ensureEnd("://") : $scheme);
    }

    /**
     * Set the user and password in the URL.
     *
     * Pass empty strings to remove the auth info. Pass `null` to skip setting
     * the user or password.
     *
     * @param string|null $user
     * @param string|null $password
     * @return Url
     */
    public function setAuth(string $user = null, string $password = null): Url
    {
        if ($user !== null) {
            $this->user = $user;
        }

        if ($password !== null) {
            $this->password = $password;
        }

        return $this;
    }

    /**
     * Get the authentication information in the URL.
     *
     * The user and password will be separated by a colon if appropriate.
     *
     * @return string
     */
    public function getAuth(): string
    {
        $auth = "";

        if ($this->user) {
            $auth .= $this->user;

            if ($this->password) {
                $auth .= ":" . $this->password;
            }

            $auth .= "@";
        }

        return $auth;
    }

    /**
     * Set the host.
     *
     * @param string $host
     * @return Url
     */
    public function setHost(string $host): Url
    {
        $this->host = $host;

        return $this;
    }

    /**
     * Get the host.
     *
     * @return string
     */
    public function getHost(): string
    {
        return $this->host;
    }

    /**
     * Set the port number.
     *
     * @param int $port
     * @return Url
     */
    public function setPort(int $port): Url
    {
        $this->port = $port;

        return $this;
    }

    /**
     * Get the port number.
     *
     * By default, port 80 is ignored and any other value will have a colon (:) prepended.
     * If $asNumber is true, the actual port number will be returned as a number.
     *
     * @param bool $asNumber Avoid prefixing the port number with a `:`
     * @return string|int
     */
    public function getPort(bool $asNumber = false): int|string
    {
        if ($asNumber) {
            return $this->port;
        }

        if ($this->port && $this->port != 80) {
            return ":" . $this->port;
        }

        return "";
    }

    /**
     * Set the path segments.
     *
     * Multiple string arguments are allowed, with or without slash separators.
     *
     * @param string ...$path
     * @return Url
     */
    public function setPath(string ...$path): Url
    {
        $this->path = [];

        foreach ($path as $value) {
            $segments = array_filter(explode("/", $value));
            $this->path = array_values(array_merge($this->path, $segments));
        }

        return $this;
    }

    /**
     * Append a segment to the end of the path.
     *
     * Multiple string arguments are allowed, with or without slash separators.
     *
     * @param string ...$segment
     * @return Url
     */
    public function addPath(string ...$segment): Url
    {
        return $this->setPath($this->getPath(), ...$segment);
    }

    /**
     * Remove a specific segment from the path.
     *
     * @param string $segment
     * @return Url
     */
    public function removePath(string $segment): Url
    {
        $path = $this->path;

        $pos = array_search($segment, $path, true);

        if ($pos !== false) {
            unset($path[$pos]);
        }

        $this->path = array_values(array_filter($path));

        return $this;
    }

    /**
     * Get the path.
     *
     * @return string
     */
    public function getPath(): string
    {
        return "/" . implode("/", $this->path);
    }

    /**
     * Set a query param.
     *
     * @param string $param
     * @param mixed $value
     * @return Url
     */
    public function setQueryParam(string $param, mixed $value): Url
    {
        $this->query[$param] = $value;

        return $this;
    }

    /**
     * Set the query string.
     *
     * @param string $queryString
     * @return Url
     */
    public function setQueryString(string $queryString): Url
    {
        parse_str($queryString, $this->query);

        return $this;
    }

    /**
     * Set the query params.
     *
     * @param array $query
     * @return Url
     */
    public function setQuery(array $query): Url
    {
        $this->query = $query;

        return $this;
    }

    /**
     * Get the query params.
     *
     * @param ?array $keys
     * @return array
     */
    public function getQuery(array $keys = null): array
    {
        return $keys ? array_intersect_key($this->query, array_flip($keys)) : $this->query;
    }

    /**
     * Get a query param.
     *
     * @param string $param
     * @param mixed|null $default
     * @return mixed
     */
    public function getQueryParam(string $param, mixed $default = null): mixed
    {
        return $this->query[$param] ?? $default;
    }

    /**
     * Merge multiple query params with the current params.
     *
     * @param array $query
     * @return Url
     */
    public function mergeQueryParams(array $query): Url
    {
        $this->query = array_merge($this->query, $query);

        return $this;
    }

    /**
     * Get the query string.
     *
     * The return value will include a '?' if there are query params.
     *
     * @return string
     */
    public function getQueryString(): string
    {
        return $this->query ? "?" . http_build_query($this->query) : "";
    }

    /**
     * Set the fragment.
     *
     * @param string $fragment
     * @return Url
     */
    public function setFragment(string $fragment): Url
    {
        $this->fragment = (string) str($fragment)->after("#");

        return $this;
    }

    /**
     * Get the fragment.
     *
     * @return string
     */
    public function getFragment(): string
    {
        return $this->fragment ? "#" . $this->fragment : "";
    }

    /**
     * Convert the Url object to a URL string.
     *
     * @return string
     */
    public function toString(): string
    {
        return $this->getScheme()
            . $this->getAuth()
            . $this->getHost()
            . $this->getPort()
            . $this->getPath()
            . $this->getQueryString()
            . $this->getFragment();
    }

    /**
     * Convert the Url object to a URL string.
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->toString();
    }
}
