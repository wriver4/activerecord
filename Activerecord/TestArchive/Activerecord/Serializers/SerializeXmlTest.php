<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Activerecord\Test\Activerecord\Serializers;

use Activerecord\DateTime;
use Activerecord\Serializers\AbstractSerialize;
use Activerecord\Serializers\SerializeXml;
use Activerecord\Test\Helpers\DatabaseTest;

/**
 * Description of SerializeXmlTest
 *
 * @author mark weisser <mark at whizbangdevelopers.com>
 */
class SerializeXmlTest
        extends \PHPUnit_Framework_TestCase
{

    public function setUp()
    {

    }

    public function tearDown()
    {
        parent::tearDown();
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

    public function testWorksWithDatetime()
    {
        Author::find(1)->updateAttribute('created_at', new DateTime());
        $this->assertRegExp('/<updated_at>[0-9]{4}-[0-9]{2}-[0-9]{2}/',
                Author::find(1)->toXml());
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

}