<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Activerecord\Test\Activerecord\Serializers;

use Activerecord\Serializers\AbstractSerialize;
use Activerecord\DateTime;
use Activerecord\Serializers\SerializeArray;
use Activerecord\Test\Helpers\DatabaseTest;

/**
 * Description of SerializeArrayTest
 *
 * @author mark weisser <mark at whizbangdevelopers.com>
 */
class SerializeArrayTest
        extends DatabaseTest
{

    public function setUp()
    {

    }

    public function tearDown()
    {
        parent::tearDown();
        SerializeArray::$include_root = false;
    }

// this is in json also
    public function testSerializeArray($options = [], $model = null)
    {
        if (!$model)
        {
            $model = Book::find(1);
        }

        $s = new SerializeJson($model, $options);
        return $s->toArray();
    }

    public function testOnly()
    {
        $this->assertHasKeys('name', 'special',
                $this->testSerializeArray([
                    'only' => [
                        'name',
                        'special']]));
    }

    public function testOnlyNotArray()
    {
        $this->assertHasKeys('name',
                $this->testSerializeArray(['only' => 'name']));
    }

    public function testOnlyShouldOnlyApplyToAttributes()
    {
        $this->assertHasKeys('name', 'author',
                $this->testSerializeArray([
                    'only' => 'name',
                    'include' => 'author']));
        $this->assertHasKeys('book_id', 'upper_name',
                $this->testSerializeArray([
                    'only' => 'book_id',
                    'methods' => 'upper_name']));
    }

    public function testOnlyOverridesExcept()
    {
        $this->assertHasKeys('name',
                $this->testSerializeArray([
                    'only' => 'name',
                    'except' => 'name']));
    }

    public function testExcept()
    {
        $this->assertDoesNotHasKeys('name', 'special',
                $this->testSerializeArray([
                    'except' => [
                        'name',
                        'special']]));
    }

    public function testExceptTakesAString()
    {
        $this->assertDoesNotHasKeys('name',
                $this->testSerializeArray(['except' => 'name']));
    }

    public function testMethods()
    {
        $a = $this->testSerializeArray(['methods' => ['upper_name']]);
        $this->assertEquals('ANCIENT ART OF MAIN TANKING', $a['upper_name']);
    }

    public function testMethodsTakesAString()
    {
        $a = $this->testSerializeArray(['methods' => 'upper_name']);
        $this->assertEquals('ANCIENT ART OF MAIN TANKING', $a['upper_name']);
    }

    // methods added last should we shuld have value of the method in our json
    // rather than the regular attribute value
    public function testMethodsMethodSameAsAttribute()
    {
        $a = $this->testSerializeArray(['methods' => 'name']);
        $this->assertEquals('ancient art of main tanking', $a['name']);
    }

    public function testInclude()
    {
        $a = $this->testSerializeArray(['include' => ['author']]);
        $this->assertHasKeys('parent_author_id', $a['author']);
    }

    public function testIncludeNestedWithNestedOptions()
    {
        $a = $this->testSerializeArray(['include' => ['events' => ['except' => 'title',
                    'include' => ['host' => ['only' => 'id']]]]], Host::find(4));

        $this->assertEquals(3, \count($a['events']));
        $this->assertDoesNotHasKeys('title', $a['events'][0]);
        $this->assertEquals(['id' => 4], $a['events'][0]['host']);
    }

    public function testDatetimeValuesGetConvertedToStrings()
    {
        $now = new DateTime();
        $a = $this->testSerializeArray([
            'only' => 'created_at'], new Author(['created_at' => $now]));
        $this->assertEquals($now->format(AbstractSerialize::$DATETIME_FORMAT),
                $a['created_at']);
    }

    public function testToArray()
    {
        $book = Book::find(1);
        $array = $book->toArray();
        $this->assertEquals($book->attributes(), $array);
    }

    public function testToArrayIncludeRoot()
    {
        SerializeArray::$include_root = true;
        $book = Book::find(1);
        $array = $book->toArray();
        $book_attributes = ['book' => $book->attributes()];
        $this->assertEquals($book_attributes, $array);
    }

    public function testToArrayExcept()
    {
        $book = Book::find(1);
        $array = $book->toArray(['except' => ['special']]);
        $book_attributes = $book->attributes();
        unset($book_attributes['special']);
        $this->assertEquals($book_attributes, $array);
    }

}