<?php

namespace Test\Adapters;

use Activerecord\Adapters\Sqlite;
use Activerecord\Connection;
use Activerecord\Exceptions\ExceptionDatabase;
use Test\Helpers\DatabaseTest;

class SqliteTest
        extends DatabaseTest
{

    public function setUp($connection_name = null)
    {
        parent::setUp('sqlite');
    }

    public function tearDown()
    {
        parent::tearDown();

        @\unlink(self::InvalidDb);
    }

    public static function tearDownAfterClass()
    {
        parent::tearDownAfterClass();

        @\unlink(static::$db);
    }

    public function testConnectToInvalidDatabaseShouldNotCreateDbFile()
    {
        try
        {
            Connection::instance("sqlite://".self::InvalidDb);
            $this->assertFalse(true);
        }
        catch (ExceptionDatabase $e)
        {
            $this->assertFalse(\file_exists(__DIR__."/".self::InvalidDb));
        }
    }

    public function testLimitWithNullOffsetDoesNotContainOffset()
    {
        $ret = [];
        $sql = 'SELECT * FROM authors ORDER BY name ASC';
        $this->conn->query_and_fetch($this->conn->limit($sql, null, 1),
                function($row) use (&$ret)
        {
            $ret[] = $row;
        });

        $this->assertTrue(strpos($this->conn->last_query, 'LIMIT 1') !== false);
    }

    public function testGh183SqliteadapterAutoincrement()
    {
        // defined in lowercase: id integer not null primary key
        $columns = $this->conn->columns('awesome_people');
        $this->assertTrue($columns['id']->auto_increment);

        // defined in uppercase: `amenity_id` INTEGER NOT NULL PRIMARY KEY
        $columns = $this->conn->columns('amenities');
        $this->assertTrue($columns['amenity_id']->auto_increment);

        // defined using int: `rm-id` INT NOT NULL
        $columns = $this->conn->columns('`rm-bldg`');
        $this->assertFalse($columns['rm-id']->auto_increment);

        // defined using int: id INT NOT NULL PRIMARY KEY
        $columns = $this->conn->columns('hosts');
        $this->assertTrue($columns['id']->auto_increment);
    }

    public function testDatetimeToString()
    {
        $datetime = '2009-01-01 01:01:01';
        $this->assertEquals($datetime,
                $this->conn->datetimeToString(date_create($datetime)));
    }

    public function testDateToString()
    {
        $datetime = '2009-01-01';
        $this->assertEquals($datetime,
                $this->conn->dateToString(date_create($datetime)));
    }

    // not supported
    public function testConnectWithPort()
    {

    }

}