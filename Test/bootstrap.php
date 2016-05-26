<?php
require_once '../vendor/autoload.php';
require_once '../Test/Helpers/DatabaseTest.php';
require_once 'Helpers/AdapterTest.php';
/*
use Activerecord\Test\Helpers\DatabaseTest;

if (\getenv('LOG') !== 'false') DatabaseTest::$log = true;

Activerecord\Config::initialize(function($cfg)
{
    $cfg->setModelDirectory(\realpath(__DIR__.'/../Models'));
    $cfg->setConnections(array(
        'mysql' => \getenv('PHPAR_MYSQL') ? : 'mysql://test:test@127.0.0.1/test',
        'pgsql' => \getenv('PHPAR_PGSQL') ? : 'pgsql://test:test@127.0.0.1/test',
        'oci' => \getenv('PHPAR_OCI') ? : 'oci://test:test@127.0.0.1/dev',
        'sqlite' => \getenv('PHPAR_SQLITE') ? : 'sqlite://test.db'));

    $cfg->setDefaultConnection('mysql');

    for ($i = 0; $i < \count($GLOBALS['argv']); ++$i)
    {
        if ($GLOBALS['argv'][$i] == '--adapter')
        {
            $cfg->setDefaultConnection($GLOBALS['argv'][$i + 1]);
        }
        elseif ($GLOBALS['argv'][$i] == '--slow-tests')
        {
            $GLOBALS['slow_tests'] = true;
        }
    }

    if (\class_exists('LogFile')) // PEAR Log installed
    {
        $logger = new LogFile(\dirname(__FILE__).'/../log/query.log', 'ident',
                array(
            'mode' => 0664,
            'timeFormat' => '%Y-%m-%d %H:%M:%S'));

        $cfg->setLogging(true);
        $cfg->setLogger($logger);
    }
    else
    {
        // if ($GLOBALS['show_warnings'] && !isset($GLOBALS['show_warnings_done'])) org
        if (!isset($GLOBALS['show_warnings_done']))
        {
            echo "(Logging SQL queries disabled, PEAR::Log not found.)\n";
        }

        DatabaseTest::$log = false;
    }

    // if ($GLOBALS['show_warnings'] && !isset($GLOBALS['show_warnings_done'])) org
    if (!isset($GLOBALS['show_warnings_done']))
    {
        if (!\extension_loaded('memcache'))
        {
            echo "(Cache Tests will be skipped, Memcache not found.)\n";
        }
    }

    \date_default_timezone_set('UTC');

    $GLOBALS['show_warnings_done'] = true;
});

error_reporting(E_ALL | E_STRICT);
*/