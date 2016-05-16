<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Test\Activerecord;

use Activerecord\Config;
use Activerecord\ConnectionManager;

/**
 * Description of ConnectionManagerTest
 *
 * @author mark weisser <mark at whizbangdevelopers.com>
 */
class ConnectionManagerTest
        extends DatabaseTest
{

    public function test_get_connection_with_null_connection()
    {
        $this->assertNotNull(ConnectionManager::get_connection(null));
        $this->assertNotNull(ConnectionManager::get_connection());
    }

    public function test_get_connection()
    {
        $this->assertNotNull(ConnectionManager::get_connection('mysql'));
    }

    public function test_get_connection_uses_existing_object()
    {
        $a = ConnectionManager::get_connection('mysql');
        $a->harro = 'harro there';

        $this->assert_same($a, ConnectionManager::get_connection('mysql'));
    }

    public function test_gh_91_get_connection_with_null_connection_is_always_default()
    {
        $conn_one = ConnectionManager::get_connection('mysql');
        $conn_two = ConnectionManager::get_connection();
        $conn_three = ConnectionManager::get_connection('mysql');
        $conn_four = ConnectionManager::get_connection();

        $this->assert_same($conn_one, $conn_three);
        $this->assert_same($conn_two, $conn_three);
        $this->assert_same($conn_four, $conn_three);
    }

}