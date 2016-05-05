<?php

namespace Activerecord;

use Activerecord\Inflector;
use Activerecord\Utils;

/**
 * @package Activerecord
 */
class StandardInflector
        extends Inflector
{

    public function tableize($s)
    {
        return Utils::pluralize(strtolower($this->underscorify($s)));
    }

    public function variablize($s)
    {
        return str_replace([
            '-',
            ' '], [
            '_',
            '_'], strtolower(trim($s)));
    }

}
