<?php

namespace Test\Models;

class User
        extends \ActiveRecord\Model
{

    static $has_many = array(
        array(
            'user_newsletters'),
        array(
            'newsletters',
            'through' => 'user_newsletters')
    );

}