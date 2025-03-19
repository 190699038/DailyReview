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
            
            $stmt = $conn->prepare("SELECT wg.*,d.department_name 
                FROM weekly_goals wg
                INNER JOIN departments d ON wg.department_id = d.id
                WHERE mondayDate = ? AND wg.department_id = ?  ORDER BY executor,priority DESC");
            $stmt->execute([$mondayDate, $departmentId]);
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
            break;

        case 'batch_create':
            $postData = $_POST;//json_decode(file_get_contents('php://input'), true);
            if (!is_array($postData)) {
                throw new Exception('无效的批量数据格式');
            }

            $conn->beginTransaction();
            try {
                $results = [];
                foreach ($postData as $item) {
                    $allowedFields = ['department_id', 'executor', 'executor_id', 'weekly_goal', 'is_new_goal', 'mondayDate','priority','status'];
                    $fields = [];
                    $placeholders = [];
                    $values = [];
                    $executor = "";
                    $executorId = "";

                    foreach ($allowedFields as $field) {
                        if (isset($item[$field])) {
                            $fields[] = $field;
                            $placeholders[] = '?';
                            if ($field === 'executor') {
                                $executor = $item[$field];
                            }
                            if ($field === 'executor_id') {
                                $executorId = $item[$field];
                            }
                            $values[] = $item[$field];
                        }
                    }

                    if (!in_array('mondayDate', $fields) || !is_numeric($item['mondayDate'])) {
                        throw new Exception('mondayDate是必填且必须是有效日期格式');
                    }

                    if (!in_array('is_new_goal', $fields)) {
                        $fields[] = 'is_new_goal';
                        $placeholders[] = '?';
                        $values[] = 0;
                    }

                    $fields[] = 'createdate';
                    $placeholders[] = '?';
                    $values[] = date('Ymd');

                    $stmt = $conn->prepare("INSERT INTO weekly_goals (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $placeholders) . ");");
                    $stmt->execute($values);
                    $weeklyGoalId = $conn->lastInsertId();

                    if ($executor != '' && $executorId != '') {
                        $executorIds = explode('/', $executorId);
                        $executors = explode('/', $executor);

                        if (count($executorIds) !== count($executors)) {
                            throw new Exception('executor_id和executor参数长度不一致');
                        }

                        $dailyStmt = $conn->prepare("INSERT INTO daily_goals (" . implode(', ', array_merge($fields, ['weekly_goals_id'])) . ") VALUES (" . implode(', ', array_merge($placeholders, ['?'])) . ");");

                        foreach ($executorIds as $index => $executorId) {
                            $dailyValues = array_merge($values, [$weeklyGoalId]);
                            $dailyValues[array_search('executor_id', $fields)] = $executorId;
                            $dailyValues[array_search('executor', $fields)] = $executors[$index];
                            
                            $dailyStmt->execute($dailyValues);
                        }
                    }

                    $results[] = $weeklyGoalId;
                }
                $conn->commit();
                echo json_encode($results);
            } catch (Exception $e) {
                $conn->rollBack();
                throw $e;
            }
            break;

        case 'create':
            // 允许创建的字段白名单
            $allowedFields = ['department_id', 'executor', 'executor_id', 'weekly_goal', 'is_new_goal', 'mondayDate','priority','status'];
            
            // 动态收集参数并验证必填字段
            $fields = [];
            $placeholders = [];
            $values = [];
            

            $executor = "";
            $executorId = "";
            foreach ($allowedFields as $field) {
                if (isset($_REQUEST[$field])) {
                    $fields[] = $field;
                    $placeholders[] = '?';
                    if ($field === 'executor') {
                        $executor = $_REQUEST[$field];
                    }
                    if ($field === 'executor_id') {
                        $executorId = $_REQUEST[$field];
                    }
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
            
            $conn->beginTransaction();
            $stmt->execute($values);
            $weeklyGoalId = $conn->lastInsertId();
            // 拆分执行人信息
            if ( $executor != '' && $executorId != '' ) {
            
                $executorIds = explode('/',$executorId);
                $executors = explode('/', $executor);
                
                if (count($executorIds) !== count($executors)) {
                    throw new Exception('executor_id和executor参数长度不一致');
                }

                // 准备daily_goals插入
                $dailyFields = array_merge($fields, ['weekly_goals_id']);
                $dailyPlaceholders = array_merge($placeholders, ['?']);
                $dailyValues = array_merge($values, ['']);

                $dailyStmt = $conn->prepare("INSERT INTO daily_goals (" 
                    . implode(', ', $dailyFields) . ") VALUES (" . implode(', ', $dailyPlaceholders) . ");");

                foreach ($executorIds as $index => $executorId) {
                    $dailyValues[array_search('executor_id', $dailyFields)] = $executorId;
                    $dailyValues[array_search('executor', $dailyFields)] = $executors[$index];
                    $dailyValues[array_search('weekly_goals_id', $dailyFields)] = $weeklyGoalId;
                    
                    $dailyStmt->execute($dailyValues);
                }
            }

            $conn->commit();
            echo json_encode(['id' => $weeklyGoalId]);
            break;

        case 'update':
            $id = $_REQUEST['id'];
            
            // 允许更新的字段列表
            $allowedFields = ['department_id', 'executor', 'executor_id', 'weekly_goal', 'is_new_goal','mondayDate','priority','status'];
            $updates = [];
            $params = [];
            $executor = "";
            $executorId = "";
            foreach ($allowedFields as $field) {
                if (isset($_REQUEST[$field])) {
                    $updates[] = "$field = ?";
                    if ($field === 'executor') {
                        $executor = $_REQUEST[$field];
                    }
                    if ($field === 'executor_id') {
                        $executorId = $_REQUEST[$field];
                    }
                    $params[] = $_REQUEST[$field];
                }
            }

            if (empty($updates)) {
                throw new Exception('至少需要一个更新字段');
            }

            $params[] = $id; // 最后添加where条件参数
            $sql = "UPDATE weekly_goals SET " . implode(', ', $updates) . " WHERE id = ?";
            
            $conn->beginTransaction();
            try {
                $stmt = $conn->prepare($sql);
                $stmt->execute($params);
                
                // 获取更新后的周目标数据
                $stmt = $conn->prepare("SELECT * FROM weekly_goals WHERE id = ?");
                $stmt->execute([$id]);
                $weeklyGoal = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$weeklyGoal) {
                    throw new Exception('周目标不存在');
                }
                
                // 处理执行人信息
                if ($executor !== '' && $executorId !== '') {
                    $executorIds = explode('/', $executorId);
                    $executors = explode('/', $executor);

                    // 获取当前已关联的执行人ID
                    $checkStmt = $conn->prepare("SELECT executor_id FROM daily_goals WHERE weekly_goals_id = ?");
                    $checkStmt->execute([$id]);
                    $existingIds = $checkStmt->fetchAll(PDO::FETCH_COLUMN);

                    // 删除不存在于新列表的旧执行人
                    $idsToDelete = array_diff($existingIds, $executorIds);
                    if (!empty($idsToDelete)) {
                        $placeholders = rtrim(str_repeat('?,', count($idsToDelete)), ',');
                        $deleteStmt = $conn->prepare("DELETE FROM daily_goals 
                            WHERE weekly_goals_id = ? 
                            AND executor_id IN ($placeholders)");
                        $deleteParams = array_merge([$id], $idsToDelete);
                        $deleteStmt->execute($deleteParams);
                    }
                    
                    if (count($executorIds) !== count($executors)) {
                        throw new Exception('executor_id和executor参数长度不一致');
                    }
                    
                    // 插入daily_goals记录
                    foreach ($executorIds as $index => $eid) {
                        // 检查是否已存在
                        $checkStmt = $conn->prepare("SELECT id FROM daily_goals WHERE weekly_goals_id = ? AND executor_id = ?");
                        $checkStmt->execute([$id, $eid]);
                        if (!$checkStmt->fetch()) {
                            // 构建插入字段和值
                            $insertFields = [
                                'department_id',
                                'executor',
                                'executor_id',
                                'weekly_goal',
                                'is_new_goal',
                                'mondayDate',
                                'priority',
                                'status',
                                'weekly_goals_id',
                                'createdate'
                            ];
                            $insertValues = [
                                $weeklyGoal['department_id'],
                                $executors[$index],
                                $eid,
                                $weeklyGoal['weekly_goal'],
                                $weeklyGoal['is_new_goal'],
                                $weeklyGoal['mondayDate'],
                                $weeklyGoal['priority'],
                                $weeklyGoal['status'],
                                $id,
                                date('Ymd')
                            ];
                            $placeholders = implode(', ', array_fill(0, count($insertValues), '?'));
                            $insertStmt = $conn->prepare("INSERT INTO daily_goals (" . implode(', ', $insertFields) . ") VALUES ($placeholders)");
                            $insertStmt->execute($insertValues);
                        }
                    }
                }
                
                $conn->commit();
            } catch (Exception $e) {
                $conn->rollBack();
                throw $e;
            }
            
            echo json_encode(['updated' => $stmt->rowCount()]);
            break;

        case 'delete':
            $id = $_REQUEST['id'];
            $conn->beginTransaction();
            try {
                // 删除关联日目标
                $dailyStmt = $conn->prepare("DELETE FROM daily_goals WHERE weekly_goals_id = ?");
                $dailyStmt->execute([$id]);
                $dailyDeleted = $dailyStmt->rowCount();

                // 删除周目标
                $weeklyStmt = $conn->prepare("DELETE FROM weekly_goals WHERE id = ?");
                $weeklyStmt->execute([$id]);
                $weeklyDeleted = $weeklyStmt->rowCount();

                $conn->commit();
                echo json_encode([
                    'deleted' => $weeklyDeleted,
                    'related_daily_deleted' => $dailyDeleted
                ]);
            } catch(Exception $e) {
                $conn->rollBack();
                throw $e;
            }
            break;

                case 'batch_create':
            // 已在上方处理

        default:
            http_response_code(400);
            echo json_encode(['error' => '无效的操作类型']);
    }
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => '数据库操作失败: ' . $e->getMessage()]);
}