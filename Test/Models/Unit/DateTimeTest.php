<?php

namespace Test;

use Activerecord\DateTime;
use Activerecord\Exceptions\ExceptionDatabase;
use Test\Models\Author;

/**
 * Description of DateTimeTest
 *
 * @author mark weisser <mark at whizbangdevelopers.com>
 */
class DateTimeTest
        extends \PHPUnit_Framework_TestCase
{

    public function setUp()
    {
        $this->date = new DateTime();
    }

    public function tearDown()
    {

    }

    private function assertDirtifies($method /* , method params, ... */)
    {
        try
        {
            $model = new Author();
        }
        catch (ExceptionDatabase $e)
        {
            $this->markTestSkipped('failed to connect. '.$e->getMessage());
        }
        $datetime = new DateTime();
        $datetime->attributeOf($model, 'some_date');

        $args = \func_get_args();
        \array_shift($args);

        \call_user_func_array(array(
            $datetime,
            $method), $args);
        $this->assertArrayHasKeys('some_date', $model->dirtyAttributes());
    }

    public function testSetIsoDate()
    {
        $a = new \DateTime();
        $a->setISODate(2001, 1);

        $b = new DateTime();
        $b->setISODate(2001, 1);

        $this->assertEquals($a, $b);
    }

    public function testSetTime()
    {
        $a = new \DateTime();
        $a->setTime(1, 1);

        $b = new DateTime();
        $b->setTime(1, 1);

        $this->assertEquals($a, $b);
    }

    public function testGetFormatWithFriendly()
    {
        $this->assertEquals('Y-m-d H:i:s', DateTime::getFormat('db'));
    }

    public function testGetFormatWithFormat()
    {
        $this->assertEquals('Y-m-d', DateTime::getFormat('Y-m-d'));
    }

    public function testGetFormatWithNull()
    {
        $this->assertEquals(\DateTime::RFC2822, DateTime::getFormat());
    }

    public function testFormat()
    {
        $this->assertTrue(\is_string($this->date->format()));
        $this->assertTrue(\is_string($this->date->format('Y-m-d')));
    }

    public function testFormatByFriendlyName()
    {
        $d = date(DateTime::getFormat('db'));
        $this->assertEquals($d, $this->date->format('db'));
    }

    public function testFormatByCustomFormat()
    {
        $format = 'Y/m/d';
        $this->assertEquals(date($format), $this->date->format($format));
    }

    public function testToString()
    {
        $this->assertEquals(\date(DateTime::getFormat()), "".$this->date);
    }

    public function testCreateFromFormatErrorHandling()
    {
        $d = DateTime::createFromFormat('H:i:s Y-d-m', '!!!');
        $this->assertFalse($d);
    }

    public function testCreateFromFormatWithoutTz()
    {
        $d = DateTime::createFromFormat('H:i:s Y-d-m', '03:04:05 2000-02-01');
        $this->assertEquals(new DateTime('2000-01-02 03:04:05'), $d);
    }

    public function testCreateFromFormatWithTz()
    {
        $d = DateTime::createFromFormat('Y-m-d H:i:s', '2000-02-01 03:04:05',
                        new \DateTimeZone('Etc/GMT-10'));
        $this->assertEquals(new DateTime('2000-01-31 17:04:05'), $d);
    }

    public function format($format = null)
    {

    }

    public static function createFromFormat($format, $time)
    {

    }

}