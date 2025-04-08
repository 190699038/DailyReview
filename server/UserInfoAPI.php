<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}
require_once 'db_connect.php';

try {
    $action = $_REQUEST['action'] ?? '';

    switch ($action) {
        case 'get_all_users':
            // 4.1 查询部门用户（联表查询部门名称）
            $stmt = $conn->prepare("SELECT u.*, d.department_name FROM users u 
                                  JOIN departments d ON u.department_id = d.id 
                                  WHERE u.is_active = 1");
            $stmt->execute();
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['data' => $users]);
            break;
        case 'get_users':
            // 4.1 查询部门用户（联表查询部门名称）
            $departmentId = $_REQUEST['department_id'];
            $stmt = $conn->prepare("SELECT u.*, d.department_name FROM users u 
                                  JOIN departments d ON u.department_id = d.id 
                                  WHERE u.department_id = :department_id AND u.is_active = 1");
            $stmt->bindParam(':department_id', $departmentId, PDO::PARAM_INT);
            $stmt->execute();
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['data' => $users]);
            break;

        case 'add_user':
            // 4.2 新增用户（显式设置is_active为true）
            $stmt = $conn->prepare("INSERT INTO users 
                (partner_name, mode, department_id, position, is_active) 
                VALUES (:partner_name, :mode, :department_id, :position, 1)");
            
            $stmt->bindParam(':partner_name', $_REQUEST['partner_name'], PDO::PARAM_STR);
            $stmt->bindParam(':mode', $_REQUEST['mode'], PDO::PARAM_STR);
            $stmt->bindParam(':department_id', $_REQUEST['department_id'], PDO::PARAM_INT);
            $stmt->bindParam(':position', $_REQUEST['position'], PDO::PARAM_STR);
            
            $stmt->execute();
            echo json_encode(['success' => $stmt->rowCount() > 0, 'id' => $conn->lastInsertId()]);
            break;

        case 'update_user':
            // 4.3 更新用户（支持布尔值转换）
            $isActive = $_REQUEST['is_active'];
            
            $stmt = $conn->prepare("UPDATE users SET 
                partner_name = :partner_name, 
                mode = :mode,
                department_id = :department_id,
                position = :position,
                is_active = :is_active
                WHERE id = :id");
            
            $stmt->bindParam(':partner_name', $_REQUEST['partner_name'], PDO::PARAM_STR);
            $stmt->bindParam(':mode', $_REQUEST['mode'], PDO::PARAM_STR);
            $stmt->bindParam(':department_id', $_REQUEST['department_id'], PDO::PARAM_INT);
            $stmt->bindParam(':position', $_REQUEST['position'], PDO::PARAM_STR);
            $stmt->bindParam(':is_active', $isActive, PDO::PARAM_INT);
            $stmt->bindParam(':id', $_REQUEST['id'], PDO::PARAM_INT);
            
            

            $stmt->execute();
            echo json_encode(['success' => $stmt->rowCount() > 0]);
            break;

        case 'get_departments':
            // 4.4 查询所有部门
            $stmt = $conn->query("SELECT * FROM departments");
            $departments = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['data' => $departments]);
            break;

        default:
            http_response_code(400);
            echo json_encode(['error' => '无效的操作类型']);
            exit;
    }

} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => '数据库错误: ' . $e->getMessage()]);
}

$conn = null;