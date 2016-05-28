<?php

namespace Test;

use Activerecord\Config;

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

}