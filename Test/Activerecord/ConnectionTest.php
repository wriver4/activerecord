<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Test\Activerecord;

use Activerecord\Connection;

/**
 * Description of ConnectionTest
 *
 * @author mark weisser <mark at whizbangdevelopers.com>
 */
class ConnectionTest
        extends \PHPUnit_Framework_TestCase
{

    public function setUp()
    {

    }

    public function tearDown()
    {

    }

    /**
     * @expectedException Activerecord\DatabaseException
     */
    public function testConnectionInfoFromShouldThrowExceptionWhenNoHost()
    {
        Connection::parseConnectionUrl('mysql://user:pass@');
    }

    public function testConnectionInfo()
    {
        $info = Connection::parseConnectionUrl('mysql://user:pass@127.0.0.1:3306/dbname');
        $this->assertEquals('mysql', $info->protocol);
        $this->assertEquals('user', $info->user);
        $this->assertEquals('pass', $info->pass);
        $this->assertEquals('127.0.0.1', $info->host);
        $this->assertEquals(3306, $info->port);
        $this->assertEquals('dbname', $info->db);
    }

    public function testGh103SqliteConnectionStringRelative()
    {
        $info = Connection::parseConnectionUrl('sqlite://../some/path/to/file.db');
        $this->assertEquals('../some/path/to/file.db', $info->host);
    }

    /**
     * @expectedException Activerecord\DatabaseException
     */
    public function testGh103SqliteConnectionStringAbsolute()
    {
        $info = Connection::parseConnectionUrl('sqlite:///some/path/to/file.db');
    }

    public function testGh103SqliteConnectionStringUnix()
    {
        $info = Connection::parseConnectionUrl('sqlite://unix(/some/path/to/file.db)');
        $this->assertEquals('/some/path/to/file.db', $info->host);

        $info = Connection::parseConnectionUrl('sqlite://unix(/some/path/to/file.db)/');
        $this->assertEquals('/some/path/to/file.db', $info->host);

        $info = Connection::parseConnectionUrl('sqlite://unix(/some/path/to/file.db)/dummy');
        $this->assertEquals('/some/path/to/file.db', $info->host);
    }

    public function testGh103SqliteConnectionStringWindows()
    {
        $info = Connection::parseConnectionUrl('sqlite://windows(c%3A/some/path/to/file.db)');
        $this->assertEquals('c:/some/path/to/file.db', $info->host);
    }

    public function testParseConnectionUrlWithUnixSockets()
    {
        $info = Connection::parseConnectionUrl('mysql://user:password@unix(/tmp/mysql.sock)/database');
        $this->assertEquals('/tmp/mysql.sock', $info->host);
    }

    public function testParseConnectionUrlWithDecodeOption()
    {
        $info = Connection::parseConnectionUrl('mysql://h%20az:h%40i@127.0.0.1/test?decode=true');
        $this->assertEquals('h az', $info->user);
        $this->assertEquals('h@i', $info->pass);
    }

    public function testEncoding()
    {
        $info = Connection::parseConnectionUrl('mysql://test:test@127.0.0.1/test?charset=utf8');
        $this->assertEquals('utf8', $info->charset);
    }

}