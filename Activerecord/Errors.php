<?php

namespace Activerecord;

/**
 * Class that holds {@link Validations} errors.
 *
 * @package Activerecord
 */
class Errors
        implements \IteratorAggregate
{

    private $model;
    private $errors;
    public static $default_error_messages = [
        'inclusion' => "is not included in the list",
        'exclusion' => "is reserved",
        'invalid' => "is invalid",
        'confirmation' => "doesn't match confirmation",
        'accepted' => "must be accepted",
        'empty' => "can't be empty",
        'blank' => "can't be blank",
        'too_long' => "is too long (maximum is %d characters)",
        'too_short' => "is too short (minimum is %d characters)",
        'wrong_length' => "is the wrong length (should be %d characters)",
        'taken' => "has already been taken",
        'not_a_number' => "is not a number",
        'greater_than' => "must be greater than %d",
        'equal_to' => "must be equal to %d",
        'less_than' => "must be less than %d",
        'odd' => "must be odd",
        'even' => "must be even",
        'unique' => "must be unique",
        'less_than_or_equal_to' => "must be less than or equal to %d",
        'greater_than_or_equal_to' => "must be greater than or equal to %d"
    ];

    /**
     * Constructs an {@link Errors} object.
     *
     * @param Model $model The model the error is for
     * @return Errors
     */
    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    /**
     * Nulls $model so we don't get pesky circular references. $model is only needed during the
     * validation process and so can be safely cleared once that is done.
     */
    public function clearModel()
    {
        $this->model = null;
    }

    /**
     * Add an error message.
     *
     * @param string $attribute Name of an attribute on the model
     * @param string $msg The error message
     */
    public function add($attribute, $msg)
    {
        if (\is_null($msg))
        {
            $msg = self :: $default_error_messages['invalid'];
        }

        if (!isset($this->errors[$attribute]))
        {
            $this->errors[$attribute] = [$msg];
        }
        else
        {
            $this->errors[$attribute][] = $msg;
        }
    }

    /**
     * Adds an error message only if the attribute value is
     * {@link http://www.php.net/empty empty}.
     *
     * @param string $attribute Name of an attribute on the model
     * @param string $msg The error message
     */
    public function addOnEmpty($attribute, $msg)
    {
        if (empty($msg))
        {
            $msg = self::$default_error_messages['empty'];
        }

        if (empty($this->model->$attribute))
        {
            $this->add($attribute, $msg);
        }
    }

    /**
     * Retrieve error messages for an attribute.
     *
     * @param string $attribute Name of an attribute on the model
     * @return array or null if there is no error.
     */
    public function __get($attribute)
    {
        if (!isset($this->errors[$attribute]))
        {
            return null;
        }

        return $this->errors[$attribute];
    }

    /**
     * Adds the error message only if the attribute value was null or an
     * empty string.
     *
     * @param string $attribute Name of an attribute on the model
     * @param string $msg The error message
     */
    public function addOnBlank($attribute, $msg)
    {
        if (!$msg)
        {
            $msg = self::$default_error_messages['blank'];
        }

        if (($value = $this->model->$attribute) === '' || $value === null)
        {
            $this->add($attribute, $msg);
        }
    }

    /**
     * Returns true if the specified attribute had any error messages.
     *
     * @param string $attribute Name of an attribute on the model
     * @return boolean
     */
    public function isInvalid($attribute)
    {
        return isset($this->errors[$attribute]);
    }

    /**
     * Returns the error message(s) for the specified attribute or null if none.
     *
     * @param string $attribute Name of an attribute on the model
     * @return string/array
     * Array of strings if several error occured on this attribute.
     */
    public function on($attribute)
    {
        $errors = $this->$attribute;

        return $errors && count($errors) == 1 ? $errors[0] : $errors;
    }

    /**
     * Returns the internal errors object.
     *
     * <code>
     * $model->errors->getRawErrors();
     *
     * # array(
     * #  "name" => array("can't be blank"),
     * #  "state" => array("is the wrong length (should be 2 chars)",
     * # )
     * </code>
     */
    public function getRawErrors()
    {
        return $this->errors;
    }

    /**
     * Returns all the error messages as an array.
     *
     * <code>
     * $model->errors->fullMessages();
     *
     * # array(
     * #  "Name can't be blank",
     * #  "State is the wrong length (should be 2 chars)"
     * # )
     * </code>
     *
     * @return array
     */
    public function fullMessages()
    {
        $fullMessages = [];

        // $this->toArray(function($attribute, $message) use (&$fullMessages)
        //  orginal
        $this->toArray(function($message) use (&$fullMessages)
        {
            $fullMessages[] = $message;
        });

        return $fullMessages;
    }

    /**
     * Returns all the error messages as an array, including error key.
     *
     * <code>
     * $model->errors->errors();
     *
     * # array(
     * #  "name" => array("Name can't be blank"),
     * #  "state" => array("State is the wrong length (should be 2 chars)")
     * # )
     * </code>
     *
     * @param callable $closure
     * Closure to fetch the errors in some other format (optional)
     * This closure has the signature function($attribute, $message)
     * and is called for each available error message.
     * @return array
     */
    public function toArray($closure = null)
    {
        $errors = [];

        if ($this->errors)
        {
            foreach ($this->errors as $attribute => $messages)
            {
                foreach ($messages as $msg)
                {
                    if (\is_null($msg))
                    {
                        continue;
                    }
                    $errors[$attribute][] = ($message = Utils::humanAttribute($attribute).' '.$msg);

                    if ($closure)
                    {
                        $closure($attribute, $message);
                    }
                }
            }
        }
        return $errors;
    }

    /**
     * Convert all error messages to a String.
     * This function is called implicitely if the object is casted to a string:
     *
     * <code>
     * echo $error;
     *
     * # "Name can't be blank\nState is the wrong length (should be 2 chars)"
     * </code>
     * @return string
     */
    public function __toString()
    {
        return \implode("\n", $this->fullMessages());
    }

    /**
     * Returns true if there are no error messages.
     * @return boolean
     */
    public function isEmpty()
    {
        return empty($this->errors);
    }

    /**
     * Clears out all error messages.
     */
    public function clear()
    {
        $this->errors = [];
    }

    /**
     * Returns the number of error messages there are.
     * @return int
     */
    public function size()
    {
        if ($this->isEmpty())
        {
            return 0;
        }

        $count = 0;

        // foreach ($this->errors as $attribute => $error) original
        foreach ($this->errors as $error)
        {
            $count += count($error);
        }

        return $count;
    }

    /**
     * Returns an iterator to the error messages.
     *
     * This will allow you to iterate over the {@link Errors} object using foreach.
     *
     * <code>
     * foreach ($model->errors as $msg)
     *   echo "$msg\n";
     * </code>
     *
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->fullMessages());
    }

}