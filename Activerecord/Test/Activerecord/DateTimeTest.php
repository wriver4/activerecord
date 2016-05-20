<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Activerecord\Test\Activerecord;

use Activerecord\DateTime as DateTime;
use Activerecord\Exceptions\ExceptionDatabase;

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
        // $this->original_format = DateTime::$DEFAULT_FORMAT;
    }

    public function tearDown()
    {
        // DateTime::$DEFAULT_FORMAT = $this->original_format;
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
        $this->assertHasKeys('some_date', $model->dirtyAttributes());
    }

    public function testShouldFlagTheAttributeDirty()
    {
        // $interval = new DateInterval('PT1S');
        // $timezone = new DateTimeZone('America/New_York');
        //  $this->assert_dirtifies('setDate', 2001, 1, 1);
        //  $this->assert_dirtifies('setISODate', 2001, 1);
        //   $this->assert_dirtifies('setTime', 1, 1);
        //  $this->assert_dirtifies('setTimestamp', 1);
        //   $this->assert_dirtifies('setTimezone', $timezone);
        //   $this->assert_dirtifies('modify', '+1 day');
        //   $this->assert_dirtifies('add', $interval);
        //  $this->assert_dirtifies('sub', $interval);
    }

    public function testSetIsoDate()
    {
        $a = new \DateTime();
        $a->setISODate(2001, 1);

        $b = new DateTime();
        $b->setISODate(2001, 1);

        //  $this->assertDatetimeEquals($a, $b);
    }

    public function testSetTime()
    {
        $a = new \DateTime();
        $a->setTime(1, 1);

        $b = new DateTime();
        $b->setTime(1, 1);

        //$this->assertDatetimeEquals($a, $b);
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

    public function testFormatUsesDefault()
    {
        // $d = \date(DateTime::$FORMATS[DateTime::$DEFAULT_FORMAT]);
        // $this->assertEquals($d, $this->date->format());
    }

    public function testAllFormats()
    {
        //  foreach (DateTime::$FORMATS as $name => $format)
        //         $this->assertEquals(date($format), $this->date->format($name));
    }

    public function testChangeDefaultFormatToFormatString()
    {
        // DateTime::$DEFAULT_FORMAT = 'H:i:s';
        // $this->assertEquals(\date(DateTime::$DEFAULT_FORMAT),
        //         $this->date->format());
    }

    public function testChangeDefaultFormatToFriently()
    {
        // DateTime::$DEFAULT_FORMAT = 'short';
        // $this->assertEquals(\date(DateTime::$FORMATS['short']),
        //         $this->date->format());
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

}