<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = 'localhost:3306';
$dbname = 'book_swap_db';
$user = 'root';
$password = '';

// Create connection with error handling
$connectNow = new mysqli($host, $user, $password, $dbname);

// Check connection
if ($connectNow->connect_error) {
    die("Connection failed: " . $connectNow->connect_error);
}

// Set charset to utf8
$connectNow->set_charset("utf8");
?>