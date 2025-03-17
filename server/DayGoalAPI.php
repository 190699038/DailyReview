<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');
require __DIR__ . '/db_connect.php';

$action = $_REQUEST['action'] ?? '';

try {
    switch ($action) {
        case 'get':
            $date = $_GET['date'] ?? date('Y-m-d');
            $stmt = $conn->prepare("SELECT * FROM daily_goals WHERE date = ?");
            $stmt->execute([$date]);
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
            break;

        case 'create':
            if($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode(['error' => '仅支持POST方法']);
                break;
            }
            $date = $_REQUEST['date'];
            $executor_id = $_REQUEST['executor_id'];
            $goal_content = $_REQUEST['goal_content'];
            
            $stmt = $conn->prepare("INSERT INTO daily_goals (date, executor_id, goal_content) VALUES (?, ?, ?)");
            $stmt->execute([$date, $executor_id, $goal_content]);
            echo json_encode(['id' => $conn->lastInsertId()]);
            break;

        case 'complete':
            $id = $_REQUEST['id'];
            $stmt = $conn->prepare("UPDATE daily_goals SET is_completed = 1 WHERE id = ?");
            $stmt->execute([$id]);
            echo json_encode(['updated' => $stmt->rowCount()]);
            break;

        case 'update':
            $id = $_POST['id'];
            $goal_content = $_POST['goal_content'];
            $executor_id = $_POST['executor_id'];
            
            $stmt = $conn->prepare("UPDATE daily_goals SET goal_content = ?, executor_id = ? WHERE id = ?");
            $stmt->execute([$goal_content, $executor_id, $id]);
            echo json_encode(['updated' => $stmt->rowCount()]);
            break;

        case 'delete':
            $id = $_POST['id'];
            $stmt = $conn->prepare("DELETE FROM daily_goals WHERE id = ?");
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