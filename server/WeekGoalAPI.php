<?php
require __DIR__ . '/db_connect.php';

$action = $_REQUEST['action'] ?? '';

try {
    switch ($action) {
         case 'list':
            // 参数白名单和验证
            $allowedParams = ['department_id', 'startDate', 'endDate','country','mondayDates'];
            
            // 验证必传参数
            if (!isset($_REQUEST['mondayDates']) || empty($_REQUEST['mondayDates'])) {
                throw new Exception('mondayDates参数必传');
            }
            
            $mondayDates = $_REQUEST['mondayDates'];
            
            // 安全处理：将逗号分隔的日期转为整数数组，防止SQL注入
            $dateArray = array_filter(array_map('intval', explode(',', $mondayDates)));
            if (empty($dateArray)) {
                throw new Exception('mondayDates参数格式无效');
            }
            $placeholders = implode(',', array_fill(0, count($dateArray), '?'));
            
            // 构建查询条件
            $conditions = [];
            $params = [];
            
            // department_id 如果为0则不参与查询
            if (isset($_REQUEST['department_id']) && $_REQUEST['department_id'] != 0) {
                $conditions[] = 'wg.department_id = ?';
                $params[] = intval($_REQUEST['department_id']);
            }
            
            // country 如果为空则不参与查询
            if (isset($_REQUEST['country']) && !empty($_REQUEST['country'])) {
                $conditions[] = 'wg.country = ?';
                $params[] = $_REQUEST['country'];
            }
            
            // 基础SQL
            $sql = "SELECT wg.*, d.department_name 
                   FROM weekly_goals wg 
                   INNER JOIN departments d ON wg.department_id = d.id 
                   WHERE wg.mondayDate IN ($placeholders)";
            
            // 添加mondayDates参数
            $queryParams = $dateArray;
            
            // 添加其他动态条件
            if (!empty($conditions)) {
                $sql .= ' AND ' . implode(' AND ', $conditions);
                $queryParams = array_merge($queryParams, $params);
            }
            
            $sql .= ' ORDER BY wg.mondayDate ASC';
            
            $stmt = $conn->prepare($sql);
            $stmt->execute($queryParams);
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
            break;
        case 'get':
            // 参数白名单和验证
            $allowedParams = ['mondayDate', 'department_id', 'executor', 'real_finish_date','status', 'pre_finish_date','createdate','country','priority'];
            $conditions = [];
            $params = [];

            // 基础参数校验
            if (!isset($_REQUEST['department_id']) || !is_numeric($_REQUEST['department_id'])) {
                throw new Exception('department_id参数必传且必须为数字');
            }
            $departmentId = (int)$_REQUEST['department_id'];
            
            if (!isset($_REQUEST['mondayDate']) || !is_numeric($_REQUEST['mondayDate'])) {
                throw new Exception('mondayDate参数必须为有效日期格式');
            }
            $mondayDate = $_REQUEST['mondayDate'];

            // 动态构建查询条件
            if ($departmentId != 0) {
                $conditions[] = 'wg.department_id = ?';
                $params[] = $departmentId;
            }

            foreach ($allowedParams as $param) {
                if (isset($_REQUEST[$param]) && !empty($_REQUEST[$param])) {
                    switch ($param) {
                        case 'executor':
                            $conditions[] = 'wg.executor LIKE ?';
                            $params[] = '%' . $_REQUEST[$param] . '%';
                            break;
                        case 'real_finish_date':
                        case 'pre_finish_date':
                        case 'createdate':
                            if (!preg_match('/^\d{8}$/', $_REQUEST[$param])) {
                                throw new Exception($param.'格式错误，应为YYYYMMDD');
                            }
                            $conditions[] = $param.' = ?';
                            $params[] = $_REQUEST[$param];
                            break;
                        case 'country':
                        case 'priority':
                            $conditions[] = $param.' = ?';
                            $params[] = $_REQUEST[$param];
                            break;
                            break;

                    }
                }
            }

            // 基础SQL
            $sql = "SELECT wg.*, d.department_name 
                   FROM weekly_goals wg
                   INNER JOIN departments d ON wg.department_id = d.id
                   WHERE mondayDate = ?";
            
            // 添加动态条件
            if (!empty($conditions)) {
                $sql .= ' AND ' . implode(' AND ', $conditions);
            }
            
             if ($departmentId != 0) {
                $sql .= ' ORDER BY priority DESC, version DESC ';
            }else{
                $sql .= ' ORDER BY department_id DESC, executor DESC ';

            }


            // echo($sql);
            // 合并查询参数
            $queryParams = array_merge([$mondayDate], $params);
            
            $stmt = $conn->prepare($sql);
            $stmt->execute($queryParams);
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));

            
            
            break;

        case 'getWeekPeriod':
            $stmt = $conn->prepare("SELECT DISTINCT(mondayDate) FROM weekly_goals ORDER BY mondayDate DESC LIMIT 20");
            $stmt->execute();
            echo json_encode($stmt->fetchAll(PDO::FETCH_COLUMN, 0));
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
            $allowedFields = ['department_id', 'executor', 'executor_id', 'weekly_goal', 'is_new_goal', 'mondayDate','priority','status','remark','pre_finish_date','real_finish_date','country','version','cross_week','process'];
            
            // 动态收集参数并验证必填字段
            $fields = [];
            $placeholders = [];
            $values = [];

            if( !isset($_REQUEST['remark'])){
                $_REQUEST['remark'] = '';
            }

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
            
            $stmt->execute($values);
            $weeklyGoalId = $conn->lastInsertId();

            echo json_encode(['id' => $weeklyGoalId]);
            break;

        case 'update':
            $id = $_REQUEST['id'];
            
            // 允许更新的字段列表
            $allowedFields = ['department_id', 'executor', 'executor_id', 'weekly_goal', 'is_new_goal','mondayDate','priority','status','remark','pre_finish_date','real_finish_date','country','version','cross_week','process'];
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
                
                $conn->commit();
            } catch (Exception $e) {
                $conn->rollBack();
                throw $e;
            }
            
            echo json_encode(['updated' => $stmt->rowCount()]);
            break;

        case 'delete':
            $id = $_REQUEST['id'];
            $stmt = $conn->prepare("DELETE FROM weekly_goals WHERE id = ?");
            $stmt->execute([$id]);
            echo json_encode(['deleted' => $stmt->rowCount()]);
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