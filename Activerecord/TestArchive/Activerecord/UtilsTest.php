<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Activerecord\Test\Activerecord;

//use ActiveRecord as AR;
use Activerecord\Utils;

/**
 * Description of UtilsTest
 *
 * @author mark weisser <mark at whizbangdevelopers.com>
 */
class UtilsTest
        extends \PHPUnit_Framework_TestCase
{

    public function setUp()
    {
        $this->object_array = [null,
            null];
        $this->object_array[0] = new stdClass();
        $this->object_array[0]->a = "0a";
        $this->object_array[0]->b = "0b";
        $this->object_array[1] = new stdClass();
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
        $this->assertEquals(["0a",
            "1a"],
                Utils::collect($this->object_array,
                        function($obj)
                {
                    return $obj->a;
                }));
    }

    public function testCollectWithArrayOfObjectsUsingString()
    {
        $this->assertEquals(["0a",
            "1a"], Utils::collect($this->object_array, "a"));
    }

    public function testCollectWithArrayHashUsingClosure()
    {
        $this->assertEquals(["0a",
            "1a"],
                Utils::collect($this->array_hash,
                        function($item)
                {
                    return $item["a"];
                }));
    }

    public function testCollectWithArrayHashUsingString()
    {
        $this->assertEquals(["0a",
            "1a"], Utils::collect($this->array_hash, "a"));
    }

    public function testArrayFlatten()
    {
        $this->assertEquals([], Utils::arrayFlatten([]));
        $this->assertEquals([1], Utils::arrayFlatten([1]));
        $this->assertEquals([1], Utils::arrayFlatten([[
                1]]));
        $this->assertEquals([1,
            2], Utils::arrayFlatten([[
                1,
                2]]));
        $this->assertEquals([1,
            2],
                Utils::arrayFlatten([[
                1],
                    2]));
        $this->assertEquals([
            1,
            2], Utils::arrayFlatten([1,
                    [2]]));
        $this->assertEquals([1,
            2,
            3],
                Utils::arrayFlatten([1,
                    [2],
                    3]));
        $this->assertEquals([1,
            2,
            3,
            4],
                Utils::arrayFlatten([
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
                Utils::arrayFlatten([1,
                    [2,
                        3],
                    4,
                    [5,
                        6]]));
    }

    public function testAll()
    {
        $this->assertTrue(Utils::all(null, [null,
                    null]));
        $this->assertTrue(Utils::all(1, [1,
                    1]));
        $this->assertFalse(Utils::all(1, [1,
                    '1']));
        $this->assertFalse(Utils::all(null, ['',
                    null]));
    }

    public function testClassify()
    {
        $bad_class_names = [
            'ubuntu_rox',
            'stop_the_Snake_Case',
            'CamelCased',
            'camelCased'];
        $good_class_names = [
            'UbuntuRox',
            'StopTheSnakeCase',
            'CamelCased',
            'CamelCased'];

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
            'StopTheSnakeCase',
            'AngryBox',
            'MadSheepHerder',
            'HappyPerson'];

        $class_names = [];
        foreach ($bad_class_names as $s)
        {
            $class_names[] = Utils::classify($s, true);
        }

        $this->assertEquals($class_names, $good_class_names);
    }

    public function testSingularize()
    {
        $this->assertEquals('order_status', Utils::singularize('order_status'));
        $this->assertEquals('order_status', Utils::singularize('order_statuses'));
        $this->assertEquals('os_type', Utils::singularize('os_type'));
        $this->assertEquals('os_type', Utils::singularize('os_types'));
        $this->assertEquals('photo', Utils::singularize('photos'));
        $this->assertEquals('pass', Utils::singularize('pass'));
        $this->assertEquals('pass', Utils::singularize('passes'));
    }

    public function testWrapStringsInArrays()
    {
        $x = ['1',
            ['2']];
        $this->assertEquals([[
        '1'],
            ['2']], Utils::wrapStringsInArrays($x));

        $x = '1';
        $this->assertEquals([[
        '1']], Utils::wrapStringsInArrays($x));
    }

}