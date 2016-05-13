<?php
/**
 * These two classes have been <i>heavily borrowed</i> from Ruby on Rails' Activerecord so much that
 * this piece can be considered a straight port. The reason for this is that the vaildation process is
 * tricky due to order of operations/events. The former combined with PHP's odd typecasting means
 * that it was easier to formulate this piece base on the rails code.
 *
 * @package Activerecord
 */

namespace Activerecord;

use Activerecord\Errors;
use Activerecord\Reflections;
use Activerecord\Utils;

/**
 * Manages validations for a {@link Model}.
 *
 * This class isn't meant to be directly used. Instead you define
 * validators thru static variables in your {@link Model}. Example:
 *
 * <code>
 * class Person extends Activerecord\Model {
 *   static $validatesLengthOf = array(
 *     array('name', 'within' => array(30,100),
 *     array('state', 'is' => 2)
 *   );
 * }
 *
 * $person = new Person();
 * $person->name = 'Tito';
 * $person->state = 'this is not two characters';
 *
 * if (!$person->is_valid())
 *   print_r($person->errors);
 * </code>
 *
 * @package Activerecord
 * @see Errors
 * @link http://www.phpActiverecord.org/guides/validations
 */
class Validations
{

    private $model;
    private $options = [];
    private $validators = [];
    private $record;
    private static $VALIDATION_FUNCTIONS = [
        'validatesPresenceOf',
        'validatesSizeOf',
        'validatesLengthOf',
        'validatesInclusionOf',
        'validatesExclusionOf',
        'validatesFormatOf',
        'validatesNumericalityOf',
        'validatesUniquenessOf'
    ];
    private static $DEFAULT_VALIDATION_OPTIONS = [
        'on' => 'save',
        'allow_null' => false,
        'allow_blank' => false,
        'message' => null,
    ];
    private static $ALL_RANGE_OPTIONS = [
        'is' => null,
        'within' => null,
        'in' => null,
        'minimum' => null,
        'maximum' => null,
    ];
    private static $ALL_NUMERICALITY_CHECKS = [
        'greater_than' => null,
        'greater_than_or_equal_to' => null,
        'equal_to' => null,
        'less_than' => null,
        'less_than_or_equal_to' => null,
        'odd' => null,
        'even' => null
    ];

    /**
     * Constructs a {@link Validations} object.
     *
     * @param Model $model The model to validate
     * @return Validations
     */
    public function __construct(Model $model)
    {
        $this->model = $model;
        $this->record = new Errors($this->model);
        $this->reflect = Reflections::instance()->get(\get_class($this->model));
        $this->validators = \array_intersect(\array_keys($this->reflect->getStaticProperties()),
                self::$VALIDATION_FUNCTIONS);
    }

    public function getRecord()
    {
        return $this->record;
    }

    /**
     * Returns validator data.
     *
     * @return array
     */
    public function rules()
    {
        $data = [];
        foreach ($this->validators as $validate)
        {
            $attrs = $this->reflect->getStaticPropertyValue($validate);

            foreach (wrapStringsInArrays($attrs) as $attr)
            {
                $field = $attr[0];

                if (!isset($data[$field]) || !\is_array($data[$field]))
                {
                    $data[$field] = [];
                }

                $attr['validator'] = $validate;
                unset($attr[0]);
                \array_push($data[$field], $attr);
            }
        }
        return $data;
    }

    /**
     * Runs the validators.
     *
     * @return Errors the validation errors if any
     */
    public function validate()
    {
        foreach ($this->validators as $validate)
        {
            $definition = $this->reflect->getStaticPropertyValue($validate);
            $this->$validate(wrapStringsInArrays($definition));
        }

        $model_reflection = Reflections::instance()->get($this->model);

        if ($model_reflection->hasMethod('validate') && $model_reflection->getMethod('validate')->isPublic())
        {
            $this->model->validate();
        }

        $this->record->clearModel();
        return $this->record;
    }

