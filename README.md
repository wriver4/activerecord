# activerecord

Warning working on conforming to PSR-1 starting 5/11/2016 use Ver. 1.1

## Instructions

Uses Composer to manage autoload.
In bootstrap file assuming root directory.
```php
$loader = require_once 'vendor/autoload.php';
$loader->addPsr4('Models\\', __dir__.'\Models');
// What? Why? Composers base path is vendor and I don't want to update Composer for every new Model.

//Simple Config
$cfg = Activerecord\Config::instance();
$cfg->setConnections(array(
    'development' =>
    'mysql://root:root@localhost/treebark?charset=utf8'));
```
Model classes
```php
namespace Models;

class Bar \\ for table name bars by convention
        extends \Activerecord\Model \\ leading slash required
{
}
```
Super Simple Usage Example:
```php
$bars = Models\Bar::find(1)->to_array();
echo $bars['name'];
echo '<br>';
$bars = Models\Bar::all();
foreach ($bars as $bar)
{
    echo '<br> id:';
    echo $bar->id;
    echo '<br> Name of Bar:';
    echo $bar->name;
    echo '<br> Address:';
    echo $bar->address;
    echo '<br>';
}
```
The php-activerecord.org instructions for use remain the same.

### Updated 4/25/2016
A revised implementation of php-activerecord by jpfuentes;
Currently working but testing system incomplete.

### Original Commit 12/9/2015
A revised implementation of php-activerecord by jpfuentes
This is an inprocess version testing is not complete.
So far the php-activerecord.org instructions for use remain the same.
