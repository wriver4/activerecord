<?php

class BookInclusion
        extends \Activerecord\Model
{

    static $table = 'books';
    public static $validates_inclusion_of = array(
        array(
            'name',
            'in' => array(
                'blah',
                'tanker',
                'shark'))
    );

}