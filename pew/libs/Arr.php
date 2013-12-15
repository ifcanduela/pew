<?php

namespace pew\libs;

class Arr
{
    public static function index(array $arr, $index)
    {
        return $arr[$index];
    }
    
    public static function first(array $arr, $n = 1)
    {
        $slice = array_slice($arr, 0, $n);

        return count($slice) === 1 ? $slice[0] : $slice;
    }

    public static function last(array $arr, $n = 1)
    {
        $slice = array_slice($arr, -$n, $n);

        return count($slice) === 1 ? $slice[0] : $slice;
    }

    public static function pluck($arr, $filter)
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
        
        # if there are no remaining filters, $arr complies with the rules
        if (count($filters) == 0) {
            return $arr;
        }

        # if $arr is an object, get its properties
        if (is_object($arr)) {
            $arr = get_object_vars($arr);
        }
        
        # by this point, $arr must be an array
        if (!is_array($arr)) {
            return null;
        }
        
        # get the current filter
        $f = array_shift($filters);
        
        # scan the arr array
        foreach ($arr as $key => $value) {
            # assume recursive calls won't be necessary
            $reap = false;
            
            switch ($f) {
                case '#':
                    # match any number
                    $reap = is_numeric($key);
                    break;
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
                # if the value matched, call recursively to reap()
                $arr[$key] = static::pluck($arr[$key], $filters);
                # if the result is empty, discard it
                if (is_null($arr[$key]) || (is_array($arr[$key]) && count($arr[$key]) === 0)) {
                    unset($arr[$key]);
                }
            } else {
                # if the key didn't match, discard it
                unset($arr[$key]);
            }
        }
        
        return $arr;
    }

    public static function flatten($arr)
    {
        # store results here
        $flat = [];
        
        # loop through the $arr array
        foreach ($arr as $key => $value) {
            if (is_array($value)) {
                # if value's an array, merge current elements and value's flattened result
                $flat = array_merge($flat, self::flatten($value));
            } else {
                # if $value is a scalar value, append it to the results
                $flat[] = $value;
            }
        }
        
        return $flat;
    }

    public static function reindex($arr, $key_name, $value_name = null)
    {
        $result = array();

        foreach ($arr as $key => $value) {
            if (is_object($value)) {
                # normalize to array
                $value = (array) $value;
            }

            if (is_array($value) && isSet($value[$key_name])) {
                $key_name_value = $value[$key_name];
                
                if (is_null($value_name)) {
                    # if $value_name is null the while element is used
                    $value_name_value = $value;
                } elseif (isSet($value[$value_name])) {
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

    public static function get_path(array $arr, $path)
    {
        $indexes = explode('.', $path);
        
        do {
            $index = array_shift($indexes);
            
            if (!isSet($arr[$index])) {
                return null;
            }

            $arr = $arr[$index];
        } while (!empty($indexes));

        return $arr;
    }

    public static function set_path(array &$arr, $path, $value)
    {
        $indexes = explode('.', $path);
        
        do {
            $index = array_shift($indexes);
            
            if (!isSet($arr[$index])) {
                $arr[$index] = [];
            }

            $arr =& $arr[$index];
        } while (!empty($indexes));

        $arr = $value;
    }
}

$a = [1, 2, 3, 4, 5];

var_dump(Arr::index($a, 4));
var_dump(Arr::index($a, 9));
var_dump(Arr::last($a));
var_dump(Arr::last($a, 3));
var_dump(Arr::first($a));
var_dump(Arr::first($a, 2));

$s = [
    ['id' => 1, 'name' => 'Igor'],
    ['id' => 21, 'name' => 'Igordo'],
    ['id' => 88, 'name' => 'Fatso'],
];

var_dump(Arr::reindex($s, 'id'));
var_dump(Arr::pluck($s, '#:id'));
var_dump(Arr::flatten($s));

$p = [
    'session' => [
        'user' => [
            'username' => "Guest",
            'id' => 32,
        ]
    ]
];

var_dump(Arr::get_path($p, 'session.user.username'));
Arr::set_path($p, 'session.user.username', 'Admin');
var_dump($p);
Arr::set_path($p, 'session.user', ['nothing' => 'Here']);
var_dump($p);
