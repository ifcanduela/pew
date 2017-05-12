<?php
/**
 * Assorted functions, helpers and shortcuts.
 */

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
    } else {
        return isset($pew[$key]) ? $pew[$key] : null;
    }
}

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

/**
 * Applies a text sanitization filter to a string.
 *
 * This function calls filter_var with FILTER_SANITIZE_MAGIC_QUOTES if the
 * parameter is a string. If it's not a string, it return the parameter
 * unmodified.
 *
 * @param string $string The string to sanitize
 * @return string The sanitized string
 * @see http://php.net/manual/en/function.filter-var.php
 */
function sanitize($string)
{
    if (is_string($string)) {
        return filter_var($string, FILTER_SANITIZE_MAGIC_QUOTES);
    } else {
        return $string;
    }
}

/**
 * Cleans a string using basic PHP functions.
 *
 * @param string $evil_string A string to clean up
 * @return string The resulting string
 * @see http://php.net/manual/en/function.strip_tags.php
 * @see http://php.net/manual/en/function.stripslashes.php
 * @see http://php.net/manual/en/function.htmlentities.php
 * @see http://php.net/manual/en/function.filter_var.php
 */
function pew_clean_string($evil_string)
{
    $no_tags = strip_tags($evil_string);
    $no_slashes = stripslashes($no_tags);
    $no_quotes = filter_var($no_slashes, FILTER_SANITIZE_MAGIC_QUOTES);
    $clean_string = htmlentities($no_quotes, ENT_QUOTES, 'UTF-8');

    return $clean_string;
}

/**
 * Returns a value from an array (discarding the array itself).
 *
 * This function is useful as a workaround for the unavailability of unnamed array
 * dereferencing in PHP 5.3:
 *
 *   $my_desired_value = func_that_returns_array()[$index];
 *
 * That would not work. You just do this:
 *
 *   $my_desired_value = array_index(func_that_returns_array(), $index);
 *
 * @param array $array The array data
 * @param mixed $index The integer or string index to retrieve
 * @param bool $strict Whether or not to throw an InvalidArgumenException
 *                     if the index does not exist
 * @return mixed|null
 * @throws InvalidArgumentException When $index is not found and $strict is true
 */
function deref(array $array, $index, $strict = false)
{
    if (!array_key_exists($index, $array)) {
        if ($strict) {
            throw new InvalidArgumentException("Array key does not exist: [$index]");
        } else {
            return null;
        }
    }

    return $array[$index];
}

/**
 * Group elements of an array by the value of one of their keys.
 *
 * @param array $array Source array
 * @param mixed $field Grouping field
 * @return array The grouped array
 */
function array_group(array $array, $field): array
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
    # store results here
    $flat = [];

    # loop through the $data array
    foreach ($data as $key => $value) {
        if (is_array($value)) {
            $function_name = __FUNCTION__;
            # if value's an array, merge current elements and value's flattened result
            $flat = array_merge($flat, $function_name($value));
        } else {
            # if $value is a scalar value, append it to the results
            $flat[] = $value;
        }
    }

    return $flat;
}


/**
 * Convert an array into an XML structure.
 *
 * @param array $data
 * @param \SimpleXMLElement|string $xml
 * @param string $root_name
 * @see http://stackoverflow.com/a/5965940/1007072
 */
function array_to_xml(array $data, &$xml, $root_name = 'root')
{
    if (is_string($xml)) {
        $xml = new \SimpleXMLElement('<' . $root_name . '></' . $root_name . '>');
    }

    foreach ($data as $k => $v) {
        if (is_array($v)) {
            if (is_numeric($k)) {
                $child = $xml->addChild($root_name);
                array_to_xml($v, $child, $root_name);
            } else {
                $child = $xml->addChild($k);
                array_to_xml($v, $child, $k);
            }
        } else {
            $xml->addAttribute($k, $v);
        }
    }
}
/**
 * Makes sure the folders in a slash-delimited path exist.
 *
 * This is useful when mkdir() does not support the $recursive parameter,
 * although the framework is developed with PHP 5.3 in mind.
 *
 * @param string $path A single or multi-folder path
 * @return bool Returns false on error, true otherwise
 */
function check_dirs($path)
{
    # Normalize the directory separators
    $path = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);

    if (file_exists($path)) {
        return true;
    }

    # Obtain an array with from the folder string and filter empty elements
    $dirs = explode(DIRECTORY_SEPARATOR, $path);
    $dirs = array_filter($dirs);

    # If there are no folders, leave it be
    if (count($dirs) == 0) {
        return false;
    }

    $cp = '';

    while (count($dirs) > 0) {
        # Aggregate the folders to form the current path
        $cp .= array_shift($dirs) . DIRECTORY_SEPARATOR;

        if (!is_dir($cp)) {
            # If the folder does not exist, attempt to create it
            if (!mkdir($cp)) {
                # Return false on error
                return false;
            }
        }
    }

    return true;
}

