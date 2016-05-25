<?php

namespace Test\Models;

class Book
        extends \Activerecord\Model
{

    static $belongs_to = ['author'];
    static $has_one = [];
    static $use_custom_get_name_getter = false;

    public function upperName()
    {
        return \strtoupper($this->name);
    }

    public function name()
    {
        return \strtolower($this->name);
    }

    public function getName()
    {
        if (self::$use_custom_get_name_getter)
        {
            return \strtoupper($this->readAttribute('name'));
        }
        else
        {
            return $this->readAttribute('name');
        }
    }

    public function getUpperName()
    {
        return \strtoupper($this->name);
    }

    public function getLowerName()
    {
        return \strtolower($this->name);
    }

}