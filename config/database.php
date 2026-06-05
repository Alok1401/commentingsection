<?php
/**
 * Database Connection Configuration
 * Multilingual Comment System v1.0
 */

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'comment_system');

$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if (!$conn) {
    // Try creating the database if it doesn't exist
    $tempConn = mysqli_connect(DB_HOST, DB_USER, DB_PASS);
    if ($tempConn) {
        mysqli_query($tempConn, "CREATE DATABASE IF NOT EXISTS " . DB_NAME . " CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        mysqli_close($tempConn);
        $conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    }
    if (!$conn) {
        die(json_encode(['error' => 'Database connection failed: ' . mysqli_connect_error()]));
    }
}

mysqli_set_charset($conn, "utf8mb4");

// CORS headers for API
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}
?>
