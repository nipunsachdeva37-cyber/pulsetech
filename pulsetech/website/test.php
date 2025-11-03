<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Testing Database Connection...</h2>";

// Database credentials - NEW USER
$host = 'localhost';
$user = 'pulsetech_user';
$pass = 'pulsetech123';
$db = 'pulsetech_news';

// Test connection
$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("<p style='color:red'>❌ Connection failed: " . $conn->connect_error . "</p>");
}

echo "<p style='color:green'>✅ Database connected successfully!</p>";

// Test if articles table exists
$result = $conn->query("SELECT COUNT(*) as count FROM articles");
if ($result) {
    $row = $result->fetch_assoc();
    echo "<p style='color:green'>✅ Articles table exists! Total articles: " . $row['count'] . "</p>";
} else {
    echo "<p style='color:red'>❌ Articles table not found: " . $conn->error . "</p>";
}

// Test if tags table exists
$result = $conn->query("SELECT COUNT(*) as count FROM tags");
if ($result) {
    $row = $result->fetch_assoc();
    echo "<p style='color:green'>✅ Tags table exists! Total tags: " . $row['count'] . "</p>";
} else {
    echo "<p style='color:red'>❌ Tags table not found: " . $conn->error . "</p>";
}

$conn->close();

echo "<hr><p><a href='index.php'>Try Index Page →</a></p>";
?>