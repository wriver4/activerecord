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
        extends aSerialize
{

    public static $include_root = false;

    public function to_s()
    {
        return self::$include_root ? [
            strtolower(get_class($this->model)) => $this->to_a()] : $this->to_a();
    }

}
