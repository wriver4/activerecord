<?php

namespace Test\Models;

class Newsletter
        extends \Activerecord\Model
{

    static $has_many = array(
        array(
            'user_newsletters'),
        array(
            'users',
            'through' => 'user_newsletters'),
    );

}