<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Activerecord\Test\Activerecord\Adapters;

use Activerecord\Connection;
use Activerecord\Config;
use Activerecord\Test\Helpers\AdapterTest;

/**
 * Description of PgsqlTest
 *
 * @author mark weisser <mark at whizbangdevelopers.com>
 */
class PgsqlTest
        extends AdapterTest
{

    public function setUp($connection_name = null)
    {
        parent::setUp('pgsql');
    }

    public function tearDown()
    {

    }

    public function testInsertId()
    {
        $this->conn->query("INSERT INTO authors(author_id,name) VALUES(nextval('authors_author_id_seq'),'name')");
        $this->assertTrue($this->conn->insertId('authors_author_id_seq') > 0);
    }

    public function testInsertIdWithParams()
    {
        $x = ['name'];
        $this->conn->query("INSERT INTO authors(author_id,name) VALUES(nextval('authors_author_id_seq'),?)",
                $x);
        $this->assertTrue($this->conn->insertId('authors_author_id_seq') > 0);
    }

    public function testInsertIdShouldReturnExplicitlyInsertedId()
    {
        $this->conn->query('INSERT INTO authors(author_id,name) VALUES(99,\'name\')');
        $this->assertTrue($this->conn->insertId('authors_author_id_seq') > 0);
    }

    public function testSetCharset()
    {
        $connection_string = Config::instance()->getConnection($this->connection_name);
        $conn = Connection::instance($connection_string.'?charset=utf8');
        $this->assertEquals("SET NAMES 'utf8'", $conn->last_query);
    }

    public function testGh96ColumnsNotDuplicatedByIndex()
    {
        $this->assertEquals(3,
                $this->conn->queryColumnInfo("user_newsletters")->rowCount());
    }

}