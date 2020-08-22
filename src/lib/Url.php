<?php declare(strict_types=1);

namespace pew\lib;

use pew\request\Request;
use Stringy\Stringy as S;

/**
 * Class to manipulate URLs.
 */
class Url
{
    /** @var Request */
    public $request;

    /** @var string */
    public $scheme = "http";

    /** @var string|null */
    public $user;

    /** @var string|null */
    public $password;

    /** @var string */
    public $host = "";

    /** @var int */
    public $port = 80;

    /** @var array */
    public $path = [];

    /** @var array */
    public $query = [];

    /** @var string */
    public $fragment = "";

    /**
     * Create a URL object.
     *
     * @param string|Url|Request $request
     */
    public function __construct($request = null)
    {
        if (is_string($request) || ($request instanceof Url)) {
            $request = (string) $request;
            $this->request = Request::create($request);

            if (false !== strpos($request, "#")) {
                $this->fragment = (string) S::create($request)->substr(strpos($request, "#"))->removeLeft("#");
            }
        } elseif ($request instanceof Request) {
            $this->request = $request;
        } else {
            $this->request = Request::createFromGlobals();
        }

        $this->scheme = $this->request->getScheme();
        $this->user = $this->request->getUser();
        $this->password = $this->request->getPassword();
        $this->host = $this->request->getHost();
        $this->port = $this->request->getPort();
        $this->path = array_filter(explode("/", $this->request->getPathInfo()));

        if ($this->request->getQueryString()) {
            parse_str($this->request->getQueryString(), $this->query);
        }
    }

    /**
     * Get the base URL.
     *
     * @return string
     */
    public function base()
    {
        return $this->to();
    }

    /**
     * Get a URL to a path.
     *
     * @param string ...$path
     * @return string
     */
    public function to(string ...$path)
    {
        $path = rtrim("/" . join("/", array_filter($path)), "/");
        $path = preg_replace('~\/+~', "/", $path);

        return $this->request->getSchemeAndHttpHost() . $path;
    }

    /**
     * Set the URL scheme.
     *
     * @param string $scheme
     * @return Url
     */
    public function setScheme(string $scheme)
    {
        $this->scheme = S::create($scheme)->removeRight("://");

        return $this;
    }

    /**
     * Get the URL scheme.
     *
     * This value will always include :// at the end.
     *
     * @return string
     */
    public function getScheme()
    {
        return S::create($this->scheme ?: "http")->ensureRight("://");
    }

    /**
     * Set the user and password in the URL.
     *
     * To remove the auth info, pass `null` as $user.
     *
     * @param string|null $user
     * @param string|null $password
     * @return Url
     */
    public function setAuth(string $user = null, string $password = null)
    {
        $this->user = null;
        $this->password = null;

        if ($user) {
            $this->user = $user;

            if ($password) {
                $this->password = $password;
            }
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
    public function getAuth()
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
    public function setHost(string $host)
    {
        $this->host = $host;

        return $this;
    }

    /**
     * Get the host.
     *
     * @return string
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * Set the port number.
     *
     * @param int $port
     * @return Url
     */
    public function setPort(int $port)
    {
        $this->port = $port;

        return $this;
    }

    /**
     * Get the port number.
     *
     * By default, port 80 is ignored and any other value will have a colon (:) prepended.
     * If $asNumber is true, the actual port number will be returned as an number.
     *
     * @param boolean $asNumber Avoid prefixing the port number with a `:`
     * @return string|int
     */
    public function getPort($asNumber = false)
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
     * Set the pah segments.
     *
     * Multiple string arguments are allowed, with or without slash separators.
     *
     * @param string ...$path
     * @return Url
     */
    public function setPath(string ...$path)
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
    public function addPath(string ...$segment)
    {
        return $this->setPath($this->getPath(), ...$segment);
    }

    /**
     * Remove a specific segment from the path.
     *
     * @param string $segment
     * @return Url
     */
    public function removePath(string $segment)
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
    public function getPath()
    {
        return "/" . join("/", $this->path);
    }

    /**
     * Set a query param.
     *
     * @param string $param
     * @param mixed $value
     * @return Url
     */
    public function setQueryParam(string $param, $value)
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
    public function setQueryString(string $queryString)
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
    public function setQuery(array $query)
    {
        $this->query = $query;

        return $this;
    }

    /**
     * Get the query params.
     *
     * @param array $keys
     * @return array
     */
    public function getQuery(array $keys = null)
    {
        return $keys ? array_intersect_key($this->query, array_flip($keys)) : $this->query;
    }

    /**
     * Get a query param.
     *
     * @param string $param
     * @param mixed $default
     * @return mixed
     */
    public function getQueryParam(string $param, $default = null)
    {
        return $this->query[$param] ?? $default;
    }

    /**
     * Merge multiple query params with the current params.
     *
     * @param array $query
     * @return Url
     */
    public function mergeQueryParams(array $query)
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
    public function getQueryString()
    {
        return $this->query ? "?" . http_build_query($this->query) : "";
    }

    /**
     * Set the fragment.
     *
     * @param string $fragment
     * @return Url
     */
    public function setFragment(string $fragment)
    {
        $this->fragment = (string) S::create($fragment)->substr(strpos($fragment, "#"))->removeLeft("#");

        return $this;
    }

    /**
     * Get the fragment.
     *
     * @return string
     */
    public function getFragment()
    {
        return $this->fragment ? "#" . $this->fragment : "";
    }

    /**
     * Convert the Url object to a Url string.
     *
     * @return string
     */
    public function toString()
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
     * Convert the Url object to a Url string.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->toString();
    }
}
