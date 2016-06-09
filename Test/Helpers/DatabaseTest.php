<?php

namespace Test\Helpers;

use Activerecord\Table;
use Activerecord\Config;
use Activerecord\Adapters\Sqlite;
use Activerecord\ConnectionManager;
use Activerecord\Exceptions\ExceptionDatabase;
use Activerecord\Exceptions\ExceptionUndefinedProperty;

class DatabaseTest
        extends \PHPUnit_Framework_TestCase
{

    protected $conn;
    public static $log = false;
    public static $db;

    public function setUp($connection_name = null)
    {
        require_once 'DatabaseLoader.php';
        Table::clearCache();

        $config = Config::instance();
        $this->original_default_connection = $config->getDefaultConnection();

        // $this->original_date_class = $config->getDateClass();

        if ($connection_name)
        {
            $config->setDefaultConnection($connection_name);
        }

        if ($connection_name == 'sqlite' || $config->getDefaultConnection() == 'sqlite')
        {
            // need to create the db. the adapter specifically does not create it for us.
            static::$db = substr(Config::instance()->getConnection('sqlite'), 9);
            new Sqlite(static::$db);
        }

        $this->connection_name = $connection_name;
        try
        {
            $this->conn = ConnectionManager::getConnection($connection_name);
        }
        catch (ExceptionDatabase $e)
        {
            $this->markTestSkipped($connection_name.' failed to connect. '.$e->getMessage());
        }

        $GLOBALS['Activerecord_LOG'] = false;

        $loader = new DatabaseLoader($this->conn);
        $loader->resetTableData();

        if (self::$log)
        {
            $GLOBALS['Activerecord_LOG'] = true;
        }
    }

    public function tearDown()
    {
        // Config::instance()->setDateClass($this->original_date_class);
        if ($this->original_default_connection)
        {
            Config::instance()->setDefaultConnection($this->original_default_connection);
        }
    }

    public function testAssertExceptionMessageContains($contains, $closure)
    {
        $message = "";

        try
        {
            $closure();
        }
        catch (ExceptionUndefinedProperty $e)
        {
            $message = $e->getMessage();
        }

        $this->assertContains($contains, $message);
    }

    /**
     * Returns true if $regex matches $actual.
     *
     * Takes database specific quotes into account by removing them. So, this won't
     * work if you have actual quotes in your strings.
     */
    public function testAssertSqlHas($needle, $haystack)
    {
        $needle = \str_replace([
            '"',
            '`'], '', $needle);
        $haystack = \str_replace([
            '"',
            '`'], '', $haystack);
        return $this->assertContains($needle, $haystack);
    }

    public function testAssertSqlDoesNotContain($needle, $haystack)
    {
        $needle = \str_replace([
            '"',
            '`'], '', $needle);
        $haystack = \str_replace([
            '"',
            '`'], '', $haystack);
        return $this->assertNotContains($needle, $haystack);
    }

}