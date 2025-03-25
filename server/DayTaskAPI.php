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
        case 'updateTask':
            $executor_id = $_POST['id'];
            $time_spent = $_POST['time_spent'];
            $time_spent = $_POST['progress'];
            $is_new_goal = $_POST['is_new_goal'];
            $stmt = $conn->prepare("UPDATE daily_tasks SET time_spent = ?, progress = ?, is_new_goal = ? WHERE id = ?");
            $stmt->execute([$time_spent, $progress, $is_new_goal, $id]);
            echo json_encode(['updated' => $stmt->rowCount()]);
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
        case 'getUserGoalAndTasks':
                $date_str = $_POST['dates'] ?? '';
                $monday_date = $_POST['monday_date'] ?? '';
                $executor_id = $_POST['executor_id'] ?? '';

                if(empty($executor_id) || empty($monday_date) || empty($date_str)) {
                    http_response_code(400);
                    echo json_encode(['error' => '缺少必要参数']);
                    break;
                }

                $date_array = explode(',', $date_str);
                $result = [];
                foreach($date_array as $date) {
                    if( !empty($date)){
                        // 查询每日任务
                        $task_stmt = $conn->prepare("SELECT * FROM daily_tasks WHERE date = ? AND executor_id = ?");
                        $task_stmt->execute([$date, $executor_id]);
                        $dailyTasks = $task_stmt->fetchAll(PDO::FETCH_ASSOC);

                        // 查询周目标
                        $goal_stmt = $conn->prepare("SELECT * FROM daily_goals WHERE mondayDate = ? AND executor_id = ? AND createdate <= ?");
                        $goal_stmt->execute([$monday_date, $executor_id,$date]);
                        $dailyGoals = $goal_stmt->fetchAll(PDO::FETCH_ASSOC);

                        $result[] = [
                            'date' => $date,
                            'dailyTasks' => $dailyTasks,
                            'dailyGoals' => $dailyGoals
                        ];
                    }
                   
                }
    
                echo json_encode(['data' => $result]);
                break;
        case 'get_history':
            $week_period = $_GET['week_period'] ?? '';
            $executor_ids = $_GET['executor_id'] ?? '';
            $end_date = $_GET['end_date'] ?? '';
            
            if(empty($executor_ids) || empty($week_period) || empty($end_date)) {
                http_response_code(400);
                echo json_encode(['error' => '缺少必要参数']);
                break;
            }

            $result = [];
            foreach(explode(',', $executor_ids) as $executor_id) {
                // 查询周目标
                $goal_stmt = $conn->prepare("SELECT * FROM daily_goals WHERE mondayDate = ? AND executor_id = ?");
                $goal_stmt->execute([$week_period, $executor_id]);
                $dailyGoals = $goal_stmt->fetchAll(PDO::FETCH_ASSOC);

                // 查询周任务
                $task_stmt = $conn->prepare("SELECT * FROM daily_tasks 
                                            WHERE date >= ? AND date < ? AND executor_id = ?");
                $task_stmt->execute([$week_period, $end_date, $executor_id]);
                $dailyTasks = $task_stmt->fetchAll(PDO::FETCH_ASSOC);

                $result[$executor_id] = [
                    'dailyGoal' => $dailyGoals,
                    'dailyTasks' => $dailyTasks
                ];
            }
    
            echo json_encode(['data' => $result]);
            break;
        default:
            http_response_code(400);
            echo json_encode(['error' => '无效的操作类型']);
    }
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => '数据库操作失败: ' . $e->getMessage()]);
}