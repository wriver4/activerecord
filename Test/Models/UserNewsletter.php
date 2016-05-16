<?php

namespace Test\Activerecord;

class UserNewsletter
        extends \ActiveRecord\Model
{

    static $belong_to = array(
        array(
            'user'),
        array(
            'newsletter'),
    );

}