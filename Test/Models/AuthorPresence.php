<?php

namespace Test\Models;

class AuthorPresence
        extends \Activerecord\Model
{

    static $table_name = 'authors';
    static $validates_presence_of = [[
    'some_date']];

}