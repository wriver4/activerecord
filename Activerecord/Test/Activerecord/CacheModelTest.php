<?php

namespace Activerecord\Test\Activerecord;

use Activerecord\Cache;
use Activerecord\Config;
use Activerecord\Test\Helpers\DatabaseTest;

class CacheModelTest
        extends DatabaseTest
{

    public function setUp($connection_name = null)
    {
        if (!\extension_loaded('memcache'))
        {
            $this->markTestSkipped('The memcache extension is not available');
            return;
        }
        parent::setUp($connection_name);
        Config::instance()->setCache('memcache://localhost');
    }

    protected static function setMethodPublic($className, $methodName)
    {
        $class = new \ReflectionClass($className);
        $method = $class->getMethod($methodName);
        $method->setAccessible(true);
        return $method;
    }

    public function tearDown()
    {
        Cache::flush();
        Cache::initialize(null);
    }

    public function testGetReturnsTheValue()
    {
        $this->assertEquals("abcd", $this->cache_get());
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

    public function testNamespaceIsSetProperly()
    {
        Cache::$options['namespace'] = 'myapp';
        $this->cacheGet();
        $this->assertSame("abcd", Cache::$adapter->read("myapp::1337"));
    }

    public function testDefaultExpire()
    {
        $this->assertEquals(30, Author::table()->cache_model_expire);
    }

    public function testExplicitExpire()
    {
        $this->assertEquals(2592000, Publisher::table()->cache_model_expire);
    }

    public function testCacheKey()
    {
        $method = $this->setMethodPublic('Author', 'cache_key');
        $author = Author::first();

        $this->assertEquals("Author-1", $method->invokeArgs($author, array()));
    }

    public function testModelCacheFindByPk()
    {
        $publisher = Publisher::find(1);
        $method = $this->setMethodPublic('Publisher', 'cache_key');
        $cache_key = $method->invokeArgs($publisher, array());
        $publisherDirectlyFromCache = Cache::$adapter->read($cache_key);

        $this->assertEquals($publisher->name, $publisherDirectlyFromCache->name);
    }

    public function testModelCacheNew()
    {
        $publisher = new Publisher(["name" => "HarperCollins"]);
        $publisher->save();

        $method = $this->setMethodPublic('Publisher', 'cache_key');
        $cache_key = $method->invokeArgs($publisher, []);

        $publisherDirectlyFromCache = Cache::$adapter->read($cache_key);

        $this->assertTrue(\is_object($publisherDirectlyFromCache));
        $this->assertEquals($publisher->name, $publisherDirectlyFromCache->name);
    }

    public function testModelCacheFind()
    {
        $method = $this->setMethodPublic('Publisher', 'cache_key');
        $publishers = Publisher::all();

        foreach ($publishers as $publisher)
        {
            $cache_key = $method->invokeArgs($publisher, array());
            $publisherDirectlyFromCache = Cache::$adapter->read($cache_key);

            $this->assertEquals($publisher->name,
                    $publisherDirectlyFromCache->name);
        }
    }

    public function testRegularModelsNotCached()
    {
        $method = $this->setMethodPublic('Author', 'cache_key');
        $author = Author::first();
        $cache_key = $method->invokeArgs($author, array());
        $this->assertFalse(Cache::$adapter->read($cache_key));
    }

    public function testModelDeleteFromCache()
    {
        $method = $this->setMethodPublic('Publisher', 'cache_key');
        $publisher = Publisher::find(1);
        $cache_key = $method->invokeArgs($publisher, array());

        $publisher->delete();

        // at this point, the cached record should be gone
        $this->assertFalse(Cache::$adapter->read($cache_key));
    }

    public function testModelUpdateCache()
    {
        $method = $this->setMethodPublic('Publisher', 'cache_key');

        $publisher = Publisher::find(1);
        $cache_key = $method->invokeArgs($publisher, array());
        $this->assertEquals("Random House", $publisher->name);

        $publisherDirectlyFromCache = Cache::$adapter->read($cache_key);
        $this->assertEquals("Random House", $publisherDirectlyFromCache->name);

        // make sure that updates make it to cache
        $publisher->name = "Puppy Publishing";
        $publisher->save();

        $publisherDirectlyFromCache = Cache::$adapter->read($cache_key);
        $this->assertEquals("Puppy Publishing",
                $publisherDirectlyFromCache->name);
    }

}