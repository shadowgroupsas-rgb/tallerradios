<?php
// config/db.php

// Database Configuration
// Edit these values to match your cPanel MySQL Database credentials
$host = '127.0.0.1';
$db   = 'radio_sim_db';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

// DSN (Data Source Name)
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    // If testing locally (SQLite)
    if (getenv('APP_ENV') === 'local_test') {
        throw new PDOException("Skipping MySQL for local test");
    }
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    if (getenv('APP_ENV') === 'local_test') {
        try {
            // Use SQLite fallback for testing
            $sqlitePath = __DIR__ . '/../database.sqlite';
            $pdo = new PDO('sqlite:' . $sqlitePath);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (Exception $ex) {
             die("SQLite Connection Failed: " . $ex->getMessage());
        }
    } else {
        // If connection fails, log it and show a generic error
        error_log($e->getMessage());
        die("Database connection failed. Please check config/db.php.");
    }
}
?>
