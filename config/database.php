<?php
/**
 * Database Connection — InfinityFree Live
 */
error_reporting(E_ALL);
ini_set('display_errors', 0);

$conn = mysqli_connect('sql206.infinityfree.com', 'if0_42102202', 'HF7xAqaubjweV', 'if0_42102202_comments');

if (!$conn) {
    header('Content-Type: application/json');
    die(json_encode(['error' => 'Database connection failed']));
}

mysqli_set_charset($conn, "utf8");

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}
?>
