<?php

namespace Test\Activerecord;

use Activerecord\Adapters\Pgsql;
use Activerecord\Column;

class PgsqlAdapterTest
        extends AdapterTest
{

    public function setUp($connection_name = null)
    {
        parent::setUp('pgsql');
    }

    public function test_insert_id()
    {
        $this->conn->query("INSERT INTO authors(author_id,name) VALUES(nextval('authors_author_id_seq'),'name')");
        $this->assertTrue($this->conn->insert_id('authors_author_id_seq') > 0);
    }

    public function test_insert_id_with_params()
    {
        $x = array(
            'name');
        $this->conn->query("INSERT INTO authors(author_id,name) VALUES(nextval('authors_author_id_seq'),?)",
                $x);
        $this->assertTrue($this->conn->insert_id('authors_author_id_seq') > 0);
    }

    public function test_insert_id_should_return_explicitly_inserted_id()
    {
        $this->conn->query('INSERT INTO authors(author_id,name) VALUES(99,\'name\')');
        $this->assertTrue($this->conn->insert_id('authors_author_id_seq') > 0);
    }

    public function test_set_charset()
    {
        $connection_string = Activerecord\Config::instance()->get_connection($this->connection_name);
        $conn = Activerecord\Connection::instance($connection_string.'?charset=utf8');
        $this->assertEquals("SET NAMES 'utf8'", $conn->last_query);
    }

    public function test_gh96_columns_not_duplicated_by_index()
    {
        $this->assertEquals(3,
                $this->conn->query_column_info("user_newsletters")->rowCount());
    }

}