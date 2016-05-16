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
        extends PHPUnit_Framework_TestCase
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
    public function test_connection_info_from_should_throw_exception_when_no_host()
    {
        Activerecord\Connection::parse_connection_url('mysql://user:pass@');
    }

    public function test_connection_info()
    {
        $info = Activerecord\Connection::parse_connection_url('mysql://user:pass@127.0.0.1:3306/dbname');
        $this->assert_equals('mysql', $info->protocol);
        $this->assert_equals('user', $info->user);
        $this->assert_equals('pass', $info->pass);
        $this->assert_equals('127.0.0.1', $info->host);
        $this->assert_equals(3306, $info->port);
        $this->assert_equals('dbname', $info->db);
    }

    public function test_gh_103_sqlite_connection_string_relative()
    {
        $info = Activerecord\Connection::parse_connection_url('sqlite://../some/path/to/file.db');
        $this->assert_equals('../some/path/to/file.db', $info->host);
    }

    /**
     * @expectedException Activerecord\DatabaseException
     */
    public function test_gh_103_sqlite_connection_string_absolute()
    {
        $info = Activerecord\Connection::parse_connection_url('sqlite:///some/path/to/file.db');
    }

    public function test_gh_103_sqlite_connection_string_unix()
    {
        $info = Activerecord\Connection::parse_connection_url('sqlite://unix(/some/path/to/file.db)');
        $this->assert_equals('/some/path/to/file.db', $info->host);

        $info = Activerecord\Connection::parse_connection_url('sqlite://unix(/some/path/to/file.db)/');
        $this->assert_equals('/some/path/to/file.db', $info->host);

        $info = Activerecord\Connection::parse_connection_url('sqlite://unix(/some/path/to/file.db)/dummy');
        $this->assert_equals('/some/path/to/file.db', $info->host);
    }

    public function test_gh_103_sqlite_connection_string_windows()
    {
        $info = Activerecord\Connection::parse_connection_url('sqlite://windows(c%3A/some/path/to/file.db)');
        $this->assert_equals('c:/some/path/to/file.db', $info->host);
    }

    public function test_parse_connection_url_with_unix_sockets()
    {
        $info = Activerecord\Connection::parse_connection_url('mysql://user:password@unix(/tmp/mysql.sock)/database');
        $this->assert_equals('/tmp/mysql.sock', $info->host);
    }

    public function test_parse_connection_url_with_decode_option()
    {
        $info = Activerecord\Connection::parse_connection_url('mysql://h%20az:h%40i@127.0.0.1/test?decode=true');
        $this->assert_equals('h az', $info->user);
        $this->assert_equals('h@i', $info->pass);
    }

    public function test_encoding()
    {
        $info = Activerecord\Connection::parse_connection_url('mysql://test:test@127.0.0.1/test?charset=utf8');
        $this->assert_equals('utf8', $info->charset);
    }

}