    /**
     * Validates a field is not null and not blank.
     *
     * <code>
     * class Person extends Activerecord\Model {
     *   static $validatesPresenceOf = array(
     *     array('first_name'),
     *     array('last_name')
     *   );
     * }
     * </code>
     *
     * Available options:
     *
     * <ul>
     * <li><b>message:</b> custom error message</li>
     * <li><b>allow_blank:</b> allow blank strings</li>
     * <li><b>allow_null:</b> allow null strings</li>
     * </ul>
     *
     * @param array $attrs Validation definition
     */
    public function validatesPresenceOf($attrs)
    {
        $configuration = \array_merge(self::$DEFAULT_VALIDATION_OPTIONS,
                ['message' => Errors::$DEFAULT_ERROR_MESSAGES['blank'],
            'on' => 'save']);

        foreach ($attrs as $attr)
        {
            $options = \array_merge($configuration, $attr);
            $this->record->addOnBlank($options[0], $options['message']);
        }
    }

    /**
     * Validates that a value is included the specified array.
     *
     * <code>
     * class Car extends Activerecord\Model {
     *   static $validatesInclusionOf = array(
     *     array('fuel_type', 'in' => array('hyrdogen', 'petroleum', 'electric')),
     *   );
     * }
     * </code>
     *
     * Available options:
     *
     * <ul>
     * <li><b>in/within:</b> attribute should/shouldn't be a value within an array</li>
     * <li><b>message:</b> custome error message</li>
     * <li><b>allow_blank:</b> allow blank strings</li>
     * <li><b>allow_null:</b> allow null strings</li>
     * </ul>
     *
     * @param array $attrs Validation definition
     */
    public function validatesInclusionOf($attrs)
    {
        $this->validatesInclusionOrExclusionOf('inclusion', $attrs);
    }

    /**
     * This is the opposite of {@link validates_include_of}.
     *
     * Available options:
     *
     * <ul>
     * <li><b>in/within:</b> attribute should/shouldn't be a value within an array</li>
     * <li><b>message:</b> custome error message</li>
     * <li><b>allow_blank:</b> allow blank strings</li>
     * <li><b>allow_null:</b> allow null strings</li>
     * </ul>
     *
     * @param array $attrs Validation definition
     * @see validatesInclusionOf
     */
    public function validatesExclusionOf($attrs)
    {
        $this->validatesInclusionOrExclusionOf('exclusion', $attrs);
    }

    /**
     * Validates that a value is in or out of a specified list of values.
     *
     * Available options:
     *
     * <ul>
     * <li><b>in/within:</b> attribute should/shouldn't be a value within an array</li>
     * <li><b>message:</b> custome error message</li>
     * <li><b>allow_blank:</b> allow blank strings</li>
     * <li><b>allow_null:</b> allow null strings</li>
     * </ul>
     *
     * @see validatesInclusionOf
     * @see validatesExclusionOf
     * @param string $type Either inclusion or exclusion
     * @param $attrs Validation definition
     */
    public function validatesInclusionOrExclusionOf($type, $attrs)
    {
        $configuration = \array_merge(self::$DEFAULT_VALIDATION_OPTIONS,
                [
            'message' => Errors::$DEFAULT_ERROR_MESSAGES[$type],
            'on' => 'save']);

        foreach ($attrs as $attr)
        {
            $options = \array_merge($configuration, $attr);
            $attribute = $options[0];
            $var = $this->model->$attribute;

            if (isset($options['in']))
            {
                $enum = $options['in'];
            }
            elseif (isset($options['within']))
            {
                $enum = $options['within'];
            }

            if (!\is_array($enum))
            {
                [$enum];
            }

            $message = \str_replace('%s', $var, $options['message']);

            if ($this->isNullWithOption($var, $options) || $this->isBlankWithOption($var,
                            $options))
            {
                continue;
            }

            if (('inclusion' == $type && !\in_array($var, $enum)) || ('exclusion'
                    == $type && \in_array($var, $enum)))
            {
                $this->record->add($attribute, $message);
            }
        }
    }

