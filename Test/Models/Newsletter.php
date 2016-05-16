<?php

namespace Test\Models;

class Newsletter
        extends \Activerecord\Model
{

    static $has_many = [[
    'user_newsletters'],
        ['users',
            'through' => 'user_newsletters'],];

}