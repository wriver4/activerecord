<?php

namespace Activerecord\Exceptions;

use Activerecord\Exceptions\ExceptionModel;

/**
 * Summary of file UndefinedProperty.
 *
 * Description of file UndefinedProperty.
 *
 *
 * @license
 *
 * @copyright
 *
 * @version
 *
 * @author mark weisser <mark at whizbangdevelopers.com>
 */

/**
 * Thrown when attempting to access an invalid property on a {@link Model}.
 *
 * @package Activerecord
 */
class ExceptionUndefinedProperty
        extends ExceptionModel
{

    /**
     * Sets the Exceptions message to show the undefined property's name.
     *
     * @param str $property_name name of undefined property
     * @return void
     */
    public function __construct($class_name, $property_name)
    {
        if (\is_array($property_name))
        {
            $this->message = \implode("\r\n", $property_name);
            return;
        }

        $this->message = "Undefined property: {$class_name}->{$property_name} in {$this->file} on line {$this->line}";
        parent::__construct();
    }

}
