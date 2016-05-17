<?php

namespace Test\Activerecord;

use Activerecord\DateTime;
use Activerecord\Serializers\AbstractSerialize;
use Activerecord\Serializers\SerializeArray;
use Activerecord\Serializers\SerializeJson;

class SerializationTest
        extends \Test\Helpers\DatabaseTest
{

    public function tearDown()
    {
        parent::tearDown();
        SerializeArray::$include_root = false;
        SerializeJson::$include_root = false;
    }

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

    public function testToXmlInclude()
    {
        $xml = Host::find(4)->toXml(array(
            'include' => 'events'));
        $decoded = \get_object_vars(new SimpleXMLElement($xml));

        $this->assertEquals(3, \count($decoded['events']->event));
    }

    public function testToXml()
    {
        $book = Book::find(1);
        $this->assertEquals($book->attributes(),
                \get_object_vars(new SimpleXMLElement($book->toXml())));
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

    public function testWorksWithDatetime()
    {
        Author::find(1)->updateAttribute('created_at', new DateTime());
        $this->assertRegExp('/<updated_at>[0-9]{4}-[0-9]{2}-[0-9]{2}/',
                Author::find(1)->toXml());
        $this->assertRegExp('/"updated_at":"[0-9]{4}-[0-9]{2}-[0-9]{2}/',
                Author::find(1)->toJson());
    }

    public function testToXmlSkipInstruct()
    {
        $this->assertSame(false,
                \strpos(Book::find(1)->toXml(['skip_instruct' => true]),
                        '<?xml version'));
        $this->assertSame(0,
                \strpos(Book::find(1)->toXml(['skip_instruct' => false]),
                        '<?xml version'));
    }

    public function testOnlyMethod()
    {
        $this->assertContains('<sharks>lasers</sharks>',
                Author::first()->toXml(['only_method' => 'return_something']));
    }

    public function testToCsv()
    {
        $book = Book::find(1);
        $this->assertEquals('1,1,2,"Ancient Art of Main Tanking",0,0',
                $book->toCsv());
    }

    public function testToCsvOnlyHeader()
    {
        $book = Book::find(1);
        $this->assertEquals('book_id,author_id,secondary_author_id,name,numeric_test,special',
                $book->toCsv(['only_header' => true])
        );
    }

    public function testToCsvOnlyMethod()
    {
        $book = Book::find(1);
        $this->assertEquals('2,"Ancient Art of Main Tanking"',
                $book->toCsv([
                    'only' => [
                        'name',
                        'secondary_author_id']]));
    }

    public function testToCsvOnlyMethodOnHeader()
    {
        $book = Book::find(1);
        $this->assertEquals('secondary_author_id,name',
                $book->toCsv(['only' => ['secondary_author_id',
                        'name'],
                    'only_header' => true])
        );
    }

    public function testToCsvWithCustomDelimiter()
    {
        $book = Book::find(1);
        SerializeCsv::$delimiter = ';';
        $this->assertEquals('1;1;2;"Ancient Art of Main Tanking";0;0',
                $book->toCsv());
    }

    public function testToCsvWithCustomEnclosure()
    {
        $book = Book::find(1);
        SerializeCsv::$delimiter = ',';
        SerializeCsv::$enclosure = "'";
        $this->assertEquals("1,1,2,'Ancient Art of Main Tanking',0,0",
                $book->toCsv());
    }

}