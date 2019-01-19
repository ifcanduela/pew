<?php
/**
 * Assorted functions, helpers and shortcuts.
 */

if (!function_exists("array_path")) {
    /**
     * Get an element from an array using a character-delimited list of indexes.
     *
     * @param array $array
     * @param string $path
     * @param string $separator
     * @return mixed
     */
    function array_path(array $array, string $path, string $separator = ".")
    {
        $steps = explode($separator, $path);
        $step = array_shift($steps);

        if (array_key_exists($step, $array)) {
            if (count($steps)) {
                return array_path($array[$step], join($separator, $steps), $separator);
            } else {
                return $array[$step];
            }
        }

        return null;
    }
}

if (!function_exists("flash")) {
    /**
     * Helper for flash data.
     *
     * @param string $key Flash data key to read
     * @param mixed $default Value to return in case the keys don't exist
     * @return mixed Value of the key
     */
    function flash($key = null, $default = null)
    {
        static $session;

        if (!$session) {
            $session = pew("session");
        }

        if (null === $key) {
            return $session->getFlashBag()->all();
        } elseif ($session->getFlashBag()->has($key)) {
            return $session->getFlashBag()->get($key);
        }

        return $default;
    }
}

if (!function_exists("here")) {
    /**
     * Get the current URI.
     *
     * @return string
     */
    function here()
    {
        static $here;

        if (!$here) {
            $here = pew("path");
        }

        return $here;
    }
}

if (!function_exists("pew")) {
    /**
     * Pew config read-only shortcut.
     *
     * @param string $key Key to read
     * @return mixed The value for the key
     */
    function pew(string $key)
    {
        $app = \pew\App::instance();

        if (!$app) {
            throw new \RuntimeException("The application has not been initialized");
        }

        return $app->get($key);
    }
}

if (!function_exists("root")) {
    /**
     * A quick way to get the filesystem root directory or any file below it.
     *
     * If the framework files reside in C:\htdocs\pewexample, this call
     *     `echo root('app\libs\my_lib.php');`
     * will print
     *     `C:\htdocs\pewexample\app\libs\my_lib.php`
     *
     * This function does not handle `.` and `..` in any special way and is not
     * concerned with whether the path exists or not.
     *
     * @param string ...$path A path to include in the output
     * @return string The resulting path
     */
    function root(...$path)
    {
        static $root_path;

        if (!isset($root_path)) {
            $root_path = pew("root_path");
        }

        array_unshift($path, $root_path);

        $path = join(DIRECTORY_SEPARATOR, array_filter($path));
        $path = preg_replace('~[\\\/]+~', DIRECTORY_SEPARATOR, $path);

        return $path;
    }
}

if (!function_exists("session")) {
    /**
     * Helper for session values.
     *
     * Accepts a period-delimited string of sub-indices.
     *
     * @param string $path Keys to access
     * @param mixed $default Value to return in case the keys don't exist
     * @return mixed Value of the key
     */
    function session($path = null, $default = null)
    {
        static $session;

        if (!$session) {
            $session = pew("session");
        }

        if (is_null($path)) {
            return $session->all();
        }

        return array_path($session->all(), $path, ".") ?? $default;
    }
}

if (!function_exists("url")) {
    /**
     * Gets an absolute URL, having the location of the site as base URL.
     *
     * If the site is hosted at http://www.example.com/pewexample, the call
     *     echo url('www/css/styles.css');
     * will print
     *     http://www.example.com/pewexample/www/css/styles.css.
     *
     * Pass associative arrays to add query parameters to the URL.
     *
     * @param mixed ...$path One or more path segments
     * @return string The resulting url
     */
    function url(...$path)
    {
        static $base_url;

        if (!isset($base_url)) {
            $base_url = pew('request')->appUrl();
            $base_url = rtrim($base_url, '/') . '/';
        }

        $params = array_filter($path, "is_array");
        $segments = array_filter($path, function ($segment) {
            return is_scalar($segment) || method_exists($segment, "__toString");
        });
        $path = preg_replace('~\/+~', '/', join('/', $segments));

        $url = $base_url . ltrim($path, '/');

        if ($params) {
            $query = count($params) ? array_merge(...$params) : [];
            $query_string = http_build_query($query);
            $url .= $query_string;
        }

        return $url;
    }
}
