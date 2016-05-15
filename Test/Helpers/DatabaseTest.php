<?php

namespace Test\Helpers;

require_once __DIR__.'/DatabaseLoader.php';

class DatabaseTest
        extends SnakeCase_PHPUnit_Framework_TestCase
{

    protected $conn;
    public static $log = false;
    public static $db;

    public function set_up($connection_name = null)
    {
        Activerecord\Table::clear_cache();

        $config = Activerecord\Config::instance();
        $this->original_default_connection = $config->get_default_connection();

        $this->original_date_class = $config->get_date_class();

        if ($connection_name) $config->set_default_connection($connection_name);

        if ($connection_name == 'sqlite' || $config->get_default_connection() == 'sqlite')
        {
            // need to create the db. the adapter specifically does not create it for us.
            static::$db = substr(Activerecord\Config::instance()->get_connection('sqlite'),
                    9);
            new SQLite3(static::$db);
        }

        $this->connection_name = $connection_name;
        try
        {
            $this->conn = Activerecord\ConnectionManager::get_connection($connection_name);
        }
        catch (Activerecord\DatabaseException $e)
        {
            $this->mark_test_skipped($connection_name.' failed to connect. '.$e->getMessage());
        }

        $GLOBALS['Activerecord_LOG'] = false;

        $loader = new DatabaseLoader($this->conn);
        $loader->reset_table_data();

        if (self::$log) $GLOBALS['Activerecord_LOG'] = true;
    }

    public function tear_down()
    {
        Activerecord\Config::instance()->set_date_class($this->original_date_class);
        if ($this->original_default_connection) Activerecord\Config::instance()->set_default_connection($this->original_default_connection);
    }

    public function assert_exception_message_contains($contains, $closure)
    {
        $message = "";

        try
        {
            $closure();
        }
        catch (Activerecord\UndefinedPropertyException $e)
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
    public function assert_sql_has($needle, $haystack)
    {
        $needle = str_replace(array(
            '"',
            '`'), '', $needle);
        $haystack = str_replace(array(
            '"',
            '`'), '', $haystack);
        return $this->assertContains($needle, $haystack);
    }

    public function assert_sql_doesnt_has($needle, $haystack)
    {
        $needle = str_replace(array(
            '"',
            '`'), '', $needle);
        $haystack = str_replace(array(
            '"',
            '`'), '', $haystack);
        return $this->assertNotContains($needle, $haystack);
    }

}