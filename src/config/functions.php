<?php

declare(strict_types=1);

/**
 * Assorted functions, helpers and shortcuts.
 */

namespace pew;

use InvalidArgumentException;
use pew\lib\Url;
use Symfony\Component\String\AbstractString;
use Symfony\Component\String\Slugger\AsciiSlugger;

use function Symfony\Component\String\s;

/**
 * Find a key in an array using a callback.
 *
 * @param array $array
 * @param callable $callback
 * @return int|string|null
 */
function array_find_key(array $array, callable $callback): int|string|null
{
    foreach ($array as $key => $value) {
        $match = $callback($value, $key);

        if ($match) {
            return $key;
        }
    }

    return null;
}

/**
 * Find a value in an array using a callback.
 *
 * @param array $array
 * @param callable $callback
 * @return mixed
 */
function array_find_value(array $array, callable $callback): mixed
{
    foreach ($array as $key => $value) {
        $match = $callback($value, $key);

        if ($match) {
            return $value;
        }
    }

    return null;
}

/**
 * Get an element from an array using a character-delimited list of indexes.
 *
 * @param object|array $array
 * @param string $path
 * @param string $separator
 * @return mixed
 */
function array_path(object|array $array, string $path, string $separator = "."): mixed
{
    $steps = explode($separator, $path);
    $step = array_shift($steps);

    if (is_object($array)) {
        $array = get_object_vars($array);
    }

    if (array_key_exists($step, $array)) {
        if (count($steps)) {
            return array_path($array[$step], join($separator, $steps), $separator);
        }

        return $array[$step];
    }

    return null;
}

/**
 * Read and decode JSON from a file.
 *
 * @see https://www.php.net/json_decode
 * @see https://www.php.net/file_get_contents
 *
 * @param string $filename
 * @param bool $assoc
 * @param int $depth
 * @param int $options
 * @return mixed
 */
function file_get_json(string $filename, bool $assoc = true, int $depth = 512, int $options = 0): mixed
{
    $json = file_get_contents($filename);
    $data = json_decode($json, $assoc, $depth, $options);

    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new InvalidArgumentException("JSON decoding error: " . json_last_error_msg());
    }

    return $data;
}

/**
 * Encode and write JSON to a file.
 *
 * @see https://www.php.net/json_encode
 * @see https://www.php.net/file_put_contents
 *
 * @param string $filename
 * @param mixed $data
 * @param int $options
 * @param int $depth
 * @return void
 */
function file_put_json(string $filename, mixed $data, int $options = 0, int $depth = 512): void
{
    $json = json_encode($data, $options, $depth);

    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new InvalidArgumentException("JSON encoding error: " . json_last_error_msg());
    }

    file_put_contents($filename, $json);
}

/**
 * Helper for flash data.
 *
 * @param string|null $key Flash data key to read
 * @param mixed|null $default Value to return in case the keys don't exist
 * @return mixed Value of the key
 */
function flash(string $key = null, mixed $default = null): mixed
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

/**
 * Get the current URI.
 *
 * @return string
 */
function here(): string
{
    static $here;

    if (!$here) {
        $query = pew("request")->query->all();

        $here = url(pew("path"), $query ?: "");
    }

    return $here;
}

/**
 * Check if a path matches a named route.
 *
 * @param string $routeName The route name
 * @param string|null $path The path to check
 * @param string|null $method The request method
 * @return bool
 */
function is_route(string $routeName, string $path = null, string $method = null): bool
{
    static $router;
    static $request;

    if (!$router) {
        $router = pew("router");
    }

    if (!$request) {
        $request = pew("request");
    }

    return $router->isRoute($routeName, $path ?? $request->getPathInfo(), $method ?? $request->getMethod());
}

/**
 * Pew config read-only shortcut.
 *
 * @param string $key Key to read
 * @return mixed The value for the key
 */
