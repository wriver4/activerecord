<?php

namespace Test\Helpers;

use \Activerecord\Adapters\Oci;
use \Activerecord\Adapters\Sqlite;
use \Activerecord\Column;
use \Activerecord\Config;
use \Activerecord\Connection;
use \Activerecord\Exceptions\ExceptionDatabase;
use \Activerecord\Utils;
use \PDO;
use \SebastianBergmann\RecursionContext\Exception;
use function \array_key_exists;

class AdapterTest
        extends \Test\Helpers\DatabaseTest
{

    const InvalidDb = '__1337__invalid_db__';

    public function setUp($connection_name = null)
    {
        if (($connection_name && !\in_array($connection_name,
                        PDO::getAvailableDrivers())) ||
                Config::instance()->getConnection($connection_name) == 'skip')
        {
            $this->markTestSkipped($connection_name.' drivers are not present');
        }

        parent::setUp($connection_name);
    }

    public function testDefaultPortExceptSqlite()
    {
        if ($this->conn instanceof Sqlite)
        {
            return;
        }

        $c = $this->conn;
        $this->assertTrue($c::$DEFAULT_PORT > 0);
    }

    public function testShouldSetAdapterVariables()
    {
        $this->assertNotNull($this->conn->protocol);
    }

    public function testNullConnectionStringUsesDefaultConnection()
    {
        $this->assertNotNull(Connection::instance(null));
        $this->assertNotNull(Connection::instance(''));
        $this->assertNotNull(Connection::instance());
    }

    /**
     * @expectedException Activerecord\DatabaseException
     */
    public function testInvalidConnectionProtocol()
    {
        Connection::instance('terribledb://user:pass@host/db');
    }

    /**
     * @expectedException Activerecord\DatabaseException
     */
    public function testNoHostConnection()
    {
        if (!$GLOBALS['slow_tests'])
        {
            throw new ExceptionDatabase("");
        }

        Connection::instance("{$this->conn->protocol}://user:pass");
    }

    /**
     * @expectedException Activerecord\DatabaseException
     */
    public function testConnectionFailedInvalidHost()
    {
        if (!$GLOBALS['slow_tests'])
        {
            throw new ExceptionDatabase("");
        }

        Connection::instance("{$this->conn->protocol}://user:pass/1.1.1.1/db");
    }

    /**
     * @expectedException Activerecord\DatabaseException
     */
    public function testConnectionFailed()
    {
        Connection::instance("{$this->conn->protocol}://baduser:badpass@127.0.0.1/db");
    }

    /**
     * @expectedException Activerecord\DatabaseException
     */
    public function testConnectFailed()
    {
        Connection::instance("{$this->conn->protocol}://zzz:zzz@127.0.0.1/test");
    }

    public function testConnectWithPort()
    {
        $config = Config::instance();
        $name = $config->getDefaultConnection();
        $url = \parse_url($config->getConnection($name));
        $conn = $this->conn;
        $port = $conn::$DEFAULT_PORT;

        $connection_string = "{$url['scheme']}://{$url['user']}";
        if (isset($url['pass']))
        {
            $connection_string = "{$connection_string}:{$url['pass']}";
        }
        $connection_string = "{$connection_string}@{$url['host']}:$port{$url['path']}";

        if ($this->conn->protocol != 'sqlite')
        {
            Connection::instance($connection_string);
        }
    }

    /**
     * @expectedException Activerecord\DatabaseException
     */
    public function testConnectToInvalidDatabase()
    {
        Connection::instance("{$this->conn->protocol}://test:test@127.0.0.1/".self::InvalidDb);
    }

    public function testDateTimeType()
    {
        $columns = $this->conn->columns('authors');
        $this->assertEquals('datetime', $columns['created_at']->raw_type);
        $this->assertEquals(Column::DATETIME, $columns['created_at']->type);
        $this->assertTrue($columns['created_at']->length > 0);
    }

    public function testDate()
    {
        $columns = $this->conn->columns('authors');
        $this->assertEquals('date', $columns['some_Date']->raw_type);
        $this->assertEquals(Column::DATE, $columns['some_Date']->type);
        $this->assertTrue($columns['some_Date']->length >= 7);
    }

    public function testColumnsNoInflectionOnHashKey()
    {
        $author_columns = $this->conn->columns('authors');
        $this->assertTrue(array_key_exists('author_id', $author_columns));
    }

    public function testColumnsNullable()
    {
        $author_columns = $this->conn->columns('authors');
        $this->assertFalse($author_columns['author_id']->nullable);
        $this->assertTrue($author_columns['parent_author_id']->nullable);
    }

    public function testColumnsPk()
    {
        $author_columns = $this->conn->columns('authors');
        $this->assertTrue($author_columns['author_id']->pk);
        $this->assertFalse($author_columns['parent_author_id']->pk);
    }

    public function testColumnsSequence()
    {
        if ($this->conn->supports_sequences())
        {
            $author_columns = $this->conn->columns('authors');
            $this->assertEquals('authors_author_id_seq',
                    $author_columns['author_id']->sequence);
        }
    }

    public function testColumnsDefault()
    {
        $author_columns = $this->conn->columns('authors');
        $this->assertEquals('default_name', $author_columns['name']->default);
    }

    public function testColumnsType()
    {
        $author_columns = $this->conn->columns('authors');
        $this->assertEquals('varchar',
                \substr($author_columns['name']->raw_type, 0, 7));
        $this->assertEquals(Column::STRING, $author_columns['name']->type);
        $this->assertEquals(25, $author_columns['name']->length);
    }

    public function testColumnsText()
    {
        $author_columns = $this->conn->columns('authors');
        $this->assertEquals('text', $author_columns['some_text']->raw_type);
        $this->assertEquals(null, $author_columns['some_text']->length);
    }

    public function testColumnsTime()
    {
        $author_columns = $this->conn->columns('authors');
        $this->assertEquals('time', $author_columns['some_time']->raw_type);
        $this->assertEquals(Column::TIME, $author_columns['some_time']->type);
    }

    public function testQuery()
    {
        $sth = $this->conn->query('SELECT * FROM authors');

        while (($row = $sth->fetch()))
        {
            $this->assertNotNull($row);
        }

        $sth = $this->conn->query('SELECT * FROM authors WHERE author_id=1');
        $row = $sth->fetch();
        $this->assertEquals('Tito', $row['name']);
    }

    /**
     * @expectedException Activerecord\DatabaseException
     */
    public function testInvalidQuery()
    {
        $this->conn->query('alsdkjfsdf');
    }

    public function testFetch()
    {
        $sth = $this->conn->query('SELECT * FROM authors WHERE author_id IN(1,2,3)');
        $i = 0;
        $ids = array();

        while (($row = $sth->fetch()))
        {
            ++$i;
            $ids[] = $row['author_id'];
        }

        $this->assertEquals(3, $i);
        $this->assertEquals([
            1,
            2,
            3], $ids);
    }

    public function testQueryWithParams()
    {
        $x = [
            'Bill Clinton',
            'Tito'];
        $sth = $this->conn->query('SELECT * FROM authors WHERE name IN(?,?) ORDER BY name DESC',
                $x);
        $row = $sth->fetch();
        $this->assertEquals('Tito', $row['name']);

        $row = $sth->fetch();
        $this->assertEquals('Bill Clinton', $row['name']);

        $row = $sth->fetch();
        $this->assertEquals(null, $row);
    }

    public function testInsertIdShouldReturnExplicitlyInsertedId()
    {
        $this->conn->query('INSERT INTO authors(author_id,name) VALUES(99,\'name\')');
        $this->assertTrue($this->conn->insertId() > 0);
    }

    public function testInsertId()
    {
        $this->conn->query("INSERT INTO authors(name) VALUES('name')");
        $this->assertTrue($this->conn->insertId() > 0);
    }

    public function testInsertIdWithParams()
    {
        $x = ['name'];
        $this->conn->query('INSERT INTO authors(name) VALUES(?)', $x);
        $this->assertTrue($this->conn->insertId() > 0);
    }

    public function testInflection()
    {
        $columns = $this->conn->columns('authors');
        $this->assertEquals('parent_author_id',
                $columns['parent_author_id']->inflected_name);
    }

    public function testEscape()
    {
        $s = "Bob's";
        $this->assertNotEquals($s, $this->conn->escape($s));
    }

    public function testColumnsx()
    {
        $columns = $this->conn->columns('authors');
        $names = [
            'author_id',
            'parent_author_id',
            'name',
            'updated_at',
            'created_at',
            'some_Date',
            'some_time',
            'some_text',
            'encrypted_password',
            'mixedCaseField'];

        if ($this->conn instanceof Oci)
        {
            $names = \array_filter(\array_map('strtolower', $names),
                    function($s)
            {
                return $s !== 'some_time';
            });
        }

        foreach ($names as $field)
        {
            $this->assertTrue(array_key_exists($field, $columns));

            $this->assertEquals(true, $columns['author_id']->pk);
            $this->assertEquals('int', $columns['author_id']->raw_type);
            $this->assertEquals(Column::INTEGER, $columns['author_id']->type);
            $this->assertTrue($columns['author_id']->length > 1);
            $this->assertFalse($columns['author_id']->nullable);

            $this->assertEquals(false, $columns['parent_author_id']->pk);
            $this->assertTrue($columns['parent_author_id']->nullable);

            $this->assertEquals('varchar',
                    substr($columns['name']->raw_type, 0, 7));
            $this->assertEquals(Column::STRING, $columns['name']->type);
            $this->assertEquals(25, $columns['name']->length);
        }
    }

    public function testColumnsDecimal()
    {
        $columns = $this->conn->columns('books');
        $this->assertEquals(Column::DECIMAL, $columns['special']->type);
        $this->assertTrue($columns['special']->length >= 10);
    }

    private function limit($offset, $limit)
    {
        $ret = [];
        $sql = 'SELECT * FROM authors ORDER BY name ASC';
        $this->conn->queryAndFetch($this->conn->limit($sql, $offset, $limit),
                function($row) use (&$ret)
        {
            $ret[] = $row;
        });
        return Utils::collect($ret, 'author_id');
    }

    public function testLimit()
    {
        $this->assertEquals([2,
            1], $this->limit(1, 2));
    }

    public function testLimitToFirstRecord()
    {
        $this->assertEquals([3], $this->limit(0, 1));
    }

    public function testLimitToLastRecord()
    {
        $this->assertEquals([1], $this->limit(2, 1));
    }

    public function testLimitWithNullOffset()
    {
        $this->assertEquals([3], $this->limit(null, 1));
    }

    public function testLimitWithNulls()
    {
        $this->assertEquals([], $this->limit(null, null));
    }

    public function testFetchNoResults()
    {
        $sth = $this->conn->query('SELECT * FROM authors WHERE author_id=65534');
        $this->assertEquals(null, $sth->fetch());
    }

    public function testTables()
    {
        $this->assertTrue(\count($this->conn->tables()) > 0);
    }

    public function testQueryColumnInfo()
    {
        $this->assertGreaterThan(0,
                \count($this->conn->queryColumnInfo("authors")));
    }

    public function testQueryTableInfo()
    {
        $this->assertGreaterThan(0, \count($this->conn->queryForTables()));
    }

    public function testQueryTableInfoMustReturnOneField()
    {
        $sth = $this->conn->queryForTables();
        $this->assertEquals(1, count($sth->fetch()));
    }

    public function testTransactionCommit()
    {
        $original = $this->conn->queryAndFetchOne("select count(*) from authors");

        $this->conn->transaction();
        $this->conn->query("insert into authors(author_id,name) values(9999,'blahhhhhhhh')");
        $this->conn->commit();

        $this->assertEquals($original + 1,
                $this->conn->queryAndFetchOne("select count(*) from authors"));
    }

    public function testTransactionRollback()
    {
        $original = $this->conn->queryAndFetchOne("select count(*) from authors");

        $this->conn->transaction();
        $this->conn->query("insert into authors(author_id,name) values(9999,'blahhhhhhhh')");
        $this->conn->rollback();

        $this->assertEquals($original,
                $this->conn->queryAndFetchOne("select count(*) from authors"));
    }

    public function testShowMeUsefulPdoExceptionMessage()
    {
        try
        {
            $this->conn->query('select * from an_invalid_column');
            $this->fail();
        }
        catch (Exception $e)
        {
            $this->assertEquals(1,
                    \preg_match('/(an_invalid_column)|(exist)/',
                            $e->getMessage()));
        }
    }

    public function testQuoteNameDoNotOverQuote()
    {
        $c = $this->conn;
        $q = $c::$QUOTE_CHARACTER;
        $qn = function($s) use ($c)
        {
            return $c->quoteName($s);
        };

        $this->assertEquals("{$q}string", $qn("{$q}string"));
        $this->assertEquals("string{$q}", $qn("string{$q}"));
        $this->assertEquals("{$q}string{$q}", $qn("{$q}string{$q}"));
    }

    public function testDatetimeToString()
    {
        $datetime = '2009-01-01 01:01:01 EST';
        $this->assertEquals($datetime,
                $this->conn->datetimeToString(dateCreate($datetime)));
    }

    public function testDateToString()
    {
        $datetime = '2009-01-01';
        $this->assertEquals($datetime,
                $this->conn->dateToString(dateCreate($datetime)));
    }

}