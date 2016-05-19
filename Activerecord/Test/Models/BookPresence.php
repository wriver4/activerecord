<?php

namespace Test\Models;

class BookPresence
        extends \Activerecord\Model
{

    static $table_name = 'books';
    static $validates_presence_of = [[
    'name']];

}