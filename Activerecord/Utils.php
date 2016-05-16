<?php
/**
 *
 * @package Activerecord
 */
/*
 * Thanks to http://www.eval.ca/articles/php-pluralize (MIT license)
 *           http://dev.rubyonrails.org/browser/trunk/activesupport/lib/active_support/inflections.rb (MIT license)
 *           http://www.fortunecity.com/bally/durrus/153/gramch13.html
 *           http://www2.gsu.edu/~wwwesl/egw/crump.htm
 *
 * Changes (12/17/07)
 *   Major changes
 *   --
 *   Fixed irregular noun algorithm to use regular expressions just like the original Ruby source.
 *       (this allows for things like fireman -> firemen
 *   Fixed the order of the singular array, which was backwards.
 *
 *   Minor changes
 *   --
 *   Removed incorrect pluralization rule for /([^aeiouy]|qu)ies$/ => $1y
 *   Expanded on the list of exceptions for *o -> *oes, and removed rule for buffalo -> buffaloes
 *   Removed dangerous singularization rule for /([^f])ves$/ => $1fe
 *   Added more specific rules for singularizing lives, wives, knives, sheaves, loaves, and leaves and thieves
 *   Added Exceptions to /(us)es$/ => $1 rule for houses => house and blouses => blouse
 *   Added excpetions for feet, geese and teeth
 *   Added rule for deer -> deer
 *
 * Changes:
 *   Removed rule for virus -> viri
 *   Added rule for potato -> potatoes
 *   Added rule for *us -> *uses
 */

namespace Activerecord;

use Activerecord\Inflector;
use Activerecord\Utils;
use function Activerecord\arrayFlatten;
use function Activerecord\hasNamespace;

/**
 * Some internal utility functions.
 *
 * @package Activerecord
 */
class Utils
{

    public static function extractOptions($options)
    {
        return \is_array(\end($options)) ? \end($options) : [];
    }

    public static function addCondition($condition, &$conditions = [],
            $conjuction = 'AND')
    {
        if (\is_array($condition))
        {
            if (empty($conditions))
            {
                $conditions = arrayFlatten($condition);
            }
            else
            {
                $conditions[0] .= " $conjuction ".\array_shift($condition);
                $conditions[] = arrayFlatten($condition);
            }
        }
        elseif (\is_string($condition))
        {
            $conditions[0] .= " $conjuction $condition";
        }

        return $conditions;
    }

    public static function humanAttribute($attr)
    {
        $inflector = Inflector::instance();
        $inflected = $inflector->variablize($attr);
        $normal = $inflector->uncamelize($inflected);

        return \ucfirst(\str_replace('_', ' ', $normal));
    }

    public static function isOdd($number)
    {
        return $number & 1;
    }

    public static function isArray($type, $var)
    {
        switch ($type)
        {
            case 'range':
                if (\is_array($var) && (int) $var[0] < (int) $var[1])
                {
                    return true;
                }
        }

        return false;
    }

    public static function isBlank($var)
    {
        return 0 === \strlen($var);
    }

    private static $plural = [
        '/(quiz)$/i' => "$1zes",
        '/^(ox)$/i' => "$1en",
        '/([m|l])ouse$/i' => "$1ice",
        '/(matr|vert|ind)ix|ex$/i' => "$1ices",
        '/(x|ch|ss|sh)$/i' => "$1es",
        '/([^aeiouy]|qu)y$/i' => "$1ies",
        '/(hive)$/i' => "$1s",
        '/(?:([^f])fe|([lr])f)$/i' => "$1$2ves",
        '/(shea|lea|loa|thie)f$/i' => "$1ves",
        '/sis$/i' => "ses",
        '/([ti])um$/i' => "$1a",
        '/(tomat|potat|ech|her|vet)o$/i' => "$1oes",
        '/(bu)s$/i' => "$1ses",
        '/(alias)$/i' => "$1es",
        '/(octop)us$/i' => "$1i",
        '/(cris|ax|test)is$/i' => "$1es",
        '/(us)$/i' => "$1es",
        '/s$/i' => "s",
        '/$/' => "s"
    ];
    private static $singular = [
        '/(quiz)zes$/i' => "$1",
        '/(matr)ices$/i' => "$1ix",
        '/(vert|ind)ices$/i' => "$1ex",
        '/^(ox)en$/i' => "$1",
        '/(alias)es$/i' => "$1",
        '/(octop|vir)i$/i' => "$1us",
        '/(cris|ax|test)es$/i' => "$1is",
        '/(shoe)s$/i' => "$1",
        '/(o)es$/i' => "$1",
        '/(bus)es$/i' => "$1",
        '/([m|l])ice$/i' => "$1ouse",
        '/(x|ch|ss|sh)es$/i' => "$1",
        '/(m)ovies$/i' => "$1ovie",
        '/(s)eries$/i' => "$1eries",
        '/([^aeiouy]|qu)ies$/i' => "$1y",
        '/([lr])ves$/i' => "$1f",
        '/(tive)s$/i' => "$1",
        '/(hive)s$/i' => "$1",
        '/(li|wi|kni)ves$/i' => "$1fe",
        '/(shea|loa|lea|thie)ves$/i' => "$1f",
        '/(^analy)ses$/i' => "$1sis",
        '/((a)naly|(b)a|(d)iagno|(p)arenthe|(p)rogno|(s)ynop|(t)he)ses$/i' => "$1$2sis",
        '/([ti])a$/i' => "$1um",
        '/(n)ews$/i' => "$1ews",
        '/(h|bl)ouses$/i' => "$1ouse",
        '/(corpse)s$/i' => "$1",
        '/(us)es$/i' => "$1",
        '/(us|ss)$/i' => "$1",
        '/s$/i' => ""
    ];
    private static $irregular = [
        'move' => 'moves',
        'foot' => 'feet',
        'goose' => 'geese',
        'sex' => 'sexes',
        'child' => 'children',
        'man' => 'men',
        'tooth' => 'teeth',
        'person' => 'people'
    ];
    private static $uncountable = [
        'sheep',
        'fish',
        'deer',
        'series',
        'species',
        'money',
        'rice',
        'information',
        'equipment'
    ];

