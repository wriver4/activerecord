<?php

namespace Test\Models;

class BookExclusion
        extends \Activerecord\Model
{

    static $table = 'books';
    public static $validates_exclusion_of = array(
        array(
            'name',
            'in' => array(
                'blah',
                'alpha',
                'bravo'))
    );

}