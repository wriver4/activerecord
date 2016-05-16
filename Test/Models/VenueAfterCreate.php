<?php

namespace Test\Models;

class VenueAfterCreate
        extends \Activerecord\Model
{

    static $table_name = 'venues';
    static $after_create = ['change_name_after_create_if_name_is_change_me'];

    public function changeNameAfterCreateIfNameIsChangeMe()
    {
        if ($this->name == 'change me')
        {
            $this->name = 'changed!';
            $this->save();
        }
    }

}