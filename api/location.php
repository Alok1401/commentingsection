<?php
/**
 * Location API Endpoint
 * GET /api/location.php - Get user's location from IP
 */

require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=utf-8');

// Get client IP
$ip = $_SERVER['REMOTE_ADDR'];

// For localhost, use a public API to detect external IP
if ($ip === '127.0.0.1' || $ip === '::1') {
    $externalIp = @file_get_contents('https://api.ipify.org');
    if ($externalIp) {
        $ip = $externalIp;
    }
}

// Use ip-api.com for geolocation (free, no API key needed)
$url = "http://ip-api.com/json/{$ip}?fields=status,message,country,city,query";
$context = stream_context_create([
    'http' => [
        'timeout' => 5,
        'method' => 'GET'
    ]
]);

$response = @file_get_contents($url, false, $context);

if ($response) {
    $data = json_decode($response, true);
    if ($data && $data['status'] === 'success') {
        echo json_encode([
            'success' => true,
            'data' => [
                'city' => $data['city'] ?? 'Unknown',
                'country' => $data['country'] ?? 'Unknown',
                'ip' => $data['query']
            ]
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'data' => [
                'city' => 'Unknown',
                'country' => 'Unknown',
                'ip' => $ip
            ]
        ]);
    }
} else {
    echo json_encode([
        'success' => true,
        'data' => [
            'city' => 'Unknown',
            'country' => 'Unknown',
            'ip' => $ip
        ]
    ]);
}
?>
