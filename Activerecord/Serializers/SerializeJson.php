<?php
/**
 * @package Activerecord
 */

namespace Activerecord\Serializers;

/**
 * JSON serializer.
 *
 * @package Activerecord
 */
class SerializeJson
        extends SerializeArray
{

    public static $include_root = false;

    public function toString()
    {
        parent::$include_root = self::$include_root;
        return \json_encode(parent::toString());
    }

}
