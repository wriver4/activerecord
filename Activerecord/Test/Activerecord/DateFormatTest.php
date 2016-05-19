<?php

namespace Activerecord\Test\Activerecord;

use Activerecord\Test\Helpers\DatabaseTest;

class DateFormatTest
        extends DatabaseTest
{

    public function testDatefieldGetsConvertedToArDatetime()
    {
        //make sure first author has a date
        $author = Author::first();
        $author->some_date = new DateTime();
        $author->save();

        $author = Author::first();
        $this->assertIsA("Activerecord\\DateTime", $author->some_date);
    }

}