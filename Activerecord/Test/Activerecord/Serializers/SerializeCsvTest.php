<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Activerecord\Test\Activerecord\Serializers;

use Activerecord\DateTime;
use Activerecord\Serializers\AbstractSerialize;
use Activerecord\Test\Helpers\DatabaseTest;

/**
 * Description of SerializeCsvTest
 *
 * @author mark weisser <mark at whizbangdevelopers.com>
 */
class SerializeCsvTest
        extends DatabaseTest
{

    public function setUp()
    {

    }

    public function tearDown()
    {
        parent::tearDown();
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