function pew(string $key): mixed
{
    $app = App::instance();

    return $app->get($key);
}

/**
 * A quick way to get the filesystem root directory or any file below it.
 *
 * If the framework files reside in C:\htdocs\pew-example, this call
 *     `echo root('app\libs\my_lib.php');`
 * will print
 *     `C:\htdocs\pew-example\app\libs\my_lib.php`
 *
 * This function does not handle `.` and `..` in any special way and is not
 * concerned with whether the path exists or not.
 *
 * @param string ...$path A path to include in the output
 * @return string The resulting path
 */
function root(...$path): string
{
    static $root_path;

    if (!isset($root_path)) {
        $root_path = pew("root_path");
    }

    array_unshift($path, $root_path);

    $path = join(DIRECTORY_SEPARATOR, array_filter($path));

    return preg_replace('~[\\\/]+~', DIRECTORY_SEPARATOR, $path);
}

/**
 * Create an absolute URL from its route name.
 *
 * Pass as many arguments as the route needs, in order of appearance
 * in the URL.
 *
 * @param string $routeName The route name
 * @param string ...$params Route parameters
 * @return Url A URL object
 */
function route(string $routeName, string ...$params): Url
{
    static $router;

    if (!$router) {
        $router = pew("router");
    }

    $url = $router->createUrlFromRoute($routeName, $params);

    return new Url($url);
}

/**
 * Helper for session values.
 *
 * Accepts a period-delimited string of sub-indices.
 *
 * @param string|null $path Keys to access
 * @param mixed|null $default Value to return in case the keys don't exist
 * @return mixed Value of the key, or the Session object if no path is specified
 */
function session(string $path = null, mixed $default = null): mixed
{
    static $session;

    if (!$session) {
        $session = pew("session");
    }

    if (is_null($path)) {
        return $session;
    }

    return array_path($session->all(), $path) ?? $default;
}

/**
 * Create a slug from a string or string-like value.
 *
 * @param string|AbstractString $string
 * @param string $separator
 * @param string $language
 * @return AbstractString
 */
function slug(AbstractString|string $string, string $separator = "-", string $language = "en"): AbstractString
{
    return (new AsciiSlugger($language))
        // Create a basic slug
        ->slug((string) $string, $separator)
        // Enforce spaces between words
        ->replaceMatches("~([^A-Z])([A-Z])~", "\$1$separator\$2")
        // Enforce spaces before numbers
        ->replaceMatches("~(\\d+)~", "$separator\$1")
        // Collapse multiple consecutive separators
        ->replaceMatches("~[$separator]+~", "$separator")
        ->trim($separator)
        ->lower();
}

/**
 * Create a UnicodeString or ByteString object from a string or string-like value.
 *
 * @param mixed $string
 * @return AbstractString
 */
function str(mixed $string): AbstractString
{
    return s((string) $string);
}

/**
 * Gets an absolute URL, having the location of the site as base URL.
 *
 * If the site is hosted at http://www.example.com/pewexample, the call
 *     `echo url('www/css/styles.css');`
 * will print
 *     `http://www.example.com/pewexample/www/css/styles.css`.
 *
 * Pass associative arrays to add query parameters to the URL.
 *
 * @param mixed ...$path One or more path segments
 * @return string The resulting url
 */
function url(...$path): string
{
    static $base_url;

    if (!isset($base_url)) {
        $base_url = pew("request")->appUrl();
        $base_url = rtrim($base_url, "/") . "/";
    }

    $params = array_filter($path, "is_array");
    $segments = array_filter($path, fn ($segment) => is_scalar($segment) || is_callable([$segment, "__toString"]));
    $path = preg_replace('~\/+~', "/", join("/", $segments));

    $url = $base_url . ltrim($path, "/");

    if (count($params)) {
        $query = array_merge(...$params);
        $query_string = http_build_query($query);
        $url .= "?" . $query_string;
    }

    return $url;
}
