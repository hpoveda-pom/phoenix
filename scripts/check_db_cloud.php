<?php 
require_once('../config.php');
require_once('../models/class_querymysqli.php');
require_once('../models/class_querymysqliexe.php');
require_once('../models/class_connections.php');
require_once('../models/class_connmysqli.php');




$query_checker = "SELECT table_schema, COUNT(*) AS cant FROM information_schema.tables WHERE table_type = 'BASE TABLE' GROUP BY table_schema";
$array_checker = class_queryMysqli(3, $query_checker, null, null, null);

echo "<pre>";
print_r($array_checker);
