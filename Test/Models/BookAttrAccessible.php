<?php

namespace Test\Models;

class BookAttrAccessible
        extends \Activerecord\Model
{

    static $pk = 'book_id';
    static $table_name = 'books';
    static $attr_accessible = ['author_id'];
    static $attr_protected = ['book_id'];

}