<?php

namespace Test\Models;

class Host
        extends \Activerecord\Model
{

    static $has_many = ['events',
        ['venues',
            'through' => 'events']];

}