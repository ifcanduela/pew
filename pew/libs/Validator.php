<?php

namespace pew\libs;

/**
 * Validator class.
 *
 * Basic field validation functionality for the Model class.
 * 
 * @package pew/libs
 * @author ifcanduela <ifcanduela@gmail.com>
 */
class Validator
{
    /** @var array Ruleset */
    private $rules = [];

    /** @var array Errors from the last validation attempt */
    private $errors = [];

    /**
     * Build a validator based on a ruleset.
     * 
     * @param array $rules An array of fields and rules
     */
    public function __construct(array $rules)
    {
        $this->rules($rules);
    }

    /**
     * Get or set the ruleset.
     * 
     * @param array $rules Array of fields and rules
     * @return array The current ruleset
     */
    public function rules(array $rules = null)
    {
        if (!is_null($rules)) {
            $this->rules = $rules;
        }

        return $this->rules;
    }

    /**
     * Reset the validation errors.
     * 
     * @return null
     */
    public function reset()
    {
        $this->errors = [];
    }

    /**
     * Validate an item against the current ruleset.
     * 
     * @param array $item Item to validate
     * @return bool True id the validation succeeded, false on failure
     */
    public function validate(array $item)
    {
        $this->reset();

        foreach ($this->rules as $field => $rules) {
            if (isSet($item[$field])) {
                foreach ($rules as $k => $v) {
                    if (is_numeric($k)) {
                        $validator = $v;
                        $values = null;
                    } else {
                        $validator = $k;
                        $values = $v;
                    }

                    $validator_method = 'validate_' . $validator;
                    if (method_exists($this, $validator_method)) {
                        $validation_result = call_user_func([$this, $validator_method], $item, $field, $values);
                        
                        if ($validation_result === false) {
                            if (is_array($values)) {
                                $valuestr = join(', ', $values);
                            } else {
                                $valuestr = $values;
                            }

                            $error_string = "Value of {$field} ({$item[$field]}) does not match $validator" . ($valuestr ? " ({$valuestr})" : '');
                            $this->errors[] = [$error_string, $validator, $field, $item[$field], $values];
                        }
                    } else {
                        throw new \Exception("Invalid Validator: $validator");
                    }
                }
            }
        }

        return count($this->errors) === 0;
    }

    /**
     * Get the error list.
     *
     * @return array The list of error for the last validation attempt
     */
    public function errors()
    {
        return $this->errors;
    }

    /**
     * Check the field has a non-null value.
     * 
     * @param array $item Item to validate
     * @param string $field Field to validate
     * @return bool True if the value is correct, false otherwise
     */
    protected function validate_not_null($item, $field)
    {
        return !is_null($item[$field]);
    }

    /**
     * Check the maximum length of a string.
     * 
     * @param array $item Item to validate
     * @param string $field Field to validate
     * @param int $max_length Maximum length
     * @return bool True if the value is correct, false otherwise
     */
    protected function validate_max_length($item, $field, $max_length)
    {
        return strlen($item[$field]) <= $max_length;
    }

    /**
     * Check the minimum length of a string.
     * 
     * @param array $item Item to validate
     * @param string $field Field to validate
     * @param int $min_length Minimum length
     * @return bool True if the value is correct, false otherwise
     */
    protected function validate_min_length($item, $field, $min_length)
    {
        return strlen($item[$field]) >= $min_length;
    }

    /**
     * Check for a valid e-mail address.
     * 
     * @param array $item Item to validate
     * @param string $field Field to validate
     * @return bool True if the value is correct, false otherwise
     */
    protected function validate_email($item, $field)
    {
        return false !== filter_var($item[$field], FILTER_VALIDATE_EMAIL);
    }

    /**
     * Check for a valid boolean value.
     *
     * A valid boolean value is either true, false, 0 or 1
     * 
     * @param array $item Item to validate
     * @param string $field Field to validate
     * @return bool True if the value is correct, false otherwise
     */
    protected function validate_boolean($item, $field)
    {
        if (is_numeric($item[$field])) {
            return ($item[$field]) == 0 || ($item[$field] == 1);
        }

        return is_bool($item[$field]);
    }

    protected function validate_values($item, $field, $values)
    {
        return in_array($item[$field], $values, true);
    }

    /**
     * Check that two fields have the same value.
     * 
     * @param array $item Item to validate
     * @param string $field1 Field to validate
     * @param string $field2 Field to compare
     * @return bool True if the value is correct, false otherwise
     */
    public function validate_compare($item, $field1, $field2)
    {
        return $item[$field1] == $item[$field2];
    }

    /**
     * Check that a field matches a regular expression pattern.
     * 
     * @param array $item Item to validate
     * @param string $field Field to validate
     * @param string $regex Pattern to match
     * @return bool True if the value is correct, false otherwise
     */
    public function validate_regex($item, $field, $regex)
    {
        return 1 === preg_match($regex, $item[$field]);
    }

    /**
     * Check that a field contains an numeric value.
     * 
     * @param array $item Item to validate
     * @param string $field Field to validate
     * @return bool True if the value is correct, false otherwise
     */
    public function validate_number($item, $field)
    {
        return is_numeric($item[$field]);
    }

    /**
     * Validate the type of a variable.
     *
     * Allowed values for $type are:
     *     - array
     *     - bool
     *     - callable
     *     - double
     *     - float
     *     - int
     *     - integer
     *     - long
     *     - null
     *     - numeric
     *     - object
     *     - real
     *     - resource
     *     - scalar
     *     - string
     *
     * This validator can be extended by defining is_* functions.
     * 
     * @param array $item Item to validate
     * @param string $field Field to validate
     * @param string $type A valid PHP type
     * @return bool True if the value is correct, false otherwise
     */
    public function validate_type($item, $field, $type)
    {
        $func = "is_{$type}";
        
        if (!function_exists($func)) {
            throw new \RuntimeException("Validation of type {$type} is not available");
        }

        return call_user_func($func, $item[$field]);
    }
}
