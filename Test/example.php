<?php
$handle = new \PDO('sqlite:Fixtures\test1.db') or die("Could not open
database");
var_dump($handle);
$query = "SELECT * FROM student";