    public static function pluralize($string)
    {
// save some time in the case that singular and plural are the same
        if (\in_array(\strtolower($string), self::$uncountable))
        {
            return $string;
        }

// check for irregular singular forms
        foreach (self::$irregular as $pattern => $result)
        {
            $pattern = '/'.$pattern.'$/i';

            if (\preg_match($pattern, $string))
            {
                return \preg_replace($pattern, $result, $string);
            }
        }

// check for matches using regular expressions
        foreach (self::$plural as $pattern => $result)
        {
            if (\preg_match($pattern, $string))
            {
                return \preg_replace($pattern, $result, $string);
            }
        }

        return $string;
    }

    public static function singularize($string)
    {
// save some time in the case that singular and plural are the same
        if (\in_array(\strtolower($string), self::$uncountable))
        {
            return $string;
        }

// check for irregular plural forms
        foreach (self::$irregular as $result => $pattern)
        {
            $pattern = '/'.$pattern.'$/i';

            if (\preg_match($pattern, $string))
            {
                return \preg_replace($pattern, $result, $string);
            }
        }

// check for matches using regular expressions
        foreach (self::$singular as $pattern => $result)
        {
            if (\preg_match($pattern, $string))
            {
                return \preg_replace($pattern, $result, $string);
            }
        }

        return $string;
    }

    public static function pluralizeIf($count, $string)
    {
        if ($count == 1)
        {
            return $string;
        }
        else
        {
            return self::pluralize($string);
        }
    }

    public static function squeeze($char, $string)
    {
        return \preg_replace("/$char+/", $char, $string);
    }

    public static function addIrregular($singular, $plural)
    {
        self::$irregular[$singular] = $plural;
    }

    public function classify($class_name, $singularize = false)
    {
        if ($singularize)
        {
            $class_name = Utils::singularize($class_name);
        }

        $class_name = Inflector::instance()->camelize($class_name);
        return \ucfirst($class_name);
    }

// http://snippets.dzone.com/posts/show/4660
    public function arrayFlatten(array $array)
    {
        $i = 0;

        while ($i < count($array))
        {
            if (\is_array($array[$i]))
            {
                \array_splice($array, $i, 1, $array[$i]);
            }
            else
            {
                ++$i;
            }
        }
        return $array;
    }

    /**
     * Somewhat naive way to determine if an array is a hash.
     */
    public function isHash(&$array)
    {
        if (!\is_array($array))
        {
            return false;
        }

        $keys = \array_keys($array);
        return @\is_string($keys[0]) ? true : false;
    }

    /**
     * Strips a class name of any namespaces and namespace operator.
     *
     * @param string $class
     * @return string stripped class name
     * @access public
     */
    public function denamespace($class_name)
    {
        if (\is_object($class_name))
        {
            $class_name = \get_class($class_name);
        }

        if (self::hasNamespace($class_name))
        {
            $parts = \explode('\\', $class_name);
            return \end($parts);
        }
        return $class_name;
    }

    public function getNamespaces($class_name)
    {
        if (self::hasNamespace($class_name))
        {
            return \explode('\\', $class_name);
        }
        return null;
    }

    public function hasNamespace($class_name)
    {
        if (\strpos($class_name, '\\') !== false)
        {
            return true;
        }
        return false;
    }

    public function hasAbsoluteNamespace($class_name)
    {
        if (\strpos($class_name, '\\') === 0)
        {
            return true;
        }
        return false;
    }

    /**
     * Returns true if all values in $haystack === $needle
     * @param $needle
     * @param $haystack
     * @return unknown_type
     */
    public function all($needle, array $haystack)
    {
        foreach ($haystack as $value)
        {
            if ($value !== $needle)
            {
                return false;
            }
        }
        return true;
    }

    public function collect(&$enumerable, $name_or_closure)
    {
        $ret = [];

        foreach ($enumerable as $value)
        {
            if (\is_string($name_or_closure)) $ret[] = \is_array($value) ? $value[$name_or_closure]
                            : $value->$name_or_closure;
            elseif ($name_or_closure instanceof \Closure) $ret[] = $name_or_closure($value);
        }
        return $ret;
    }

    /**
     * Wrap string definitions (if any) into arrays.
     */
    public static function wrapStringsInArrays(&$strings)
    {
        if (!\is_array($strings))
        {
            $strings = [[
            $strings]];
        }
        else
        {
            foreach ($strings as &$str)
            {
                if (!\is_array($str))
                {
                    $str = [$str];
                }
            }
        }
        return $strings;
    }

}
