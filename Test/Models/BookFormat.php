<?php

namespace Test\Models;

class BookFormat
        extends \Activerecord\Model
{

    static $table = 'books';
    static $validates_format_of = array(
        array(
            'name')
    );

}