    /**
     * Validates that a value is numeric.
     *
     * <code>
     * class Person extends Activerecord\Model {
     *   static $validatesNumericalityOf = array(
     *     array('salary', 'greater_than' => 19.99, 'less_than' => 99.99)
     *   );
     * }
     * </code>
     *
     * Available options:
     *
     * <ul>
     * <li><b>only_integer:</b> value must be an integer (e.g. not a float)</li>
     * <li><b>even:</b> must be even</li>
     * <li><b>odd:</b> must be odd"</li>
     * <li><b>greater_than:</b> must be greater than specified number</li>
     * <li><b>greater_than_or_equal_to:</b> must be greater than or equal to specified number</li>
     * <li><b>equal_to:</b> ...</li>
     * <li><b>less_than:</b> ...</li>
     * <li><b>less_than_or_equal_to:</b> ...</li>
     * <li><b>allow_blank:</b> allow blank strings</li>
     * <li><b>allow_null:</b> allow null strings</li>
     * </ul>
     *
     * @param array $attrs Validation definition
     */
    public function validatesNumericalityOf($attrs)
    {
        $configuration = \array_merge(self::$DEFAULT_VALIDATION_OPTIONS,
                ['only_integer' => false]);

        // Notice that for fixnum and float columns empty strings are converted to nil.
        // Validates whether the value of the specified attribute is numeric by trying to convert it to a float with Kernel.Float
        // (if only_integer is false) or applying it to the regular expression /\A[+\-]?\d+\Z/ (if only_integer is set to true).
        foreach ($attrs as $attr)
        {
            $options = \array_merge($configuration, $attr);
            $attribute = $options[0];
            $var = $this->model->$attribute;

            $numericalityOptions = \array_intersect_key(self::$ALL_NUMERICALITY_CHECKS,
                    $options);

            if ($this->isNullWithOption($var, $options))
            {
                continue;
            }

            $not_a_number_message = (isset($options['message']) ? $options['message']
                                : Errors::$DEFAULT_ERROR_MESSAGES['not_a_number']);

            if (true === $options['only_integer'] && !\is_integer($var))
            {
                if (!\preg_match('/\A[+-]?\d+\Z/', (string) ($var)))
                {
                    $this->record->add($attribute, $not_a_number_message);
                    continue;
                }
            }
            else
            {
                if (!\is_numeric($var))
                {
                    $this->record->add($attribute, $not_a_number_message);
                    continue;
                }

                $var = (float) $var;
            }

            foreach ($numericalityOptions as $option => $check)
            {
                $option_value = $options[$option];
                $message = (isset($options['message']) ? $options['message'] : Errors::$DEFAULT_ERROR_MESSAGES[$option]);

                if ('odd' != $option && 'even' != $option)
                {
                    $option_value = (float) $options[$option];

                    if (!\is_numeric($option_value))
                    {
                        throw new ExceptionValidation("Argument Error $option must be a number");
                    }

                    $message = \str_replace('%d', $option_value, $message);

                    if ('greater_than' == $option && !($var > $option_value))
                    {
                        $this->record->add($attribute, $message);
                    }
                    elseif ('greater_than_or_equal_to' == $option && !($var >= $option_value))
                    {
                        $this->record->add($attribute, $message);
                    }
                    elseif ('equal_to' == $option && !($var == $option_value))
                    {
                        $this->record->add($attribute, $message);
                    }
                    elseif ('less_than' == $option && !($var < $option_value))
                    {
                        $this->record->add($attribute, $message);
                    }
                    elseif ('less_than_or_equal_to' == $option && !($var <= $option_value))
                    {
                        $this->record->add($attribute, $message);
                    }
                }
                else
                {
                    if (('odd' == $option && !Utils::isOdd($var)) || ('even' == $option
                            && Utils::isOdd($var)))
                    {
                        $this->record->add($attribute, $message);
                    }
                }
            }
        }
    }

    /**
     * Alias of {@link validatesLengthOf}
     *
     * @param array $attrs Validation definition
     */
    public function validatesSizeOf($attrs)
    {
        $this->validatesLengthOf($attrs);
    }

