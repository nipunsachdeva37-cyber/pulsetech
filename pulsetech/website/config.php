<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

/*
 * ---------------------------------------------------
 * Central Configuration File
 * ---------------------------------------------------
 */

// --- Database Credentials ---
// USE THE NEW USER YOU CREATED IN PHPMYADMIN
define('DB_HOST', 'localhost');
define('DB_USER', 'pulsetech_user');
define('DB_PASS', 'pulsetech123'); 
define('DB_NAME', 'pulsetech_news');

// --- Create Connection ---
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// --- Check Connection ---
if ($conn->connect_error) {
    // If connection fails, stop everything and show the error.
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to avoid character encoding issues
$conn->set_charset("utf8mb4");

?>