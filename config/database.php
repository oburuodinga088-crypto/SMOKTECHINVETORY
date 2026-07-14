<?php
// Set a consistent application timezone for PHP date functions.
// Use Africa/Nairobi as the default timezone for this inventory system.
date_default_timezone_set('Africa/Nairobi');
define('APP_TIMEZONE', 'Africa/Nairobi');

$host = "localhost";
$dbname = "smoketech_inventory";
$username = "root";
$password = "";

// Only create the connection if it doesn't already exist
if (!isset($conn)) {
    try {
        $conn = new PDO(
            "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
            $username,
            $password,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );

        // Ensure MySQL uses the same timezone for NOW() and timestamp fields.
        $conn->exec("SET time_zone = '+03:00'");
    } catch (PDOException $e) {
        error_log('SmokeTech database connection failed: ' . $e->getMessage());
        http_response_code(500);
        exit('Database connection is currently unavailable. Please contact the administrator.');
    }
}

if (!function_exists('getDB')) {
    function getDB(): PDO {
        global $conn;
        return $conn;
    }
}
