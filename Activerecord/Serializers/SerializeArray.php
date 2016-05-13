<?php
/**
 * @package Activerecord
 */

namespace Activerecord\Serializers;

/**
 * Array serializer.
 *
 * @package Activerecord
 */
class SerializeArray
        extends AbstractSerialize
{

    public static $include_root = false;

    public function toString()
    {
        return self::$include_root ? [
            \strtolower(\get_class($this->model)) => $this->toArray()] : $this->toArray();
    }

}
