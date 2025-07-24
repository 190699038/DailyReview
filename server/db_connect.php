<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');

define('DB_SERVER', '127.0.0.1');
define('DB_USERNAME', 'DailyReview');
define('DB_PASSWORD', 'DailyReview123');
define('DB_NAME', 'dailyreview');

try {
    $conn = new PDO("mysql:host=" . DB_SERVER . ";dbname=" . DB_NAME, DB_USERNAME, DB_PASSWORD);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->exec("set names utf8");

} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => '数据库连接失败: ' . $e->getMessage()]);
    exit;
}
?>