<?php

namespace foo\bar\biz;

class User
        extends \Activerecord\Model
{

    static $has_many = array(
        array(
            'user_newsletters'),
        array(
            'newsletters',
            'through' => 'user_newsletters')
    );

}

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

class UserNewsletter
        extends \Activerecord\Model
{

    static $belong_to = array(
        array(
            'user'),
        array(
            'newsletter'),
    );

}