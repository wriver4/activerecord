<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Activerecord\Test\Activerecord\Serializers;

/**
 * Description of SerializeJsonTest
 *
 * @author mark weisser <mark at whizbangdevelopers.com>
 */
class SerializeJsonTest
        extends \PHPUnit_Framework_TestCase
{

    public function setUp()
    {

    }

    public function tearDown()
    {
        parent::tearDown();
        SerializeJson::$include_root = false;
    }

// Tis is in array also
    public function testSerializeArray($options = [], $model = null)
    {
        if (!$model)
        {
            $model = Book::find(1);
        }

        $s = new SerializeJson($model, $options);
        return $s->toArray();
    }

    public function testToJson()
    {
        $book = Book::find(1);
        $json = $book->toJson();
        $this->assertEquals($book->attributes(), (array) \json_decode($json));
    }

    public function testToJsonIncludeRoot()
    {
        SerializeJson::$include_root = true;
        $this->assertNotNull(\json_decode(Book::find(1)->toJson())->book);
    }

    public function testWorksWithDatetime()
    {
        Author::find(1)->updateAttribute('created_at', new DateTime());
        $this->assertRegExp('/"updated_at":"[0-9]{4}-[0-9]{2}-[0-9]{2}/',
                Author::find(1)->toJson());
    }

}