    /**
     * Validates that a value is matches a regex.
     *
     * <code>
     * class Person extends Activerecord\Model {
     *   static $validatesFormatOf = array(
     *     array('email', 'with' => '/^.*?@.*$/')
     *   );
     * }
     * </code>
     *
     * Available options:
     *
     * <ul>
     * <li><b>with:</b> a regular expression</li>
     * <li><b>message:</b> custom error message</li>
     * <li><b>allow_blank:</b> allow blank strings</li>
     * <li><b>allow_null:</b> allow null strings</li>
     * </ul>
     *
     * @param array $attrs Validation definition
     */
    public function validatesFormatOf($attrs)
    {
        $configuration = \array_merge(self::$DEFAULT_VALIDATION_OPTIONS,
                ['message' => Errors::$DEFAULT_ERROR_MESSAGES['invalid'],
            'on' => 'save',
            'with' => null]);

        foreach ($attrs as $attr)
        {
            $options = \array_merge($configuration, $attr);
            $attribute = $options[0];
            $var = $this->model->$attribute;

            if (\is_null($options['with']) || !\is_string($options['with']) || !\is_string($options['with']))
            {
                throw new ExceptionValidation('Argument Error A regular expression must be supplied as the [with] option of the configuration array.');
            }
            else
            {
                $expression = $options['with'];
            }

            if ($this->isNullWithOption($var, $options) || $this->isBlankWithOption($var,
                            $options))
            {
                continue;
            }

            if (!@\preg_match($expression, $var))
            {
                $this->record->add($attribute, $options['message']);
            }
        }
    }

    /**
     * Validates the length of a value.
     *
     * <code>
     * class Person extends Activerecord\Model {
     *   static $validatesLengthOf = array(
     *     array('name', 'within' => array(1,50))
     *   );
     * }
     * </code>
     *
     * Available options:
     *
     * <ul>
     * <li><b>is:</b> attribute should be exactly n characters long</li>
     * <li><b>in/within:</b> attribute should be within an range array(min,max)</li>
     * <li><b>maximum/minimum:</b> attribute should not be above/below respectively</li>
     * <li><b>message:</b> custome error message</li>
     * <li><b>allow_blank:</b> allow blank strings</li>
     * <li><b>allow_null:</b> allow null strings. (Even if this is set to false, a null string is always shorter than a maximum value.)</li>
     * </ul>
     *
     * @param array $attrs Validation definition
     */
    public function validatesLengthOf($attrs)
    {
        $configuration = \array_merge(self::$DEFAULT_VALIDATION_OPTIONS,
                ['too_long' => Errors::$DEFAULT_ERROR_MESSAGES['too_long'],
            'too_short' => Errors::$DEFAULT_ERROR_MESSAGES['too_short'],
            'wrong_length' => Errors::$DEFAULT_ERROR_MESSAGES['wrong_length']
        ]);

        foreach ($attrs as $attr)
        {
            $options = \array_merge($configuration, $attr);
            $range_options = \array_intersect(\array_keys(self::$ALL_RANGE_OPTIONS),
                    \array_keys($attr));
            \sort($range_options);

            switch (\sizeof($range_options))
            {
                case 0:
                    throw new ExceptionValidation('Argument Error Range unspecified.  Specify the [within], [maximum], or [is] option.');

                case 1:
                    break;

                default:
                    throw new ExceptionValidation('Argument Error Too many range options specified.  Choose only one.');
            }

            $attribute = $options[0];
            $var = $this->model->$attribute;
            if ($this->isNullWithOption($var, $options) || $this->isBlankWithOption($var,
                            $options))
            {
                continue;
            }
            if ($range_options[0] == 'within' || $range_options[0] == 'in')
            {
                $range = $options[$range_options[0]];

                if (!(Utils::isArray('range', $range)))
                {
                    throw new ExceptionValidation("Argument Error $range_options[0] must be an array composing a range of numbers with key [0] being less than key [1]");
                }
                $range_options = ['minimum',
                    'maximum'];
                $attr['minimum'] = $range[0];
                $attr['maximum'] = $range[1];
            }
            foreach ($range_options as $range_option)
            {
                $option = $attr[$range_option];

                if ((int) $option <= 0)
                {
                    throw new ExceptionValidation("Argument Error $range_option value cannot use a signed integer.");
                }

                if (\is_float($option))
                {
                    throw new ExceptionValidation("Argument Error $range_option value cannot use a float for length.");
                }

                if (!($range_option == 'maximum' && \is_null($this->model->$attribute)))
                {
                    $messageOptions = ['is' => 'wrong_length',
                        'minimum' => 'too_short',
                        'maximum' => 'too_long'];

                    if (isset($options['message']))
                    {
                        $message = $options['message'];
                    }
                    else
                    {
                        $message = $options[$messageOptions[$range_option]];
                    }


                    $message = \str_replace('%d', $option, $message);
                    $attribute_value = $this->model->$attribute;
                    $len = \strlen($attribute_value);
                    $value = (int) $attr[$range_option];

                    if ('maximum' == $range_option && $len > $value)
                    {
                        $this->record->add($attribute, $message);
                    }

                    if ('minimum' == $range_option && $len < $value)
                    {
                        $this->record->add($attribute, $message);
                    }

                    if ('is' == $range_option && $len !== $value)
                    {
                        $this->record->add($attribute, $message);
                    }
                }
            }
        }
    }

