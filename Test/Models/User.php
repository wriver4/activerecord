<?php

namespace Test\Models;

class User
        extends \ActiveRecord\Model
{

    static $has_many = [[
    'user_newsletters'],
        ['newsletters',
            'through' => 'user_newsletters']];

}