/**
 * Utility function to create a URL-friendly string by cleaning ugly characters.
 *
 * Slugifying a string will remove any non-alpha-numeric characters (leaving
 * only A to Z, a to z, 0 to 9 and -), replace whitespace with dashes and then
 * normalize the string to only 1 consecutive dash.
 *
 * @param string $str The string to slugify
 * @return string The slug
 */
function slugify($str)
{
    # convert special characters to English ones
    $str = iconv('UTF-8', 'ASCII//TRANSLIT', $str);

    # strip the string from URL-unfriendly characters
    $str = preg_replace('/[^\w\d +*._\-]/', '', $str);

    # transform spaces into dashes and convert to lowercase
    $str = strtolower(str_replace([' ', '+', '*', '.'], '-', trim($str)));

    # reduce consecutive dashes to a single dash
    $str = preg_replace('/-+/', '-', $str);

    return $str;
}

function transliterate($str)
{
    $substitutions = [
        'á' => 'a', 'Á' => 'A', 'à' => 'a', 'À' => 'A', 'ă' => 'a', 'Ă' => 'A', 'â' => 'a', 'Â' => 'A', 'å' => 'a', 'Å' => 'A', 'ã' => 'a', 'Ã' => 'A', 'ą' => 'a', 'Ą' => 'A', 'ā' => 'a', 'Ā' => 'A', 'ä' => 'ae', 'Ä' => 'AE', 'æ' => 'ae', 'Æ' => 'AE',
        'ḃ' => 'b', 'Ḃ' => 'B',
        'ć' => 'c', 'Ć' => 'C', 'ĉ' => 'c', 'Ĉ' => 'C', 'č' => 'c', 'Č' => 'C', 'ċ' => 'c', 'Ċ' => 'C', 'ç' => 'c', 'Ç' => 'C',
        'ď' => 'd', 'Ď' => 'D', 'ḋ' => 'd', 'Ḋ' => 'D', 'đ' => 'd', 'Đ' => 'D', 'ð' => 'dh', 'Ð' => 'Dh',
        'é' => 'e', 'É' => 'E', 'è' => 'e', 'È' => 'E', 'ĕ' => 'e', 'Ĕ' => 'E', 'ê' => 'e', 'Ê' => 'E', 'ě' => 'e', 'Ě' => 'E', 'ë' => 'e', 'Ë' => 'E', 'ė' => 'e', 'Ė' => 'E', 'ę' => 'e', 'Ę' => 'E', 'ē' => 'e', 'Ē' => 'E',
        'ḟ' => 'f', 'Ḟ' => 'F', 'ƒ' => 'f', 'Ƒ' => 'F',
        'ğ' => 'g', 'Ğ' => 'G', 'ĝ' => 'g', 'Ĝ' => 'G', 'ġ' => 'g', 'Ġ' => 'G', 'ģ' => 'g', 'Ģ' => 'G',
        'ĥ' => 'h', 'Ĥ' => 'H', 'ħ' => 'h', 'Ħ' => 'H',
        'í' => 'i', 'Í' => 'I', 'ì' => 'i', 'Ì' => 'I', 'î' => 'i', 'Î' => 'I', 'ï' => 'i', 'Ï' => 'I', 'ĩ' => 'i', 'Ĩ' => 'I', 'į' => 'i', 'Į' => 'I', 'ī' => 'i', 'Ī' => 'I',
        'ĵ' => 'j', 'Ĵ' => 'J',
        'ķ' => 'k', 'Ķ' => 'K',
        'ĺ' => 'l', 'Ĺ' => 'L', 'ľ' => 'l', 'Ľ' => 'L', 'ļ' => 'l', 'Ļ' => 'L', 'ł' => 'l', 'Ł' => 'L',
        'ṁ' => 'm', 'Ṁ' => 'M',
        'ń' => 'n', 'Ń' => 'N', 'ň' => 'n', 'Ň' => 'N', 'ñ' => 'n', 'Ñ' => 'N', 'ņ' => 'n', 'Ņ' => 'N',
        'ó' => 'o', 'Ó' => 'O', 'ò' => 'o', 'Ò' => 'O', 'ô' => 'o', 'Ô' => 'O', 'ő' => 'o', 'Ő' => 'O', 'õ' => 'o', 'Õ' => 'O', 'ø' => 'oe', 'Ø' => 'OE', 'ō' => 'o', 'Ō' => 'O', 'ơ' => 'o', 'Ơ' => 'O', 'ö' => 'oe', 'Ö' => 'OE',
        'ṗ' => 'p', 'Ṗ' => 'P',
        'ŕ' => 'r', 'Ŕ' => 'R', 'ř' => 'r', 'Ř' => 'R', 'ŗ' => 'r', 'Ŗ' => 'R',
        'ś' => 's', 'Ś' => 'S', 'ŝ' => 's', 'Ŝ' => 'S', 'š' => 's', 'Š' => 'S', 'ṡ' => 's', 'Ṡ' => 'S', 'ş' => 's', 'Ş' => 'S', 'ș' => 's', 'Ș' => 'S', 'ß' => 'SS',
        'ť' => 't', 'Ť' => 'T', 'ṫ' => 't', 'Ṫ' => 'T', 'ţ' => 't', 'Ţ' => 'T', 'ț' => 't', 'Ț' => 'T', 'ŧ' => 't', 'Ŧ' => 'T',
        'ú' => 'u', 'Ú' => 'U', 'ù' => 'u', 'Ù' => 'U', 'ŭ' => 'u', 'Ŭ' => 'U', 'û' => 'u', 'Û' => 'U', 'ů' => 'u', 'Ů' => 'U', 'ű' => 'u', 'Ű' => 'U', 'ũ' => 'u', 'Ũ' => 'U', 'ų' => 'u', 'Ų' => 'U', 'ū' => 'u', 'Ū' => 'U', 'ư' => 'u', 'Ư' => 'U', 'ü' => 'ue', 'Ü' => 'UE',
        'ẃ' => 'w', 'Ẃ' => 'W', 'ẁ' => 'w', 'Ẁ' => 'W', 'ŵ' => 'w', 'Ŵ' => 'W', 'ẅ' => 'w', 'Ẅ' => 'W',
        'ý' => 'y', 'Ý' => 'Y', 'ỳ' => 'y', 'Ỳ' => 'Y', 'ŷ' => 'y', 'Ŷ' => 'Y', 'ÿ' => 'y', 'Ÿ' => 'Y',
        'ź' => 'z', 'Ź' => 'Z', 'ž' => 'z', 'Ž' => 'Z', 'ż' => 'z', 'Ż' => 'Z',
        'þ' => 'th', 'Þ' => 'Th', 'µ' => 'u', 'а' => 'a', 'А' => 'a', 'б' => 'b', 'Б' => 'b', 'в' => 'v', 'В' => 'v', 'г' => 'g', 'Г' => 'g', 'д' => 'd', 'Д' => 'd', 'е' => 'e', 'Е' => 'e', 'ё' => 'e', 'Ё' => 'e', 'ж' => 'zh', 'Ж' => 'zh', 'з' => 'z', 'З' => 'z', 'и' => 'i', 'И' => 'i', 'й' => 'j', 'Й' => 'j', 'к' => 'k', 'К' => 'k', 'л' => 'l', 'Л' => 'l', 'м' => 'm', 'М' => 'm', 'н' => 'n', 'Н' => 'n', 'о' => 'o', 'О' => 'o', 'п' => 'p', 'П' => 'p', 'р' => 'r', 'Р' => 'r', 'с' => 's', 'С' => 's', 'т' => 't', 'Т' => 't', 'у' => 'u', 'У' => 'u', 'ф' => 'f', 'Ф' => 'f', 'х' => 'h', 'Х' => 'h', 'ц' => 'c', 'Ц' => 'c', 'ч' => 'ch', 'Ч' => 'ch', 'ш' => 'sh', 'Ш' => 'sh', 'щ' => 'sch', 'Щ' => 'sch', 'ъ' => '', 'Ъ' => '', 'ы' => 'y', 'Ы' => 'y', 'ь' => '', 'Ь' => '', 'э' => 'e', 'Э' => 'e', 'ю' => 'ju', 'Ю' => 'ju', 'я' => 'ja', 'Я' => 'ja'
    ];

    $str = str_replace(array_keys($substitutions), array_values($substitutions), $str);

    return $str;
}

