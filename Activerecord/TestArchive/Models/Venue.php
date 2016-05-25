<?php

namespace Test\Models;

class Venue
        extends \Activerecord\Model
{

    static $use_custom_get_state_getter = false;
    static $use_custom_set_state_setter = false;
    static $has_many = [
        'events',
        [
            'hosts',
            'through' => 'events']];
    static $has_one;
    static $alias_attribute = [
        'marquee' => 'name',
        'mycity' => 'city'
    ];

    public function getState()
    {
        if (self::$use_custom_get_state_getter)
        {
            return \strtolower($this->read_attribute('state'));
        }
        else
        {
            return $this->read_attribute('state');
        }
    }

    public function setState($value)
    {
        if (self::$use_custom_set_state_setter)
        {
            return $this->assignAttribute('state', $value.'#');
        }
        else
        {
            return $this->assignAttribute('state', $value);
        }
    }

}