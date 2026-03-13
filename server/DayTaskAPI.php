<?php
require __DIR__ . '/db_connect.php';

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
            $progress = $_POST['progress'];
            $is_new_goal = $_POST['is_new_goal'];
            $stmt = $conn->prepare("UPDATE daily_tasks SET time_spent = ?, progress = ?, is_new_goal = ? WHERE id = ?");
            $stmt->execute([$time_spent, $progress, $is_new_goal, $executor_id]);
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
            if($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode(['error' => '仅支持POST方法']);
                break;
            }
            $id = $_POST['id'] ?? '';
            if (empty($id)) {
                http_response_code(400);
                echo json_encode(['error' => '缺少id参数']);
                break;
            }
            $stmt = $conn->prepare("DELETE FROM daily_tasks WHERE id = ?");
            $stmt->execute([$id]);
            echo json_encode(['deleted' => $stmt->rowCount()]);
            break;
        case 'getUserTodayTask':
                $uids = $_POST['uids'] ?? '';
                $start_date = $_POST['start_date'] ?? '';
                $end_date = $_POST['end_date'] ?? '';
                
                // 参数化查询防止SQL注入
                $uidArray = array_filter(array_map('intval', explode(',', $uids)));
                if (empty($uidArray) || empty($start_date) || empty($end_date)) {
                    echo json_encode([]);
                    break;
                }
                $placeholders = implode(',', array_fill(0, count($uidArray), '?'));
                $sql = "SELECT t.*, w.department_name, w.executor_name FROM daily_tasks_today t LEFT JOIN watch_user w ON w.executor_id = t.executor_id WHERE t.createdate >= ? AND t.createdate <= ? AND t.executor_id IN ({$placeholders}) ORDER BY t.createdate DESC, t.executor_id DESC";
                $stmt = $conn->prepare($sql);
                $params = array_merge([intval($start_date), intval($end_date)], $uidArray);
                $stmt->execute($params);
                echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
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

                $date_array = array_filter(explode(',', $date_str));
                if (empty($date_array)) {
                    echo json_encode(['data' => []]);
                    break;
                }

                // 批量查询替代循环查询
                $datePlaceholders = implode(',', array_fill(0, count($date_array), '?'));
                
                $task_stmt = $conn->prepare("SELECT * FROM daily_tasks WHERE date IN ({$datePlaceholders}) AND executor_id = ?");
                $task_stmt->execute(array_merge($date_array, [$executor_id]));
                $allTasks = $task_stmt->fetchAll(PDO::FETCH_ASSOC);

                $task_stmt_today = $conn->prepare("SELECT * FROM daily_tasks_today WHERE date IN ({$datePlaceholders}) AND executor_id = ?");
                $task_stmt_today->execute(array_merge($date_array, [$executor_id]));
                $allTasksToday = $task_stmt_today->fetchAll(PDO::FETCH_ASSOC);

                $maxDate = max($date_array);
                $goal_stmt = $conn->prepare("SELECT * FROM weekly_goals WHERE mondayDate = ? AND (executor_id = ? OR executor_id LIKE ?) AND createdate <= ?");
                $goal_stmt->execute([$monday_date, $executor_id, '%' . $executor_id . '%', $maxDate]);
                $allGoals = $goal_stmt->fetchAll(PDO::FETCH_ASSOC);

                // 按日期分组
                $tasksByDate = [];
                $todayByDate = [];
                foreach ($allTasks as $task) { $tasksByDate[$task['date']][] = $task; }
                foreach ($allTasksToday as $task) { $todayByDate[$task['date']][] = $task; }

                $result = [];
                foreach($date_array as $date) {
                    // 按日期过滤目标（createdate <= 当前日期）
                    $goalsForDate = array_values(array_filter($allGoals, function($g) use ($date) {
                        return $g['createdate'] <= $date;
                    }));
                    $result[] = [
                        'date' => $date,
                        'dailyTasks' => $tasksByDate[$date] ?? [],
                        'dailyGoals' => $goalsForDate,
                        'dailyTasks_today' => $todayByDate[$date] ?? []
                    ];
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

            $idArray = array_filter(array_map('intval', explode(',', $executor_ids)));
            if (empty($idArray)) {
                echo json_encode(['data' => []]);
                break;
            }

            // 批量查询替代循环
            $placeholders = implode(',', array_fill(0, count($idArray), '?'));

            $goal_stmt = $conn->prepare("SELECT * FROM weekly_goals WHERE mondayDate = ? AND executor_id IN ({$placeholders})");
            $goal_stmt->execute(array_merge([$week_period], $idArray));
            $allGoals = $goal_stmt->fetchAll(PDO::FETCH_ASSOC);

            $task_stmt = $conn->prepare("SELECT * FROM daily_tasks WHERE date >= ? AND date < ? AND executor_id IN ({$placeholders})");
            $task_stmt->execute(array_merge([$week_period, $end_date], $idArray));
            $allTasks = $task_stmt->fetchAll(PDO::FETCH_ASSOC);

            // 按 executor_id 分组
            $result = [];
            foreach ($idArray as $eid) {
                $eidStr = (string)$eid;
                $result[$eidStr] = [
                    'dailyGoal' => array_values(array_filter($allGoals, fn($g) => (string)$g['executor_id'] === $eidStr)),
                    'dailyTasks' => array_values(array_filter($allTasks, fn($t) => (string)$t['executor_id'] === $eidStr))
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