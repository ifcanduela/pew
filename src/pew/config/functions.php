<?php
/**
 * Assorted functions, helpers and shortcuts.
 */

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

        if (!$app instanceof \pew\App) {
            throw new \RuntimeException("The application has not been initialized");
        }

        return $app->get($key);
    }
}

if (!function_exists("frand")) {
    /**
     * Generates a floating-point pseudo-random number.
     *
     * If only one parameter is provided, it's used as upper boundary. If no parameters are
     * provided, 0.0 and 1.0 are used as boundaries.
     *
     * @param number $from Lower boundary
     * @param number $to Upper boundary
     * @return float A floating point number between 0.0 and 1.0
     */
    function frand($from = null, $to = null)
    {
        $multiplier = 1000000;

        if (!isset($to)) {
            if (isset($from)) {
                $to = $from;
                $from = 0;
            } else {
                $from = 0;
                $to = 1;
            }
        }

        $result = rand($from * $multiplier, $to * $multiplier) / $multiplier;

        return $result;
    }
}

if (!function_exists("get_execution_time")) {
    /**
     * Setups and returns a timer to compute the script execution time.
     *
     * @param bool $partial If true, the value returned is the time passed since the
     *                      last call to the function
     * @return float Seconds elapsed since the first or last call to the function
     * @see http://php.net/manual/en/function.microtime.php
     */
    function get_execution_time($partial = false)
    {
        static $microtime_start = null;
        static $microtime_last = null;

        $microtime = microtime(true);

        if ($microtime_start === null) {
            $microtime_start = $microtime_last = $microtime;
            return 0.0;
        }

        $microtime_partial = $microtime - $microtime_last;
        $microtime_last    = $microtime;

        if (!$partial) {
            return $microtime - $microtime_start;
        } else {
            return $microtime_partial;
        }
    }
}

if (!function_exists("one_of")) {
    /**
     * Pick a random element from an array.
     *
     * Returns null when the array is empty.
     *
     * @param array $list
     * @return mixed|null
     */
    function one_of(array $list)
    {
        $keys = array_keys($list);

        if (!count($keys)) {
            return null;
        }

        $keyNumber = random_int(0, count($keys) - 1);
        $key = $keys[$keyNumber];

        return $list[$key];
    }
}

if (!function_exists("array_group")) {
    /**
     * Group elements of an array by the value of one of their keys.
     *
     * @param array $array Source array
     * @param mixed $field Grouping field
     * @return array The grouped array
     */
    function array_group(array $array, $field)
    {
        $result = [];

        foreach ($array as $entry) {
            if (array_key_exists($field, $entry)) {
                $key = $entry[$field];
                $result[$key][] = $entry;
            }
        };

        return $result;
    }
}

if (!function_exists("array_reindex")) {
    /**
     * Builds a key/value array using a value from an array as index.
     *
     * The result is an array with keys corresponding to values from the
     * source array's elements. If the $value_index parameter is null the whole
     * element is assigned to the key, but if a key is provided only the value
     * of that key is assigned to the $key_index.
     *
     * @param array $array An array with array/object elements
     * @param int|string $key_name Element key to use as key
     * @param int|string $value_name Element key to use as value
     * @return array
     */
    function array_reindex(array $array, $key_name, $value_name = null)
    {
        $result = array();

        foreach ($array as $key => $value) {
            if (is_object($value)) {
                # normalize to array
                $value = (array) $value;
            }

            if (is_array($value) && array_key_exists($key_name, $value)) {
                $key_name_value = $value[$key_name];

                if (is_null($value_name)) {
                    # if $value_name is null the while element is used
                    $value_name_value = $value;
                } elseif (array_key_exists($value_name, $value)) {
                    # if $value_name corresponds to an existing key its value is used
                    $value_name_value =  $value[$value_name];
                } else {
                    # the value is null in case no value can be used
                    $value_name_value =  null;
                }

                $result[$key_name_value] = $value_name_value;
            }
        }

        return $result;
    }
}

if (!function_exists("array_reap")) {
    /**
     * Isolate values from an array according to a pattern.
     *
     * This function accepts an array and a list of filtering atoms (in string or
     * array form) and uses the atoms to progressively scan the the keys in
     * successive dimensions of the array, discarding the indexes that do not
     * conform to the atom provided for the dimension.
     *
     * Atom strings are of the form '#:$:literal:0'.
     *
     * The available atom types are:
     * * # or #i: matches any integer index
     * * $ or #s: matches any string index
     * * any other atom is taken as a literal value and will match indexes that
     *   equal the atom; this matching is not strict: atom '1' will match index 1.
     *
     * @param array|object $data The array to be filtered
     * @param mixed $filter A string or array with the filtering atoms
     * @return array The array with the matching elements
     */
    function array_reap($data, $filter)
    {
        if (is_string($filter)) {
            # if $filter is a string, divide and acquire filter atoms
            $filters = explode(":", trim($filter, ":"));
        } elseif (is_array($filter)) {
            # if $filter was already an array, go ahead
            $filters = $filter;
        } else {
            # any other possibility is an error
            return null;
        }

        # if there are no remaining filters, $data complies with the rules
        if (count($filters) == 0) {
            return $data;
        }

        # if $data is an object, get its properties
        if (is_object($data)) {
            $data = get_object_vars($data);
        }

        # by this point, $data must be an array
        if (!is_array($data)) {
            return null;
        }

        # get the current filter
        $f = array_shift($filters);

        # scan the data array
        foreach ($data as $key => $value) {
            # assume recursive calls won't be necessary
            $reap = false;

            switch ($f) {
                case "#":
                case "#i":
                    # match any number
                    $reap = is_numeric($key);
                    break;
                case "#s":
                case "$":
                    # match any string
                    $reap = is_string($key);
                    break;
                default:
                    # match specific value
                    $reap = $key == $f;
                    break;
            }

            if ($reap) {
                $function_name = __FUNCTION__;
                # if the value matched, call recursively to reap()
                $data[$key] = $function_name($data[$key], $filters);
                # if the result is empty, discard it
                if (is_null($data[$key]) || (is_array($data[$key]) && count($data[$key]) === 0)) {
                    unset($data[$key]);
                }
            } else {
                # if the key didn't match, discard it
                unset($data[$key]);
            }
        }

        return $data;
    }
}

if (!function_exists("array_flatten")) {
    /**
     * Collect all non-array values of a multi-dimensional array.
     *
     * The flatten() function collects string, numeric an boolean values from an
     * array and returns an indexed array with those values. Keys are not kept.
     * This function is useful to simplify the results from array_reap().
     *
     * @param array $data The array to be flattened
     * @return array The array with the scalar values
     */
    function array_flatten($data)
    {
        $it = new \RecursiveIteratorIterator(new \RecursiveArrayIterator($data));

        return iterator_to_array($it, false);
    }
}

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

if (!function_exists("root")) {
    /**
     * A quick way to get the filesystem root directory or any file below it.
     *
     * If the framework files reside in C:\htdocs\pewexample, this call
     *     echo root('app\libs\my_lib.php');
     * will print
     *     C:\htdocs\pewexample\app\libs\my_lib.php
     *
     * @param string|string[] ...$path A path to include in the output
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
     * @param string|string[] ...$path One or more path segments
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
        $query = count($params) ? array_merge(...$params) : [];
        $segments = array_filter($path, function ($segment) {
            return is_scalar($segment) || method_exists($segment, "__tostring");
        });
        $path = preg_replace('~\/+~', '/', join('/', $segments));

        $query_string = http_build_query($query);

        return $base_url . trim($path, '/') . ($query_string ? "?{$query_string}" : "");
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
