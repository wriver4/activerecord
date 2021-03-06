<?php
/**
 * @package Activerecord
 */

namespace Activerecord\Serializers;

use Activerecord\Model;

/**
 * Base class for Model serializers.
 *
 * All serializers support the following options:
 *
 * <ul>
 * <li><b>only:</b> a string or array of attributes to be included.</li>
 * <li><b>except:</b> a string or array of attributes to be excluded.</li>
 * <li><b>methods:</b> a string or array of methods to invoke. The method's name will be used as a key for the final attributes array
 * along with the method's returned value</li>
 * <li><b>include:</b> a string or array of associated models to include in the final serialized product.</li>
 * <li><b>only_method:</b> a method that's called and only the resulting array is serialized
 * <li><b>skip_instruct:</b> set to true to skip the <?xml ...?> declaration.</li>
 * </ul>
 *
 * Example usage:
 *
 * <code>
 * # include the attributes id and name
 * # run $model->encoded_description() and include its return value
 * # include the comments association
 * # include posts association with its own options (nested)
 * $model->to_json(array(
 *   'only' => array('id','name', 'encoded_description'),
 *   'methods' => array('encoded_description'),
 *   'include' => array('comments', 'posts' => array('only' => 'id'))
 * ));
 *
 * # except the password field from being included
 * $model->to_xml(array('except' => 'password')));
 * </code>
 *
 * @package Activerecord
 * @link http://www.phpActiverecord.org/guides/utilities#topic-serialization
 */
abstract class AbstractSerialize
{

    protected $model;
    protected $options;
    protected $attributes;

    /**
     * The default format to serialize DateTime objects to.
     *
     * @see DateTime
     */
    public static $datetime_format = 'iso8601';

    /**
     * Set this to true if the serializer needs to create a nested array keyed
     * on the name of the included classes such as for xml serialization.
     *
     * Setting this to true will produce the following attributes array when
     * the include option was used:
     *
     * <code>
     * $user = array('id' => 1, 'name' => 'Tito',
     *   'permissions' => array(
     *     'permission' => array(
     *       array('id' => 100, 'name' => 'admin'),
     *       array('id' => 101, 'name' => 'normal')
     *     )
     *   )
     * );
     * </code>
     *
     * Setting to false will produce this:
     *
     * <code>
     * $user = array('id' => 1, 'name' => 'Tito',
     *   'permissions' => array(
     *     array('id' => 100, 'name' => 'admin'),
     *     array('id' => 101, 'name' => 'normal')
     *   )
     * );
     * </code>
     *
     * @var boolean
     */
    protected $includes_with_class_name_element = false;

    /**
     * Constructs a {@link Serialization} object.
     *
     * @param Model $model The model to serialize
     * @param array &$options Options for serialization
     * @return Serialization
     */
    public function __construct(Model $model, &$options)
    {
        $this->model = $model;
        $this->options = $options;
        $this->attributes = $model->attributes();
        $this->parseOptions();
    }

    private function parseOptions()
    {
        $this->checkOnly();
        $this->checkExcept();
        $this->checkMethods();
        $this->checkInclude();
        $this->checkOnlyMethod();
    }

    private function checkOnly()
    {
        if (isset($this->options['only']))
        {
            $this->optionsToArray('only');

            $exclude = \array_diff(\array_keys($this->attributes),
                    $this->options['only']);
            $this->attributes = \array_diff_key($this->attributes,
                    \array_flip($exclude));
        }
    }

    private function checkExcept()
    {
        if (isset($this->options['except']) && !isset($this->options['only']))
        {
            $this->optionsToArray('except');
            $this->attributes = \array_diff_key($this->attributes,
                    \array_flip($this->options['except']));
        }
    }

    private function checkMethods()
    {
        if (isset($this->options['methods']))
        {
            $this->optionsToArray('methods');

            foreach ($this->options['methods'] as $method)
            {
                if (\method_exists($this->model, $method))
                {
                    $this->attributes[$method] = $this->model->$method();
                }
            }
        }
    }

    private function checkOnlyMethod()
    {
        if (isset($this->options['only_method']))
        {
            $method = $this->options['only_method'];
            if (\method_exists($this->model, $method))
            {
                $this->attributes = $this->model->$method();
            }
        }
    }

    private function checkInclude()
    {
        if (isset($this->options['include']))
        {
            $this->optionsToArray('include');

            $serializer_class = \get_class($this);

            foreach ($this->options['include'] as $association => $options)
            {
                if (!\is_array($options))
                {
                    $association = $options;
                    $options = [];
                }

                try
                {
                    $assoc = $this->model->$association;

                    if ($assoc === null)
                    {
                        $this->attributes[$association] = null;
                    }
                    elseif (!\is_array($assoc))
                    {
                        $serialized = new $serializer_class($assoc, $options);
                        $this->attributes[$association] = $serialized->toArray();
                    }
                    else
                    {
                        $includes = [];

                        foreach ($assoc as $a)
                        {
                            $serialized = new $serializer_class($a, $options);

                            if ($this->includes_with_class_name_element)
                            {
                                $includes[\strtolower(\get_class($a))][] = $serialized->toArray();
                            }
                            else
                            {
                                $includes[] = $serialized->toArray();
                            }
                        }

                        $this->attributes[$association] = $includes;
                    }
                }
                catch (UndefinedProperty $e)
                {
                    \Log::log('UndefinedProperty');//move along
                }
            }
        }
    }

    final protected function optionsToArray($key)
    {
        if (!\is_array($this->options[$key]))
        {
            $this->options[$key] = [$this->options[$key]];
        }
    }

    /**
     * Returns the attributes array.
     * @return array
     */
    final public function toArray()
    {
        foreach ($this->attributes as &$value)
        {
            if ($value instanceof \DateTime)
            {
                $value = $value->format(self::$datetime_format);
            }
        }
        return $this->attributes;
    }

    /**
     * Returns the serialized object as a string.
     * @see toString
     * @return string
     */
    final public function __toString()
    {
        return $this->toString();
    }

    /**
     * Performs the serialization.
     * @return string
     */
    abstract public function toString();
}
