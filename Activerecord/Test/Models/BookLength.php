<?php

namespace Test\Models;

class BookLength
        extends \Activerecord\Model
{

    static $table = 'books';
    static $validates_length_of = [];

}