<?php

namespace Test\Models;

class Amenity
        extends \Activerecord\Model
{

    static $table_name = 'amenities';
    static $primary_key = 'amenity_id';
    static $has_many = ['property_amenities'];

}