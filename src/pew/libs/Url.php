<?php

namespace pew\libs;

use Stringy\Stringy as S;
use pew\request\Request;

/**
 * Class to manipulate URLs.
 */
class Url
{
    /** @var Request */
    public $request;
    
    /** @var array */
    public $routes = [];
    
    /** @var array */
    public $namedRoutes = [];

    /** @var string */
    public $scheme = 'http';
    
    /** @var string */
    public $user = '';
    
    /** @var string */
    public $password = '';
    
    /** @var string */
    public $host = '';
    
    /** @var int */
    public $port = 80;
    
    /** @var string */
    public $path = [];
    
    /** @var array */
    public $query = [];
    
    /** @var string */
    public $fragment = '';

    /**
     * Create a URL object.
     *
     * @param string|Url|Request $request
     * @param array $routes
     */
    public function __construct($request = null, array $routes = [])
    {
        if (is_string($request) || ($request instanceof Url)) {
            $request = (string) $request;
            $this->request = Request::create($request);

            if (false !== strpos($request, '#')) {
                $this->fragment = (string) S::create($request)->substr(strpos($request, '#'))->removeLeft('#');
            }
        } elseif ($request instanceof Request) {
            $this->request = $request;
        } else {
            $this->request = Request::createFromGlobals();
        }

        $this->routes = $routes;

        $this->scheme = $this->request->getScheme();
        $this->user = $this->request->getUser();
        $this->password = $this->request->getPassword();
        $this->host = $this->request->getHost();
        $this->port = $this->request->getPort();
        $this->path = array_filter(explode('/', $this->request->getPathInfo()));
        parse_str($this->request->getQueryString(), $this->query);
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
     * @param string|string[] ...$path
     * @return string
     */
    public function to(string ...$path)
    {
        $path = rtrim('/' . join('/', $path), '/');
        $path = preg_replace('~\/+~', '/', $path);

        return $this->request->getSchemeAndHttpHost() . $path;
    }

    /**
     * Create a URL to a named route.
     *
     * @param string $routeName
     * @param array $params
     * @return string
     * @throws \Exception
     */
    public function toRoute(string $routeName, array $params = [])
    {
        # arrange all the named routes
        if (!$this->namedRoutes) {
            foreach ($this->routes as $route) {
                if (isset($route['name']) && $route['name']) {
                    $this->namedRoutes[$route['name']] = $route;
                }
            }
        }

        if (isset($this->namedRoutes[$routeName])) {
            $route = $this->namedRoutes[$routeName];
            $path = $route['path'];

            # merge the defaults with the passed params
            if (isset($route['defaults'])) {
                $params = array_merge($route['defaults'], $params);
            }

            # replace all available params
            foreach ($params as $key => $value) {
                $path = str_replace('{' . $key . '}', $value, $path);
            }

            # clear all remaining optional parameters
            while (strpos($path, '}]') !== false) {
                // try to remove [\{var}]
                $path = preg_replace('~(\[\/\{[^}]+\}\])~', '', $path);
            }

            # check if there are any remaining mandatory parameters
            if (strpos($path, '{')) {
                throw new \Exception("Not all required placeholders could be filled for {$route['path']}");
            }

            # clean the optional parameter markers
            $path = str_replace(['[', ']'], '', $path);

            return $this->to($path);
        }

        throw new \RuntimeException("Route name not found: {$routeName}");
    }

    /**
     * Set the URL scheme.
     *
     * @param string $scheme
     * @return Url
     */
    public function setScheme(string $scheme)
    {
        $url = clone $this;
        $url->scheme = S::create($scheme)->removeRight('://');

        return $url;
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
        return S::create($this->scheme ?: 'http')->ensureRight('://');
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
        $url = clone $this;

        $url->user = null;
        $url->password = null;

        if ($user) {
            $url->user = $user;

            if ($password) {
                $url->password = $password;
            }
        }

        return $url;
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
        $auth = '';

        if ($this->user) {
            $auth .= $this->user;

            if ($this->password) {
                $auth .= ':' . $this->password;
            }

            $auth .= '@';
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
        $url = clone $this;
        $url->host = $host;

        return $url;
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
        $url = clone $this;
        $url->port = $port;

        return $url;
    }

    /**
     * Get the port number.
     *
     * By default, port 80 is ignored and any other value will have a colon (:) prepended.
     * If $asNumber is true, the actual port number will be returned as an number.
     *
     * @param boolean $asNumber Avoid prepending a : to the port number.
     * @return string
     */
    public function getPort($asNumber = false)
    {
        if ($asNumber) {
            return $this->port;
        }

        if ($this->port && $this->port != 80) {
            return ':' . $this->port;
        }

        return '';
    }

    /**
     * Set the pah segments.
     *
     * Multiple string arguments are allowed, with or without slash separators.
     *
     * @param string|string[] ...$path
     * @return Url
     */
    public function setPath(string ...$path)
    {
        $url = clone $this;

        $url->path = [];

        foreach ($path as $value) {
            $url->path = array_values(array_merge(
                $url->path,
                array_filter(explode('/', $value . '/'))
            ));
        }

        return $url;
    }

    /**
     * Append a segment to the end of the path.
     *
     * Multiple string arguments are allowed, with or without slash separators.
     *
     * @param string|string[] ...$segment
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
        $url = clone $this;
        $path = $url->path;

        $pos = array_search($segment, $path, true);

        if ($pos !== false) {
            unset($path[$pos]);
        }

        $url->path = array_values(array_filter($path));

        return $url;
    }

    /**
     * Get the path.
     *
     * @return string
     */
    public function getPath()
    {
        return '/' . join('/', $this->path);
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
        $url = clone $this;
        $url->query[$param] = $value;

        return $url;
    }

    /**
     * Set the query string.
     *
     * @param string $queryString
     * @return Url
     */
    public function setQueryString(string $queryString)
    {
        $url = clone $this;
        parse_str($queryString, $url->query);

        return $url;
    }

    /**
     * Set the query params.
     *
     * @param array $query
     * @return Url
     */
    public function setQuery(array $query)
    {
        $url = clone $this;
        $url->query = $query;

        return $url;
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
     * Get the query string.
     *
     * The return value will include a '?' if there are query params.
     *
     * @return string
     */
    public function getQueryString()
    {
        return $this->query ? '?' . http_build_query($this->query) : '';
    }

    /**
     * Set the fragment.
     *
     * @param string $fragment
     * @return Url
     */
    public function setFragment(string $fragment)
    {
        $url = clone $this;
        $url->fragment = (string) S::create($fragment)->substr(strpos($fragment, '#'))->removeLeft('#');

        return $url;
    }

    /**
     * Get the fragment.
     *
     * @return string
     */
    public function getFragment()
    {
        return $this->fragment ? '#' . $this->fragment : '';
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
