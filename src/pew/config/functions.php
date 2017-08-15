<?php
/**
 * Assorted functions, helpers and shortcuts.
 */

if (!function_exists('pew')) {
    /**
     * Pew config read-only shortcut.
     *
     * @param string $key Key to read
     * @param array $app Setup the app array-like object
     * @return mixed The value for the key
     */
    function pew($key = null, $app = null)
    {
        static $pew;

        if (isset($app)) {
            $pew = $app;
            return;
        }

        return isset($pew[$key]) ? $pew[$key] : null;
    }
}

if (!function_exists('frand')) {
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

if (!function_exists('get_execution_time')) {
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

if (!function_exists('organize_files_array')) {
    /**
     * Organizes the $_FILES array when multiple uploads are enabled.
     *
     * This function will copy the contents of the $_FILES array, changing the format from this:
     *
     * [
     *   'input_1' => [
     *     'name' => [
     *       0 => 'file1.jpg',
     *       1 => 'file2.jpg',
     *       2 => 'file2.jpg',
     *     ],
     *     'type' => [
     *       0 => 'image/jpeg',
     *       1 => 'image/jpeg',
     *       2 => 'image/jpeg',
     *     ]
     *     'tmp_name' => [
     *       0 => '/tmp/phpO2WKrJ'
     *       1 => '/tmp/php2hLO6x'
     *       2 => '/tmp/php)7HjN2'
     *     ]
     *     'error' => [
     *       0 => 0
     *       1 => 0
     *       2 => 0
     *     ]
     *     'size' => [
     *       0 => 12345
     *       1 => 24680
     *       2 => 112358
     *     ]
     *   ]
     * ]
     *
     *  Into this:
     *
     * [
     *   'input_1' => [
     *     0 => [
     *       'name' => 'file1.jpg',
     *       'type' => 'image/jpeg',
     *       'tmp_name' => '/tmp/phpO2WKrJ'
     *       'error' => 0
     *       'size' => 12345
     *     ],
     *     1 => [
     *       'name' => 'file2.jpg',
     *       'type' => 'image/jpeg',
     *       'tmp_name' => '/tmp/php2hLO6x'
     *       'error' => 0
     *       'size' => 24680
     *     ]
     *     '2 => [
     *       'name' => 'file2.jpg',
     *       'type' => 'image/jpeg',
     *       'tmp_name' => '/tmp/php)7HjN2'
     *       'error' => 0
     *       'size' => 112358
     *     ]
     *   ]
     * ]
     *
     * @param array $files_array The list of uploaded files
     * @return array
     */
    function organize_files_array($files_array)
    {
        $organized = [];

        foreach ($files_array as $input_name => $input_value) {
            foreach ($input_value as $field_name => $file_values) {
                foreach ($file_values as $file_number => $field_value) {
                    $organized[$input_name][$file_number][$field_name] = $field_value;
                }
            }
        }

        return $organized;
    }
}

if (!function_exists('array_group')) {
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

if (!function_exists('array_reindex')) {
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

if (!function_exists('array_reap')) {
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
            $filters = explode(':', trim($filter, ':'));
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
                case '#':
                case '#i':
                    # match any number
                    $reap = is_numeric($key);
                    break;
                case '#s':
                case '$':
                    # match any string
                    $reap = is_string($key);
                    break;
                default:
                    # match specific value
                    $reap = ("$key" === "$f");
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

if (!function_exists('array_flatten')) {
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

if (!function_exists('array_path')) {
    /**
     * Get an element from an array using a character-delimited list of indexes.
     *
     * @param array $array
     * @param string $path
     * @param string $separator
     * @return mixed
     */
    function array_path(array $array, string $path, string $separator = '.')
    {
        $source = (array) $array;
        $steps = explode($separator, $path);
        $step = array_shift($steps);

        if (array_key_exists($step, $source)) {
            if (count($steps)) {
                return array_path($source[$step], implode($separator, $steps), $separator);
            } else {
                return $source[$step];
            }
        }

        return null;
    }
}

if (!function_exists('root')) {
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
             $root_path = pew('root_path');
        }

        array_unshift($path, $root_path);

        $path = join(DIRECTORY_SEPARATOR, array_filter($path));
        $path = preg_replace('~[\\\/]+~', DIRECTORY_SEPARATOR, $path);

        return $path;
    }
}

if (!function_exists('url')) {
    /**
     * Gets an absolute URL, having the location of the site as base URL.
     *
     * If the site is hosted at http://www.example.com/pewexample, the call
     *     echo url('www/css/styles.css');
     * will print
     *     http://www.example.com/pewexample/www/css/styles.css.
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

        $path = preg_replace('~\/+~', '/', join('/', array_filter($path)));

        return $base_url . trim($path, '/');
    }
}

if (!function_exists('here')) {
    /**
     * Get the current URI.
     *
     * @return string
     */
    function here()
    {
        static $here;

        if (!$here) {
            $here = pew('path');
        }

        return $here;
    }
}

if (!function_exists('session')) {
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
            $session = pew('session');
        }

        if (is_null($path)) {
            return $session->all();
        }

        return array_path($session->all(), $path, '.') ?? $default;
    }
}

if (!function_exists('flash')) {
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
            $session = pew('session');
        }

        if (null === $key) {
            return $session->getFlashBag()->all();
        } elseif ($session->getFlashBag()->has($key)) {
            return $session->getFlashBag()->get($key);
        }

        return $default;
    }
}
