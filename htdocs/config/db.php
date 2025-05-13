<?php
// config/db.php

// Database configuration
define('DB_HOST', 'sql313.infinityfree.com');
define('DB_USER', 'if0_38838448');             // Change if your MySQL user is different
define('DB_PASS', 'hh65lDBcTKQW');                 // Change if your MySQL password is set
define('DB_NAME', 'if0_38838448_university_attendance');

// Create connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// Optionally, set charset to utf8mb4 for better Unicode support
$conn->set_charset('utf8mb4');
?>
