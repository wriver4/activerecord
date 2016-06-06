<?php

namespace Test;

use Activerecord\Cache;
use Activerecord\Config;
use Activerecord\Test\Models\Author;

class CacheTest
        extends \PHPUnit_Framework_TestCase
{

    public function setUp()
    {
        if (!\extension_loaded('memcache'))
        {
            $this->markTestSkipped('The memcache extension is not available');
            return;
        }
        //parent::setUp($connection_name);
        // Config::instance()->setCache('memcache://localhost');
        // Cache::initialize('memcache://localhost');
        Config::instance()->setCache('memcache://127.0.0.1');
        Cache::initialize('memcache://127.0.0.1');
    }

    public function tearDown()
    {
        Cache::flush();
        Cache::initialize(null);
    }

    private function cacheGet()
    {
        return Cache::get("1337",
                        function()
                {
                    return "abcd";
                });
    }

    public function testInitialize()
    {
        $this->assertNotNull(Cache::$adapter);
    }

    public function testInitializeWithNull()
    {
        Cache::initialize(null);
        $this->assertNull(Cache::$adapter);
    }

    public function testGetReturnsTheValue()
    {
        $this->assertEquals("abcd", $this->cacheGet());
    }

    public function testGetWritesToTheCache()
    {
        $this->cacheGet();
        $this->assertEquals("abcd", Cache::$adapter->read("1337"));
    }

    public function testGetDoesNotExecuteClosureOnCacheHit()
    {
        $this->cacheGet();
        Cache::get("1337",
                function()
        {
            throw new Exception("I better not execute!");
        });
    }

    public function testCacheAdapterReturnsFalseOnCacheMiss()
    {
        $this->assertSame(false, Cache::$adapter->read("some-key"));
    }

    public function testGetWorksWithoutCachingEnabled()
    {
        Cache::$adapter = null;
        $this->assertEquals("abcd", $this->cacheGet());
    }

    public function testCacheExpire()
    {
        Cache::$options['expire'] = 1;
        $this->cacheGet();
        sleep(2);

        $this->assertSame(false, Cache::$adapter->read("1337"));
    }

    public function testExplicitDefaultExpire()
    {
        Config::instance()->setCache('memcache://localhost',
                array(
            'expire' => 1));
        $this->assertEquals(1, Cache::$options['expire']);
    }

    public function testNamespaceIsSetProperly()
    {
        Cache::$options['namespace'] = 'myapp';
        $this->cacheGet();
        $this->assertSame("abcd", Cache::$adapter->read("myapp::1337"));
    }

    public function testDefaultExpire()
    {
        $this->assertEquals(30, Cache::$options['expire']);
    }

    /*
      public function testCachesColumnMetaData()
      {
      Author::first();

      $table_name = Author::table()->getFullyQualifiedTableName(!($this->conn instanceof Pgsql));
      $value = Cache::$adapter->read("get_meta_data-$table_name");
      $this->assertTrue(\is_array($value));
      }
     */
}