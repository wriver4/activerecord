<?php

namespace Test\Models;

use Test\Activerecord\NotModel;

class AuthorWithNonModelRelationship
        extends \Activerecord\Model
{

    static $pk = 'id';
    static $table_name = 'authors';
    static $has_many = array(
        array(
            'books',
            'class_name' => 'NotModel'));

}