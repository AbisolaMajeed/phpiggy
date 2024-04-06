<?php

include __DIR__ . '/src/Framework/Database.php';

use Framework\Database;

$db = new Database('mysql',[
    'host' => 'localhost',
    'port' => 3306,
    'dbname' => 'phpiggy'
], 'root', '');

$search = "Hats' OR 1=1 --'"; //SQL does not process code written inside comment
$query = "SELECT * FROM products WHERE name = '{$search}'";

echo $query;
$stmt = $db->connection->query($query, PDO::FETCH_ASSOC);

var_dump($stmt->fetchAll(PDO::FETCH_OBJ));

echo "Connected to database";