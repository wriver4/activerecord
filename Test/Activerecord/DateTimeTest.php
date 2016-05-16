<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Test\Activerecord;

use Activerecord\DateTime as DateTime;
use Activerecord\Exceptions\ExceptionDatabase;

/**
 * Description of DateTimeTest
 *
 * @author mark weisser <mark at whizbangdevelopers.com>
 */
class DateTimeTest
        extends PHPUnit_Framework_TestCase
{

    /**
     * @var \RemoteWebDriver
     */
    protected $webDriver;

    public function setUp()
    {
        $this->date = new DateTime();
        $this->original_format = DateTime::$DEFAULT_FORMAT;
    }

    public function tearDown()
    {
        DateTime::$DEFAULT_FORMAT = $this->original_format;
    }

    private function assert_dirtifies($method /* , method params, ... */)
    {
        try
        {
            $model = new Author();
        }
        catch (ExceptionDatabase $e)
        {
            $this->mark_test_skipped('failed to connect. '.$e->getMessage());
        }
        $datetime = new DateTime();
        $datetime->attribute_of($model, 'some_date');

        $args = func_get_args();
        array_shift($args);

        call_user_func_array(array(
            $datetime,
            $method), $args);
        $this->assert_has_keys('some_date', $model->dirty_attributes());
    }

    public function test_should_flag_the_attribute_dirty()
    {
        $interval = new DateInterval('PT1S');
        $timezone = new DateTimeZone('America/New_York');
        $this->assert_dirtifies('setDate', 2001, 1, 1);
        $this->assert_dirtifies('setISODate', 2001, 1);
        $this->assert_dirtifies('setTime', 1, 1);
        $this->assert_dirtifies('setTimestamp', 1);
        $this->assert_dirtifies('setTimezone', $timezone);
        $this->assert_dirtifies('modify', '+1 day');
        $this->assert_dirtifies('add', $interval);
        $this->assert_dirtifies('sub', $interval);
    }

    public function test_set_iso_date()
    {
        $a = new \DateTime();
        $a->setISODate(2001, 1);

        $b = new DateTime();
        $b->setISODate(2001, 1);

        $this->assert_datetime_equals($a, $b);
    }

    public function test_set_time()
    {
        $a = new \DateTime();
        $a->setTime(1, 1);

        $b = new DateTime();
        $b->setTime(1, 1);

        $this->assert_datetime_equals($a, $b);
    }

    public function test_get_format_with_friendly()
    {
        $this->assert_equals('Y-m-d H:i:s', DateTime::get_format('db'));
    }

    public function test_get_format_with_format()
    {
        $this->assert_equals('Y-m-d', DateTime::get_format('Y-m-d'));
    }

    public function test_get_format_with_null()
    {
        $this->assert_equals(\DateTime::RFC2822, DateTime::get_format());
    }

    public function test_format()
    {
        $this->assert_true(is_string($this->date->format()));
        $this->assert_true(is_string($this->date->format('Y-m-d')));
    }

    public function test_format_by_friendly_name()
    {
        $d = date(DateTime::get_format('db'));
        $this->assert_equals($d, $this->date->format('db'));
    }

    public function test_format_by_custom_format()
    {
        $format = 'Y/m/d';
        $this->assert_equals(date($format), $this->date->format($format));
    }

    public function test_format_uses_default()
    {
        $d = date(DateTime::$FORMATS[DateTime::$DEFAULT_FORMAT]);
        $this->assert_equals($d, $this->date->format());
    }

    public function test_all_formats()
    {
        foreach (DateTime::$FORMATS as $name => $format)
                $this->assert_equals(date($format), $this->date->format($name));
    }

    public function test_change_default_format_to_format_string()
    {
        DateTime::$DEFAULT_FORMAT = 'H:i:s';
        $this->assert_equals(date(DateTime::$DEFAULT_FORMAT),
                $this->date->format());
    }

    public function test_change_default_format_to_friently()
    {
        DateTime::$DEFAULT_FORMAT = 'short';
        $this->assert_equals(date(DateTime::$FORMATS['short']),
                $this->date->format());
    }

    public function test_to_string()
    {
        $this->assert_equals(date(DateTime::get_format()), "".$this->date);
    }

    public function test_create_from_format_error_handling()
    {
        $d = DateTime::createFromFormat('H:i:s Y-d-m', '!!!');
        $this->assert_false($d);
    }

    public function test_create_from_format_without_tz()
    {
        $d = DateTime::createFromFormat('H:i:s Y-d-m', '03:04:05 2000-02-01');
        $this->assert_equals(new DateTime('2000-01-02 03:04:05'), $d);
    }

    public function test_create_from_format_with_tz()
    {
        $d = DateTime::createFromFormat('Y-m-d H:i:s', '2000-02-01 03:04:05',
                        new \DateTimeZone('Etc/GMT-10'));
        $this->assert_equals(new DateTime('2000-01-31 17:04:05'), $d);
    }

}