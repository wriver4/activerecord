<?php

namespace Test\Adapters;

use Activerecord\Connection;
use Activerecord\Config;
use Activerecord\Column;
use Test\DatabaseLoader;
use Test\Functional\AdapterTest;

class MysqlTest
        extends AdapterTest
{

    public function setUp($connection_name = null)
    {
        parent::setUp('mysql');
    }

    public function tearDown()
    {
        DatabaseLoader::dropTables();
    }

    public function testEnum()
    {
        $author_columns = $this->conn->columns('authors');
        $this->assertEquals('enum', $author_columns['some_enum']->raw_type);
        $this->assertEquals(Column::STRING, $author_columns['some_enum']->type);
        $this->assertSame(null, $author_columns['some_enum']->length);
    }

    public function testSetCharset()
    {
        $connection_string = Config::instance()->getConnection($this->connection_name);
        $conn = Connection::instance($connection_string.'?charset=utf8');
        $this->assertEquals('SET NAMES ?', $conn->last_query);
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

        $this->assertTrue(\strpos($this->conn->last_query, 'LIMIT 1') !== false);
    }

    public function testColumnsSequence()
    {

    }

}
//echo '<pre> after MysqlTest  ';
//print_r(array_diff(get_declared_classes(), $predefinedClasses));
//echo '</pre>';
