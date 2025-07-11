<?php

$host = 'localhost'; // Database host
$dbname = 'web1211039_project'; // Database name
$username = 'web1211039'; // Database username
$password = '5pn2Yu_G7h'; // Database password

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); // Set error mode to exceptions

    return $pdo;
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage()); // Handle connection errors
}
