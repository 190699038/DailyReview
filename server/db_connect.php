<?php
require_once __DIR__ . '/env_loader.php';

$allowedOrigins = array_map('trim', explode(',', ($_ENV['CORS_ORIGIN'] ?? '') ?: 'https://daily.gameyzy.com'));
$requestOrigin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($requestOrigin, $allowedOrigins, true)) {
    header('Access-Control-Allow-Origin: ' . $requestOrigin);
} else {
    header('Access-Control-Allow-Origin: ' . $allowedOrigins[0]);
}
header('Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$dbServer = ($_ENV['DB_SERVER'] ?? '') ?: '127.0.0.1';
$dbUsername = ($_ENV['DB_USERNAME'] ?? '') ?: 'DailyReview';
$dbPassword = ($_ENV['DB_PASSWORD'] ?? '') ?: 'DailyReview123';
$dbName = ($_ENV['DB_NAME'] ?? '') ?: 'dailyreview';

try {
    $conn = new PDO("mysql:host=" . $dbServer . ";dbname=" . $dbName, $dbUsername, $dbPassword);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->exec("set names utf8");

} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => '数据库连接失败: ' . $e->getMessage()]);
    exit;
}
?>