<?php

class BookInclusion
        extends \Activerecord\Model
{

    static $table = 'books';
    public static $validates_inclusion_of = [[
    'name',
    'in' => [
        'blah',
        'tanker',
        'shark']]
    ];

}