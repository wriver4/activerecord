<?php

namespace Test\Models;

class BookSize
        extends \Activerecord\Model
{

    static $table = 'books';
    static $validates_size_of = [];

}