/**
 * Utility function to convert dashes and spaces to underscores.
 *
 * Behaves in the same way as str_replace. By default, replaces ' ' (space)
 * and '-' (minus sign) with '_' (underscore).
 *
 * @param string $str The string to transform
 * @param array|string $chars Array of substrings to insert
 * @param string $replacements List of equivalences for the characters to replace
 * @return string The modified string
 */
function to_underscores($str, $chars = [' ', '-'], $replacements = '_')
{
    return str_replace($chars, $replacements, $str);
}

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
function url(...$path): string
{
    static $base_url;

    if (!isset($base_url)) {
        $base_url = pew('request')->appUrl();
        $base_url = rtrim($base_url, '/') . '/';
    }

    $path = preg_replace('~\/+~', '/', join('/', array_filter($path)));

    return $base_url . trim($path, '/');
}

/**
 * Gets an absolute URL, having the location of the assets folder as base URL.
 *
 * If the site is hosted at http://www.example.com/pewexample and the
 * "www_url" app config setting is url('www'), the call
 *     echo www('css/styles.css');
 * will print
 *     http://www.example.com/pewexample/www/css/styles.css.
 *
 * @param string|string[] $path A string to print after the server, path and www location.
 * @return string The resulting url
 */
function www(...$path)
{
    return url(...$path);
}

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

/**
 * Gets the currently logged-in user data, if any.
 *
 * Commonly used in this way: echo user()->username;
 *
 * @return stdClass|boolean An object with the user info if there's a user
 *                          logged in, boolean false otherwise.
 */
function user()
{
    return session('user') ?? false;
}

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

    return $session[$path] ?? $default;
}

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
