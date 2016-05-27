<?php

namespace Test;

use Activerecord\Utils;

class UtilsTest
        extends \PHPUnit_Framework_TestCase
{

    public function setUp()
    {

        $this->object_array = [null,
            null];
        $this->object_array[0] = new \stdClass();
        $this->object_array[0]->a = "0a";
        $this->object_array[0]->b = "0b";
        $this->object_array[1] = new \stdClass();
        $this->object_array[1]->a = "1a";
        $this->object_array[1]->b = "1b";

        $this->array_hash = array(
            array(
                "a" => "0a",
                "b" => "0b"),
            array(
                "a" => "1a",
                "b" => "1b"));
    }

    public function tearDown()
    {

    }

    public function testCollectWithArrayOfObjectsUsingClosure()
    {
        $utils = new Utils();
        $this->assertEquals(["0a",
            "1a"],
                $utils->collect($this->object_array,
                        function($obj)
                {
                    return $obj->a;
                }));
    }

    public function testCollectWithArrayOfObjectsUsingString()
    {
        $utils = new Utils();
        $this->assertEquals(["0a",
            "1a"], $utils->collect($this->object_array, "a"));
    }

    public function testCollectWithArrayHashUsingClosure()
    {
        $utils = new Utils();
        $this->assertEquals(["0a",
            "1a"],
                $utils->collect($this->array_hash,
                        function($item)
                {
                    return $item["a"];
                }));
    }

    public function testCollectWithArrayHashUsingString()
    {
        $utils = new Utils();
        $this->assertEquals(["0a",
            "1a"], $utils->collect($this->array_hash, "a"));
    }

    public function testArrayFlatten()
    {
        $utils = new Utils();
        $this->assertEquals([], $utils->arrayFlatten([]));
        $this->assertEquals([1], $utils->arrayFlatten([1]));
        $this->assertEquals([1], $utils->arrayFlatten([[
                1]]));
        $this->assertEquals([1,
            2], $utils->arrayFlatten([[
                1,
                2]]));
        $this->assertEquals([1,
            2],
                $utils->arrayFlatten([[
                1],
                    2]));
        $this->assertEquals([
            1,
            2], $utils->arrayFlatten([1,
                    [2]]));
        $this->assertEquals([1,
            2,
            3],
                $utils->arrayFlatten([1,
                    [2],
                    3]));
        $this->assertEquals([1,
            2,
            3,
            4],
                $utils->arrayFlatten([
                    1,
                    [2,
                        3],
                    4]));
        $this->assertEquals([
            1,
            2,
            3,
            4,
            5,
            6],
                $utils->arrayFlatten([1,
                    [2,
                        3],
                    4,
                    [5,
                        6]]));
    }

    public function testAll()
    {
        $utils = new Utils();
        $this->assertTrue($utils->all(null, [null,
                    null]));
        $this->assertTrue($utils->all(1, [1,
                    1]));
        $this->assertFalse($utils->all(1, [1,
                    '1']));
        $this->assertFalse($utils->all(null, ['',
                    null]));
    }

    /*
      public function testClassify()
      {
      $bad_class_names = [
      'ubuntu_rox',
      'stop_the_Snake_Case',
      'CamelCased',
      'camelCased'];
      $good_class_names = [
      'ubuntuRox',
      'stopTheSnakeCase',
      'camelCased',
      'camelCased'];

      $class_names = [];
      foreach ($bad_class_names as $s)
      {
      $class_names[] = Utils::classify($s);
      }

      $this->assertEquals($class_names, $good_class_names);
      }

      public function testClassifySingularize()
      {
      $bad_class_names = ['events',
      'stop_the_Snake_Cases',
      'angry_boxes',
      'Mad_Sheep_herders',
      'happy_People'];
      $good_class_names = [
      'Event',
      'stopTheSnakeCase',
      'angryBox',
      'madSheepHerder',
      'happyPerson'];

      $class_names = [];
      foreach ($bad_class_names as $s)
      {
      $class_names[] = Utils::classify($s, true);
      }

      $this->assertEquals($class_names, $good_class_names);
      }
     */

    public function testSingularize()
    {
        $utils = new Utils();
        $this->assertEquals('order_status', $utils->singularize('order_status'));
        $this->assertEquals('order_status',
                $utils->singularize('order_statuses'));
        $this->assertEquals('os_type', $utils->singularize('os_type'));
        $this->assertEquals('os_type', $utils->singularize('os_types'));
        $this->assertEquals('photo', $utils->singularize('photos'));
        $this->assertEquals('pass', $utils->singularize('pass'));
        $this->assertEquals('pass', $utils->singularize('passes'));
    }

    public function testWrapStringsInArrays()
    {
        $utils = new Utils();
        $x = ['1',
            ['2']];
        $this->assertEquals([[
        '1'],
            ['2']], $utils->wrapStringsInArrays($x));

        $x = '1';
        $this->assertEquals([[
        '1']], $utils->wrapStringsInArrays($x));
    }

}