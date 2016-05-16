<?php

namespace Test\Models;

class PropertyAmenity
        extends \Activerecord\Model
{

    static $table_name = 'property_amenities';
    static $primary_key = 'id';
    static $belongs_to = [
        'amenity',
        'property'];

}