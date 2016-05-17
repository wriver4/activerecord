<?php

namespace Test\Activerecord;

class DateFormatTest
        extends \Test\Helpers\DatabaseTest
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