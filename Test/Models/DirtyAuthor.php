<?php

namespace Test\Models;

class DirtyAuthor
        extends \Activerecord\Model
{

    static $table = 'authors';
    static $before_save = 'before_save';

    public function before_save()
    {
        $this->name = 'i saved';
    }

}