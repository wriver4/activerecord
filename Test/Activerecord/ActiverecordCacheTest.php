<?php

namespace Test\Activerecord;

use Activerecord\Cache;
use Activerecord\Config;
use Activerecord\Adapters\Pgsql;

class ActiverecordCacheTest
        extends \Test\Helpers\DatabaseTest
{

    public function setUp($connection_name = null)
    {
        if (!extensionLoaded('memcache'))
        {
            $this->markTestSkipped('The memcache extension is not available');
            return;
        }

        parent::setUp($connection_name);
        Config::instance()->set_cache('memcache://localhost');
    }

    public function tearDown()
    {
        Cache::flush();
        Cache::initialize(null);
    }

    public function testDefaultExpire()
    {
        $this->assertEquals(30, Cache::$options['expire']);
    }

    public function testExplicitDefaultExpire()
    {
        Config::instance()->setCache('memcache://localhost',
                array(
            'expire' => 1));
        $this->assertEquals(1, Cache::$options['expire']);
    }

    public function testCachesColumnMetaData()
    {
        Author::first();

        $table_name = Author::table()->getFullyQualifiedTableName(!($this->conn instanceof Pgsql));
        $value = Cache::$adapter->read("get_meta_data-$table_name");
        $this->assertTrue(is_array($value));
    }

}