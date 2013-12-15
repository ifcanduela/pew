<?php

namespace pew\libs;

/**
 * A collection of useful string manipulation functions.
 *
 * @package pew/libs
 * @author ifcanduela <ifcanduela@gmail.com>
 */
class Str
{
    /** @var string */
    public $string = '';

    /**
     * Create a Str object.
     * 
     * @param string $str String data
     */
    public function __construct($str = '')
    {
        $this->string = $str;
    }

    /**
     * Join multiple strings together using the current string as separator.
     *
     * This method will trims whitespace as well as the separator from the
     * beginning and end of each segment.
     *
     * @param string|string[] $parts The string parts to join
     * @return Str Resulting string
     */
    protected function join($parts)
    {
        $sep = $this->string;
        $parts = func_get_args();

        if (is_array($parts[0])) {
            $parts = $parts[0];
        }

        array_walk($parts, function(&$part) use ($sep) {
            $part = trim(trim($part), $sep);
        });

        return new Str(join($sep, $parts));
    }

    /**
     * Split the string using a pattern.
     * 
     * @param string $pattern A string or regular expression
     * @param integer $limit Maximum number of parts to return
     * @return string[] An array of string parts
     */
    protected function split($pattern, $limit = -1)
    {
        $str = $this->string;

        $parts = mb_split($pattern, $str, $limit);

        return $parts;
    }

    /**
     * Convert a string into camel-case.
     * 
     * @param boolean $upper_case_first Whether to upper-case the first letter or not
     * @return Str Camel-cased  string
     */
    protected function camel_case($upper_case_first = true)
    {
        $str = $this->string;
        $parts = mb_split("[ _-]+", $str);

        array_walk($parts, function(&$part, $index) use($upper_case_first) {
            if ($index || $upper_case_first) {
                $part = ucfirst($part);
            }
        });

        $sep = new Str;

        return $sep->join($parts);
    }

    /**
     * Convert a string into URL-friendly characters.
     *
     * Uses iconv for transliteration.
     * 
     * @return Str Slugified string
     */
    protected function slug()
    {
        $str = $this->string;

        $str = iconv('UTF-8', 'ASCII//TRANSLIT', $str);
        $str = preg_replace('/[^\w\d +*._\-]/', '-', $str);
        $str = strtolower(str_replace([' ', '+', '*', '.'], '-', trim($str)));
        $str = preg_replace('/-+/', '-', $str);
        
        return new Str($str);
    }

    /**
     * Convert a string into a underscore-separated slug.
     *
     * If $from_camel_case is TRUE, an underscore will me inserted before each
     * upper-case letter.
     * 
     * @param boolean $from_camel_case Insert an underscore before every upper-case letter
     * @return Str Underscore-separated string
     */
    protected function underscores($from_camel_case = false)
    {
        $str = $this->string;

        if ($from_camel_case) {
            $str = preg_replace_callback('/([A-Z])/', function ($part) {
                return '_' . $part[0];
            }, $str);
            $str = ltrim($str, '_');
        } else {
            $str = str_replace([' ', '-'], '_', $str);
        }

        return new Str(strtolower($str));
    }

    /**
     * Append a suffix if it's not already there.
     * 
     * @param string $affix String to append
     * @return Str String with suffix
     */
    protected function safe_append($affix)
    {
        $str = $this->string;

        if (mb_strrpos($str, $affix) !== (mb_strlen($str) - mb_strlen($affix))) {
            $str = $str . $affix;
        }

        return new Str($str);
    }

    /**
     * Pewpwnd a prefix if it's not already there.
     * 
     * @param string $affix String to prepend
     * @return Str String with prefix
     */
    protected function safe_prepend($affix)
    {
        $str = $this->string;

        if (0 !== mb_strrpos($str, $affix)) {
            $str = $affix . $str;
        }

        return new Str($str);
    }

    /**
     * Check if the string begins with the supplied substring.
     * 
     * @param string|string[] $matches A string to match, or a list of strings
     * @return boolean True if the string begins with any of the substrings
     */
    protected function begins_with($matches)
    {
        $str = $this->string;

        if (!is_array($matches)) {
            $matches = [$matches];
        }

        foreach ($matches as $match) {
            if (0 === strpos($str, $match)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if the string ends with the supplied substring.
     * 
     * @param string|string[] $matches A string to match, or a list of strings
     * @return boolean True if the string ends with any of the substrings
     */
    protected function ends_with($matches)
    {
        $str = $this->string;

        if (!is_array($matches)) {
            $matches = [$matches];
        }

        foreach ($matches as $match) {
            if (mb_strlen($str) - mb_strlen($match) === mb_strrpos($str, $match)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the first characters of the string.
     * 
     * @param integer $n Amount of characters to get
     * @return Str A substring of $n characters
     */
    protected function first($n = 1)
    {
        $str = $this->string;

        return substr($str, 0, $n);
    }

    /**
     * Get the last characters of the string.
     * 
     * @param integer $n Amount of characters to get
     * @return Str A substring of $n characters
     */
    protected function last($n = 1)
    {
        $str = $this->string;

        return substr($str, -$n);
    }

    /**
     * Get characters from the beginning of a string until a substring is found.
     * 
     * @param string $n String to find
     * @return Str A substring
     */
    protected function until($stop_before)
    {
        $str = $this->string;
        $stop = strpos($str, $stop_before);

        $chunk = new Str($str);

        return $chunk->substring(0, $stop);
    }

    /**
     * Get characters after a string.
     * 
     * @param string $start_after String to find and skip
     * @return Str A substring
     */
    protected function from($start_after)
    {
        $str = $this->string;
        $start = mb_strlen($start_after) + strpos($str, $start_after);

        $chunk = new Str($str);

        return $chunk->substring($start);
    }

    /**
     * Extract a substring based on position and length.
     * 
     * @param integer $from starting position
     * @param integer $length Amount of characters to extract
     * @return Str A substring
     */
    protected function substring($from, $length = null)
    {
        $str = $this->string;
        $str = mb_substr($str, $from, $length);

        return new Str($str);
    }

    /**
     * Extract a single character based on position.
     * 
     * @param integer $pos Position of the character
     * @return Str A single-charcter string
     */
    protected function char_at($pos)
    {
        return $this->substring($pos, 1);
    }

    /**
     * Convert the object into string for use in string contexts.
     * 
     * @return string
     */
    public function __toString()
    {
        return $this->string;
    }

    /**
     * Call the methods in instance context
     * 
     * @param string $method
     * @param array $args
     * @return mixed
     */
    public function __call($method, $args)
    {
        if (method_exists($this, $method)) {
            return $this->$method($args);
        }

        throw new \BadMethodCallException("Class Str does not have a method called '{$method}'");        
    }

    /**
     * Call the methods in static context
     * 
     * @param string $method
     * @param array $args
     * @return mixed
     */
    public static function __callStatic($method, $args)
    {
        if (method_exists(__CLASS__, $method)) {
            $string = array_shift($args);
            $str = new Str($string);
            return call_user_func_array([$str, $method], $args);
        }

        throw new \BadMethodCallException("Class Str does not have a method called '{$method}'");
    }
}
