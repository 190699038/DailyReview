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
        case 'create':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode(['error' => '仅支持POST方法']);
                break;
            }
            
            // 获取前端传递的参数
            $responsible_person = $_POST['responsible_person'] ?? '';
            $priority = $_POST['priority'] ?? '';
            $product = $_POST['product'] ?? '';
            $test_content = $_POST['test_content'] ?? '';
            $test_status = $_POST['test_status'] ?? '';
            $test_progress = $_POST['test_progress'] ?? '';
            $submission_time = $_POST['submission_time'] ?? null;
            $planned_online_time = $_POST['planned_online_time'] ?? '';
            $actual_online_time = $_POST['actual_online_time'] ?? '';
            $actual_time_spent = $_POST['actual_time_spent'] ?? null;
            $creation_date = $_POST['creation_date'] ?? '';
            $remarks = $_POST['remarks'] ?? '';
            
            // 验证必填字段
            if (empty($responsible_person) || empty($test_content) || empty($creation_date)) {
                http_response_code(400);
                echo json_encode(['error' => '缺少必要参数：responsible_person, test_content, creation_date']);
                break;
            }
            
            // 生成MD5密钥
            $md5key = md5($responsible_person . $test_content);
            
            // 查询test_tasks表中是否存在记录
            $stmt = $conn->prepare("SELECT id FROM test_tasks WHERE md5key = ?");
            $stmt->execute([$md5key]);
            $task_record = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($task_record) {
                // 如果存在，获取task_id
                $task_id = $task_record['id'];
            } else {
                // 如果不存在，插入新记录
                $stmt = $conn->prepare("INSERT INTO test_tasks (md5key) VALUES (?)");
                $stmt->execute([$md5key]);
                $task_id = $conn->lastInsertId();
            }
            
            // 查询test_tasks_info表中是否存在记录
            $stmt = $conn->prepare("SELECT id FROM test_tasks_info WHERE task_id = ? AND creation_date = ?");
            $stmt->execute([$task_id, $creation_date]);
            $info_record = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($info_record) {
                // 如果存在，执行更新操作
                $stmt = $conn->prepare("
                    UPDATE test_tasks_info SET 
                    responsible_person = ?, priority = ?, product = ?, test_content = ?, 
                    test_status = ?, test_progress = ?, submission_time = ?, 
                    planned_online_time = ?, actual_online_time = ?, actual_time_spent = ?, 
                    remarks = ? 
                    WHERE task_id = ? AND creation_date = ?
                ");
                $stmt->execute([
                    $responsible_person, $priority, $product, $test_content,
                    $test_status, $test_progress, $submission_time,
                    $planned_online_time, $actual_online_time, $actual_time_spent,
                    $remarks, $task_id, $creation_date
                ]);
                echo json_encode(['success' => true, 'task_id' => $task_id, 'action' => 'updated', 'affected_rows' => $stmt->rowCount()]);
            } else {
                // 如果不存在，插入新记录
                $stmt = $conn->prepare("
                    INSERT INTO test_tasks_info 
                    (task_id, responsible_person, priority, product, test_content, 
                     test_status, test_progress, submission_time, planned_online_time, 
                     actual_online_time, actual_time_spent, creation_date, remarks) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $task_id, $responsible_person, $priority, $product, $test_content,
                    $test_status, $test_progress, $submission_time, $planned_online_time,
                    $actual_online_time, $actual_time_spent, $creation_date, $remarks
                ]);
                echo json_encode(['success' => true, 'task_id' => $task_id, 'action' => 'created', 'id' => $conn->lastInsertId()]);
            }
            break;
            
        case 'query':
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                http_response_code(405);
                echo json_encode(['error' => '仅支持GET方法']);
                break;
            }
            
            // 获取查询参数
            $responsible_person = $_GET['responsible_person'] ?? '';
            $creation_date = $_GET['creation_date'] ?? '';
            $actual_online_time = $_GET['actual_online_time'] ?? '';
            $submission_time = $_GET['submission_time'] ?? '';
            
            // 构建查询条件
            $where_conditions = [];
            $params = [];
            
            if (!empty($responsible_person)) {
                $where_conditions[] = "responsible_person = ?";
                $params[] = $responsible_person;
            }
            
            if (!empty($creation_date)) {
                $where_conditions[] = "creation_date = ?";
                $params[] = $creation_date;
            }
            
            if (!empty($actual_online_time)) {
                $where_conditions[] = "actual_online_time = ?";
                $params[] = $actual_online_time;
            }
            
            if (!empty($submission_time)) {
                $where_conditions[] = "submission_time = ?";
                $params[] = $submission_time;
            }
            
            // 构建SQL查询
            $sql = "SELECT * FROM test_tasks_info";
            if (!empty($where_conditions)) {
                $sql .= " WHERE " . implode(" AND ", $where_conditions);
            }
            $sql .= " ORDER BY id DESC";
            
            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'data' => $results, 'count' => count($results)]);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['error' => '无效的操作类型，支持的操作：create, query']);
    }
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => '数据库操作失败: ' . $e->getMessage()]);
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => '服务器错误: ' . $e->getMessage()]);
}
?>