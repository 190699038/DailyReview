<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');



if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require __DIR__ . '/db_connect.php';

$action = $_REQUEST['action'] ?? '';

try {
    switch ($action) {
        case 'get':
            if (!isset($_REQUEST['department_id']) || !is_numeric($_REQUEST['department_id'])) {
                throw new Exception('department_id参数必传且必须为数字');
            }
            $mondayDate = $_REQUEST['mondayDate'];
            $departmentId = $_REQUEST['department_id'];
            
            $stmt = $conn->prepare("SELECT wg.*, u.partner_name, d.department_name 
                FROM weekly_goals wg
                INNER JOIN users u ON wg.executor_id = u.id
                INNER JOIN departments d ON wg.department_id = d.id
                WHERE mondayDate = ? AND wg.department_id = ?  ORDER BY priority DESC");
            $stmt->execute([$mondayDate, $departmentId]);
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
            break;

        case 'create':
            // 允许创建的字段白名单
            $allowedFields = ['department_id', 'executor', 'executor_id', 'weekly_goal', 'is_new_goal', 'mondayDate','priority','status'];
            
            // 动态收集参数并验证必填字段
            $fields = [];
            $placeholders = [];
            $values = [];
            
            foreach ($allowedFields as $field) {
                if (isset($_REQUEST[$field])) {
                    $fields[] = $field;
                    $placeholders[] = '?';
                    $values[] = $_REQUEST[$field];
                }
            }

            // 验证必填字段
            if (!in_array('mondayDate', $fields) || !is_numeric($_REQUEST['mondayDate'])) {
                throw new Exception('mondayDate是必填且必须是有效日期格式');
            }

            // 设置默认值
            if (!in_array('is_new_goal', $fields)) {
                $fields[] = 'is_new_goal';
                $placeholders[] = '?';
                $values[] = 0; // 默认false
            }
            
            // 添加自动生成的创建日期
            $fields[] = 'createdate';
            $placeholders[] = '?';
            $values[] = date('Ymd');

            $stmt = $conn->prepare("INSERT INTO weekly_goals (" 
                . implode(', ', $fields) . ") VALUES (" . implode(', ', $placeholders) . ");");
            
            $stmt->execute($values);
            echo json_encode(['id' => $conn->lastInsertId()]);
            break;

        case 'update':
            $id = $_REQUEST['id'];
            
            // 允许更新的字段列表
            $allowedFields = ['department_id', 'executor', 'executor_id', 'weekly_goal', 'is_new_goal','mondayDate','priority','status'];
            $updates = [];
            $params = [];

            foreach ($allowedFields as $field) {
                if (isset($_REQUEST[$field])) {
                    $updates[] = "$field = ?";
                    $params[] = $_REQUEST[$field];
                }
            }

            if (empty($updates)) {
                throw new Exception('至少需要一个更新字段');
            }

            $params[] = $id; // 最后添加where条件参数
            $sql = "UPDATE weekly_goals SET " . implode(', ', $updates) . " WHERE id = ?";
            
            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
            echo json_encode(['updated' => $stmt->rowCount()]);
            break;

        case 'delete':
            $id = $_REQUEST['id'];
            $stmt = $conn->prepare("DELETE FROM weekly_goals WHERE id = ?");
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