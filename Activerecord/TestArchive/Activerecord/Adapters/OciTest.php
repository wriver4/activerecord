<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Activerecord\Test\Activerecord\Adapters;

use Activerecord\Config;
use Activerecord\Connection;
use Activerecord\Test\Helpers\AdapterTest;

/**
 * Description of OciTest
 *
 * @author mark weisser <mark at whizbangdevelopers.com>
 */
class OciTest
        extends AdapterTest
{

    public function setUp($connection_name = null)
    {
        parent::setUp('oci');
    }

    public function tearDown()
    {

    }

    public function testGetSequenceName()
    {
        $this->assertEquals('authors_seq',
                $this->conn->getSequenceName('authors', 'author_id'));
    }

    public function testColumnsText()
    {
        $author_columns = $this->conn->columns('authors');
        $this->assertEquals('varchar2', $author_columns['some_text']->raw_type);
        $this->assertEquals(100, $author_columns['some_text']->length);
    }

    public function testDatetimeToString()
    {
        $this->assertEquals('01-Jan-2009 01:01:01 AM',
                $this->conn->datetimeToString(\date_create('2009-01-01 01:01:01 EST')));
    }

    public function testDateToString()
    {
        $this->assertEquals('01-Jan-2009',
                $this->conn->dateToString(\date_create('2009-01-01 01:01:01 EST')));
    }

    public function testInsertId()
    {

    }

    public function testInsertIdWithParams()
    {

    }

    public function testInsertIdShouldReturnExplicitlyInsertedId()
    {

    }

    public function testColumnsTime()
    {

    }

    public function testColumnsSequence()
    {

    }

    public function testSetCharset()
    {
        $connection_string = Config::instance()->getConnection($this->connection_name);
        $conn = Connection::instance($connection_string.'?charset=utf8');
        $this->assertEquals(';charset=utf8', $conn->dsn_params);
    }

}