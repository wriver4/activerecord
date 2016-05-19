<?php

namespace Test\Models;

class Property
        extends \Activerecord\Model
{

    static $table_name = 'property';
    static $primary_key = 'property_id';
    static $has_many = ['property_amenities',
        ['amenities',
            'through' => 'property_amenities']];

}