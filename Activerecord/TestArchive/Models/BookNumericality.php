<?php

namespace Test\Models;

class BookNumericality
        extends \Activerecord\Model
{

    static $table_name = 'books';
    static $validates_numericality_of = [[
    'name']];

}