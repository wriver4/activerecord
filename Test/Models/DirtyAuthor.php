<?php

namespace Test\Models;

class DirtyAuthor
        extends \Activerecord\Model
{

    static $table = 'authors';
    static $before_save = 'before_save';

    public function beforeSave()
    {
        $this->name = 'i saved';
    }

}