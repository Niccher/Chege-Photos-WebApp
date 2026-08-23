<?php
require 'preload.php';
$db = \Config\Database::connect();
$tables = $db->listTables();
print_r($tables);
