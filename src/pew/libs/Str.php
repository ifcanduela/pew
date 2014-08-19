<?php

namespace pew\libs;

/**
 * A collection of useful string manipulation functions.
 *
 * @package pew\libs
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
        mb_internal_encoding('UTF-8');
        mb_regex_encoding('UTF-8');

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
                $str = new Str($part);
                $part = $str->upper_case_first();
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
        $str = $this->transliterate()->string;
        
        $str = preg_replace('/[^\w\d +*._\-]/', '-', $str);
        $str = mb_strtolower(str_replace([' ', '+', '*', '.'], '-', trim($str)));
        $str = preg_replace('/-+/', '-', $str);
        
        return $str;
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
            $with_spaces = [];

            for ($i = 0; $i < mb_strlen($str); $i++) {
                $char = mb_substr($str, $i, 1);

                if (mb_strtoupper($char) === $char) {
                    $char = ' ' . mb_strtolower($char);
                }

                $with_spaces[] = $char;
            }
            
            $str = join('', $with_spaces);
        }

        $str = mb_ereg_replace('([\s\-]+)', '_', $str);
        $str = new Str(ltrim($str, '_'));

        return $str->lower_case();
    }

    /**
     * Convert an underscored or camel-case string to title case.
     * 
     * @return Str
     */
    protected function title_case($from_camel_case = false)
    {
        $str = $this->underscores($from_camel_case)->string;
        $str = new Str(trim(str_replace('_', ' ', $str)));

        $parts = $str->split(' ');

        array_walk($parts, function (&$part) {
            $str = new Str($part);
            $part = $str->upper_case_first();
        });

        $title = new Str(" ");

        return $title->join($parts);
    }

    /**
     * Convert a string to lower-case letters.
     * 
     * @return Str
     */
    protected function lower_case()
    {
        $str = $this->string;

        return new Str(mb_strtolower($str));
    }

    /**
     * Convert a string to upper-case letters.
     * 
     * @return Str
     */
    protected function upper_case()
    {
        $str = $this->string;

        return new Str(mb_strtoupper($str));
    }

    /**
     * Convert the first letter of a string to upper-case.
     * 
     * @return Str
     */
    protected function upper_case_first()
    {
        $str = $this->string;
        
        $first = mb_substr($str, 0, 1);
        $rest = mb_substr($str, 1);
        return new Str(mb_strtoupper($first) . $rest);
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

        return mb_substr($str, 0, $n);
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

        return mb_substr($str, -$n);
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
        $stop = mb_strpos($str, $stop_before);

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
        $start = mb_strlen($start_after) + mb_strpos($str, $start_after);

        $chunk = new Str($str);

        return $chunk->substring($start);
    }

    /**
     * Find the position of the first ocurrence of a substring.
     * 
     * @param string $substring Character or string to search for
     * @param integer $skip Number of characters to skip from the beginning of the string
     * @return int Zero-based index
     */
    protected function first_of($substring, $skip = 0)
    {
        $str = $this->string;
        
        return mb_strpos($str, $substring, $skip);
    }

    /**
     * Find the position of the last ocurrence of a substring.
     * 
     * @param string $substring Character or string to search for
     * @param integer $skip Number of characters to skip from the end of the string
     * @return int Zero-based index
     */
    protected function last_of($substring, $skip = 0)
    {
        $str = $this->string;

        return mb_strrpos($str, $substring, -$skip);
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

        if (is_null($length)) {
            # mb_string uses 0 if $length is null
            $length = mb_strlen($str);
        }
        
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
     * Convert a string to ASCII-only characters.
     * 
     * @return Str
     */
    protected function transliterate()
    {
        $str = $this->string;

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

        return new Str($str);
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
            return call_user_func_array([$this, $method], $args);
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
