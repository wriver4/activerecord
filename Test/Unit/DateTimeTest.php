<?php

namespace Test;

use Activerecord\DateTime;
use Activerecord\Exceptions\ExceptionDatabase;
use Test\Models\Author;

class DateTimeTest
        extends \PHPUnit_Framework_TestCase
{

    public function setUp()
    {
        $this->date = new DateTime();
        //require_once 'bootstrap.php';
    }

    public function tearDown()
    {

    }

    private function getModel()
    {
        try
        {
            $model = new Author();
        }
        catch (DatabaseException $e)
        {
            $this->markTestSkipped('failed to connect. '.$e->getMessage());
        }
        return $model;
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

    public function format($format = null)
    {

    }

    public static function createFromFormat($format, $time)
    {

    }

    /*
      public function testClone()
      {
      $model = $this->getModel();
      $model_attribute = 'some_date';

      $datetime = new DateTime();
      $datetime->attributeOf($model, $model_attribute);

      $cloned_datetime = clone $datetime;

      // Assert initial state
      $this->assertFalse($model->attributeIsDirty($model_attribute));

      $cloned_datetime->add(new DateInterval('PT1S'));

      // Assert that modifying the cloned object didn't flag the model
      $this->assertFalse($model->attributeIsDirty($model_attribute));

      $datetime->add(new DateInterval('PT1S'));

      // Assert that modifying the model-attached object did flag the model
      $this->assertTrue($model->attributeIsDirty($model_attribute));

      // Assert that the dates are equal but not the same instance
      $this->assertEquals($datetime, $cloned_datetime);
      $this->assertNotSame($datetime, $cloned_datetime);
      }
     */
}