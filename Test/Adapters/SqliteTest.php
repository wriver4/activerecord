<?php

namespace Test\Adapters;

use Activerecord\Connection;
use Activerecord\Exceptions\ExceptionDatabase;
use Test\DatabaseLoader;
use Test\Functional\AdapterTest;

class SqliteTest
        extends AdapterTest
{

    public function setUp($connection_name = null)
    {
        parent::setUp('sqlite');
    }

    public function tearDown()
    {
        parent::tearDown();

        // \unlink(self::InvalidDb);
        var_dump(static::$db);
    }

    public static function tearDownAfterClass()
    {
        parent::tearDownAfterClass();

        //static::$db->query('PRAGMA writable_schema = 1');
        // static::$db->query('delete from sqlite_master where type in ("table", "index", "trigger")');
        //static::$db->query('PRAGMA writable_schema = 0');
        //\unlink(static::$db);
        //var_dump(static::$db);
    }

    /*
     * @expectedException   ExceptionDatabase
     */

    public function testConnectToInvalidDatabaseShouldNotCreateDbFile()
    {
        try
        {
            if ($GLOBALS['OS'] !== 'WIN')
            {
                $xcon = Connection::instance("sqlite://".self::InvalidDb);
                var_dump($xcon);
                $this->assertFalse(true);
            }
            else
            {
                //sqlite://windows(c%3A/GitHub/activerecord/Test/Fixtures/test.db)
                $wincon = Connection::instance("sqlite://windows('c%3A/GitHub/activerecord/Test/Fixtures/'".self::InvalidDb."')'");
                var_dump($wincon);
                $this->assertFalse(true);
            }
        }
        catch (ExceptionDatabase $e)
        {
            //$this->assertFalse(\file_exists(__DIR__."/".self::InvalidDb));
        }
    }

    public function testLimitWithNullOffsetDoesNotContainOffset()
    {
        $ret = [];
        $sql = 'SELECT * FROM authors ORDER BY name ASC';
        $this->conn->queryAndFetch($this->conn->limit($sql, null, 1),
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

    public function testTables()
    {

    }

}