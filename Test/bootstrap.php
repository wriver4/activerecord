<?php
opcache_reset();
//$predefinedClasses = get_declared_classes();
set_include_path(get_include_path().PATH_SEPARATOR.'C:/xampp/php/pear/log');
$loader = require_once '../vendor/autoload.php';
$loader->addPsr4('Test\\Models\\', __dir__.'/Models');
$loader->addPsr4('Test\\Functional\\', __dir__.'/Functional');

use Test\Functional\DatabaseTest;

if (\getenv('LOG') !== 'false')
{
    DatabaseTest::$log = true;
}
// whether or not to run the slow non-crucial tests
$GLOBALS['slow_tests'] = false;

// whether or not to show warnings when Log or Memcache is missing
$GLOBALS['show_warnings'] = true;

Activerecord\Config::initialize(function($cfg)
{
    $cfg->setConnections([
        //'mysql' => \getenv('PHPAR_MYSQL') ? : 'mysql://root:root@127.0.0.1/activerecord_test',
        //'pgsql' => \getenv('PHPAR_PGSQL') ? : 'pgsql://test:test@127.0.0.1/test',
        // 'oci' => \getenv('PHPAR_OCI') ? : 'oci://test:test@127.0.0.1/dev',
        'sqlite' => \getenv('PHPAR_SQLITE') ? : 'sqlite://windows(c%3A/GitHub/activerecord/Test/Fixtures/test.db)']);
//sqlite://windows(c%3A/GitHub/activerecord/Test/Fixtures/test.db)
    $cfg->setDefaultConnection('mysql');
    $cfg->setCache('memcache://localhost:11211');

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
if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN')
{
    $GLOBALS['OS'] = 'WIN';
}
else
{
    $GLOBALS['OS'] = 'X';
}
error_reporting(E_ALL | E_STRICT);

//echo '<pre> after bootstrap  ';
//print_r(array_diff(get_declared_classes(), $predefinedClasses));
//echo '</pre>';