    /**
     * Validates the uniqueness of a value.
     *
     * <code>
     * class Person extends Activerecord\Model {
     *   static $validatesUniquenessOf = array(
     *     array('name'),
     *     array(array('blah','bleh'), 'message' => 'blech')
     *   );
     * }
     * </code>
     *
     * Available options:
     *
     * <ul>
     * <li><b>with:</b> a regular expression</li>
     * <li><b>message:</b> custom error message</li>
     * <li><b>allow_blank:</b> allow blank strings</li>
     * <li><b>allow_null:</b> allow null strings</li>
     * </ul>
     *
     * @param array $attrs Validation definition
     */
    public function validatesUniquenessOf($attrs)
    {
        $configuration = \array_merge(self::$DEFAULT_VALIDATION_OPTIONS,
                ['message' => Errors::$DEFAULT_ERROR_MESSAGES['unique']]);
        // Retrieve connection from model for quote_name method
        $connection = $this->reflect->getMethod('connection')->invoke(null);

        foreach ($attrs as $attr)
        {
            $options = \array_merge($configuration, $attr);
            $pk = $this->model->getPrimaryKey();
            $pk_value = $this->model->$pk[0];

            if (\is_array($options[0]))
            {
                $add_record = \join("_and_", $options[0]);
                $fields = $options[0];
            }
            else
            {
                $add_record = $options[0];
                $fields = [$options[0]];
            }

            $sql = "";
            $conditions = [""];
            $pk_quoted = $connection->quoteName($pk[0]);
            if ($pk_value === null)
            {
                $sql = "{$pk_quoted} IS NOT NULL";
            }
            else
            {
                $sql = "{$pk_quoted} != ?";
                \array_push($conditions, $pk_value);
            }

            foreach ($fields as $field)
            {
                $field = $this->model->getRealAttributeName($field);
                $quoted_field = $connection->quoteName($field);
                $sql .= " AND {$quoted_field}=?";
                array_push($conditions, $this->model->$field);
            }

            $conditions[0] = $sql;

            if ($this->model->exists(['conditions' => $conditions]))
            {
                $this->record->add($add_record, $options['message']);
            }
        }
    }

    private function isNullWithOption($var, &$options)
    {
        return (\is_null($var) && (isset($options['allow_null']) && $options['allow_null']));
    }

    private function isBlankWithOption($var, &$options)
    {
        return (Utils::isBlank($var) && (isset($options['allow_blank']) && $options['allow_blank']));
    }

}
