<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');
require __DIR__ . '/db_connect.php';
// 处理预检请求（OPTIONS 方法）
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    header("HTTP/1.1 200 OK");
    exit();
}
$action = $_REQUEST['action'] ?? '';

try {
    switch ($action) {
        case 'get':
            $date = $_GET['date'] ?? date('Ymd');
            $stmt = $conn->prepare("SELECT * FROM daily_tasks WHERE date = ?");
            $stmt->execute([$date]);
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
            break;

        case 'create':
            $date = $_POST['date'];
            $executor_id = $_POST['executor_id'];
            $task_content = $_POST['task_content'];
            $time_spent = $_POST['time_spent'] ?? 0;
            
            if ($time_spent > 0) {
                $stmt = $conn->prepare("INSERT INTO daily_tasks (date, executor_id, task_content, time_spent) VALUES (?, ?, ?, ?)");
                $stmt->execute([$date, $executor_id, $task_content, $time_spent]);
                echo json_encode(['id' => $conn->lastInsertId()]);
            } else {
                echo json_encode(['error' => '时间消耗必须大于0']);
            }
            break;

        case 'update':
            $id = $_POST['id'];
            $task_content = $_POST['task_content'];
            $progress = $_POST['progress'] ?? 0;
            $time_spent = $_POST['time_spent'] ?? 0;
            
            $stmt = $conn->prepare("UPDATE daily_tasks SET task_content = ?, progress = ?, time_spent = ? WHERE id = ?");
            $stmt->execute([$task_content, $progress, $time_spent, $id]);
            echo json_encode(['updated' => $stmt->rowCount()]);
            break;

        case 'delete':
            $id = $_POST['id'];
            $stmt = $conn->prepare("DELETE FROM daily_tasks WHERE id = ?");
            $stmt->execute([$id]);
            echo json_encode(['deleted' => $stmt->rowCount()]);
            break;

                case 'delete':
            if($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode(['error' => '仅支持POST方法']);
                break;
            }
            $id = $_REQUEST['id'];
            $stmt = $conn->prepare("DELETE FROM daily_tasks WHERE id = ?");
            $stmt->execute([$id]);
            echo json_encode(['deleted' => $stmt->rowCount()]);
            break;

        default:
            http_response_code(400);
            echo json_encode(['error' => '无效的操作类型']);
    }
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => '数据库操作失败: ' . $e->getMessage()]);
}