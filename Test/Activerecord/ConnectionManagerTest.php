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
        extends \Test\Helpers\DatabaseTest
{

    public function testGetConnectionWithNullConnection()
    {
        $this->assertNotNull(ConnectionManager::getConnection(null));
        $this->assertNotNull(ConnectionManager::getConnection());
    }

    public function testGetConnection()
    {
        $this->assertNotNull(ConnectionManager::getConnection('mysql'));
    }

    public function testGetConnectionUsesExistingObject()
    {
        $a = ConnectionManager::getConnection('mysql');
        $a->harro = 'harro there';

        $this->assertSame($a, ConnectionManager::getConnection('mysql'));
    }

    public function testGh91GetConnectionWithNullConnectionIsAlwaysDefault()
    {
        $conn_one = ConnectionManager::getConnection('mysql');
        $conn_two = ConnectionManager::getConnection();
        $conn_three = ConnectionManager::getConnection('mysql');
        $conn_four = ConnectionManager::getConnection();

        $this->assertSame($conn_one, $conn_three);
        $this->assertSame($conn_two, $conn_three);
        $this->assertSame($conn_four, $conn_three);
    }

}