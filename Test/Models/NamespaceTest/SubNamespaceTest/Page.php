<?php

namespace Test\Models\NamespaceTest\SubNamespaceTest;

class Page
        extends \Activerecord\Model
{

    static $belong_to = [[
    'book',
    'class_name' => '\NamespaceTest\Book'],
    ];

}