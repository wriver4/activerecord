<?php

namespace Test\Models;

class Event
        extends \Activerecord\Model
{

    static $belongs_to = array(
        'host',
        'venue'
    );
    static $delegate = array(
        array(
            'state',
            'address',
            'to' => 'venue'),
        array(
            'name',
            'to' => 'host',
            'prefix' => 'woot')
    );

}