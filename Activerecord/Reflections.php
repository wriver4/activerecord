<?php
/**
 * @package Activerecord
 */

namespace Activerecord;

use Activerecord\Exceptions\ExceptionActiverecord;
use Activerecord\Reflections;
use Activerecord\Singleton;

/**
 * Simple class that caches reflections of classes.
 *
 * @package Activerecord
 */
class Reflections
        extends Singleton
{

    /**
     * Current reflections.
     *
     * @var array
     */
    private $reflections = [];

    /**
     * Instantiates a new ReflectionClass for the given class.
     *
     * @param string $class Name of a class
     * @return Reflections $this so you can chain calls like Reflections::instance()->add('class')->get()
     */
    public function add($class = null)
    {
        $class = $this->getClass($class);

        if (!isset($this->reflections[$class]))
        {
            $this->reflections[$class] = new \ReflectionClass($class);
        }

        return $this;
    }

    /**
     * Destroys the cached ReflectionClass.
     *
     * Put this here mainly for testing purposes.
     *
     * @param string $class Name of a class.
     * @return void
     */
    public function destroy($class)
    {
        if (isset($this->reflections[$class]))
        {
            $this->reflections[$class] = null;
        }
    }

    /**
     * Get a cached ReflectionClass.
     *
     * @param string $class Optional name of a class
     * @return mixed null or a ReflectionClass instance
     * @throws Activerecord if class was not found
     */
    public function get($class = null)
    {
        $class = $this->getClass($class);

        if (isset($this->reflections[$class]))
        {
            return $this->reflections[$class];
        }

        throw new ExceptionActiverecord("Class not found: $class");
    }

    /**
     * Retrieve a class name to be reflected.
     *
     * @param mixed $mixed An object or name of a class
     * @return string
     */
    private function getClass($mixed = null)
    {
        if (\is_object($mixed))
        {
            return \get_class($mixed);
        }

        if (!\is_null($mixed))
        {
            return $mixed;
        }

        return \get_called_class();
    }

}