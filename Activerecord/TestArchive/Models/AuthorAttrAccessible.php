<?php

namespace Test\Models;

class AuthorAttrAccessible
        extends \Activerecord\Model
{

    static $pk = 'author_id';
    static $table_name = 'authors';
    static $has_many = [[
    'books',
    'class_name' => 'BookAttrProtected',
    'foreign_key' => 'author_id',
    'primary_key' => 'book_id']];
    static $has_one = [[
    'parent_author',
    'class_name' => 'AuthorAttrAccessible',
    'foreign_key' => 'parent_author_id',
    'primary_key' => 'author_id']
    ];
    static $belongs_to = [];
    // No attributes should be accessible
    static $attr_accessible = [null];

}