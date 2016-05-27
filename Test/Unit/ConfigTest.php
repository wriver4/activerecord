<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Test;

use Activerecord\Config;
use Activerecord\DateTime;
use Activerecord\Test\Stubs\DateTimeWithoutCreateFromFormatTest;
use Activerecord\Exceptions\ExceptionConfig;

/**
 * Description of ConfigTest
 *
 * @author mark weisser <mark at whizbangdevelopers.com>
 */
class ConfigTest
        extends \PHPUnit_Framework_TestCase
{

    public function setUp()
    {
        $this->config = new Config();
        $this->connections = ['development' => 'mysql://blah/development',
            'test' => 'mysql://blah/test'];
        $this->config->setConnections($this->connections);
    }

    public function tearDown()
    {

    }

    /**
     * @expectedException Activerecord\Exceptions\ExceptionConfig
     */
    public function testSetConnectionsMustBeArray()
    {
        $this->config->setConnections(null);
    }

    public function testGetConnections()
    {
        $this->assertEquals($this->connections, $this->config->getConnections());
    }

    public function testGetConnection()
    {
        $this->assertEquals($this->connections['development'],
                $this->config->getConnection('development'));
    }

    public function testGetInvalidConnection()
    {
        $this->assertNull($this->config->getConnection('whiskey tango foxtrot'));
    }

    public function testGetDefaultConnectionAndConnection()
    {
        $this->config->setDefaultConnection('development');
        $this->assertEquals('development', $this->config->getDefaultConnection());
        $this->assertEquals($this->connections['development'],
                $this->config->getDefaultConnectionString());
    }

    public function testGetDefaultConnectionAndConnectionStringDefaultsToDevelopment()
    {
        $this->assertEquals('development', $this->config->getDefaultConnection());
        $this->assertEquals($this->connections['development'],
                $this->config->getDefaultConnectionString());
    }

    public function testGetDefaultConnectionStringWhenConnectionNameIsNotValid()
    {
        $this->config->setDefaultConnection('little mac');
        $this->assertNull($this->config->getDefaultConnectionString());
    }

    public function testDefaultConnectionIsSetWhenOnlyOneConnectionIsPresent()
    {
        $this->config->setConnections(['development' => $this->connections['development']]);
        $this->assertEquals('development', $this->config->getDefaultConnection());
    }

    public function testSetConnectionsWithDefault()
    {
        $this->config->setConnections($this->connections, 'test');
        $this->assertEquals('test', $this->config->getDefaultConnection());
    }

    public function testGetDateClassWithDefault()
    {
        // $this->assertEquals('DateTime', $this->config->getDateClass());
    }

    /**
     * @expectedException Activerecord\Exceptions\ExceptionConfig
     */
    public function testSetDateClassWhenClassDoesNotExist()
    {
        //$this->config->setDateClass('doesntexist');
    }

    /**
     * @expectedException Activerecord\Exceptions\ExceptionConfig
     */
    public function testSetDateClassWhenClassDoesNotHaveFormatOrCreateFromFormat()
    {
        //  $this->config->setDateClass('LoggerTest');
    }

    /**
     * @expectedException Activerecord\Exceptions\ExceptionConfig
     */
    public function testSetDateClassWhenClassDoesNotHaveCreateFromFormat()
    {
        // $this->config->setDateClass('DateTimeWithoutCreateFromFormatTest');
    }

    public function testSetDateClassWithValidClass()
    {
        //  $this->config->setDateClass('FormatDateTimeTest');
        //  $this->assertEquals('FormatDateTimeTest', $this->config->getDateClass());
    }

    public function testInitializeClosure()
    {
        $test = $this;

        Config::initialize(function($cfg) use ($test)
        {
            $test->assertNotNull($cfg);
            $test->assertEquals('Config', get_class($cfg));
        });
    }

    public function testLoggerObjectMustImplementLogMethod()
    {
        try
        {
            $this->config->setLogger(new LoggerTest);
            $this->fail();
        }
        catch (ExceptionConfig $e)
        {
            $this->assertEquals($e->getMessage(),
                    "Logger object must implement a public log method");
        }
    }

    private function log()
    {

    }

}