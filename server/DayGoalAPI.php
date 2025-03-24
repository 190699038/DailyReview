<?php

use function PHPSTORM_META\type;

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');
require __DIR__ . '/db_connect.php';

$action = $_REQUEST['action'] ?? '';



try {
    switch ($action) {
        case 'getUserGoalAndTasks':
            $last_date = $_POST['last_date'] ?? '';
            $monday_date = $_POST['monday_date'] ?? '';
            $user_ids = $_POST['user_ids'] ?? [];
      
            if(empty($last_date) || empty($monday_date) || empty($user_ids)) {
                http_response_code(400);
                echo json_encode(['error' => '缺少必要参数']);
                break;
            }

            // echo(gettype($executor_ids));
            $executor_ids = explode(',', $user_ids);
            $result = [];
            foreach($executor_ids as $executor_id) {
                // 查询每日任务
                $task_stmt = $conn->prepare("SELECT * FROM daily_tasks WHERE date = ? AND executor_id = ?");
                $task_stmt->execute([$last_date, $executor_id]);
                $dailyTasks = $task_stmt->fetchAll(PDO::FETCH_ASSOC);

                // 查询周目标
                $goal_stmt = $conn->prepare("SELECT * FROM daily_goals WHERE mondayDate = ? AND executor_id = ?");
                $goal_stmt->execute([$monday_date, $executor_id]);
                $dailyGoals = $goal_stmt->fetchAll(PDO::FETCH_ASSOC);

                $result[] = [
                    'executor_id' => $executor_id,
                    'dailyTasks' => $dailyTasks,
                    'dailyGoals' => $dailyGoals
                ];
            }

            echo json_encode(['data' => $result]);
            break;



        case 'get':
            $date = $_REQUEST['date'] ?? date('Ymd');
            $stmt = $conn->prepare("SELECT * FROM daily_goals WHERE createdate = ?");
            $stmt->execute([$date]);
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
            break;

        case 'create':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
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

        case 'get_target':
            $date =  $_REQUEST['report_date'] ?? date('Ymd');
            $department_id =  $_REQUEST['department_id'] ??0;

            // echo($date );
            $stmt = $conn->prepare("SELECT * FROM today_target WHERE report_date = ? AND department_id =?");
            $stmt->execute([$date,$department_id]);
            echo json_encode($stmt->fetch(PDO::FETCH_ASSOC) ?: []);
            break;

        case 'save_target':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode(['error' => '仅支持POST方法']);
                break;
            }
            $date = $_POST['report_date'] ?? date('Ymd');
            $content = $_POST['content'] ?? '';
            $department_id = $_POST['department_id'] ?? 0;

            $stmt = $conn->prepare("INSERT INTO today_target (report_date, content,department_id, message) 
                VALUES (?, ?, ?,'') 
                ON DUPLICATE KEY UPDATE content = VALUES(content)");
            $stmt->execute([$date, $content, $department_id]);
            echo json_encode(['affected_rows' => $stmt->rowCount()]);
            break;
        case 'batch_create':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode(['error' => '仅支持POST方法']);
                break;
            }

            $input =  $_POST;//json_decode(file_get_contents('php://input'), true);

            if (!is_array($input)) {
                http_response_code(400);
                echo json_encode(['error' => '无效的JSON数据']);
                break;
            }

            try {
                $conn->beginTransaction();
    
                $stmt = $conn->prepare("INSERT INTO daily_tasks 
                        (date, day_goal, executor_id, task_content, progress, time_spent, is_new_goal,daily_goals_id, createdate,mondayDate)
                        VALUES (?, ?, ?, ?, ?, ?, ?,?, ?,?)");

                foreach ($input as $item) {
                    //模糊查询周目标是否在目标的标准，查询daily_goals表中的字段weekly_goal like '%$item['day_goal']',返回daily_goals中的表id值
                    // $goalCheckStmt = $conn->prepare("SELECT id FROM daily_goals WHERE executor_id = ? and weekly_goal LIKE ?");
                    // $goalCheckStmt->execute([$item['executor_id'],"%{$item['day_goal']}%"]);
                    // $goal = $goalCheckStmt->fetch(PDO::FETCH_ASSOC);
                    $daily_goals_id = 0;
                    // if ($goal) {
                    //     $$daily_goals_id = $goal['id'];
                    // }

                    echo('SELECT id FROM daily_goals WHERE executor_id = executor_id = '.$item['executor_id'].'  and weekly_goal LIKE %'.$item['day_goal'].'%');


                    // var_dump($item);
                    $stmt->execute([
                        $item['date'],
                        $item['day_goal'],
                        $item['executor_id'],
                        $item['task_content'],
                        $item['progress'] ?? 0,
                        $item['time_spent'] ?? 0,
                        $item['is_new_goal'] ?? 0,
                        $daily_goals_id,
                        date('Ymd'),
                        $item['mondayDate']
                    ]);
                }

                $conn->commit();
                echo json_encode(['inserted' => count($input)]);
            } catch (Exception $e) {
                $conn->rollBack();
                http_response_code(400);
                echo json_encode(['error' => $e->getMessage()]);
            }
            break;

        default:
            http_response_code(400);
            echo json_encode(['error' => '无效的操作类型']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => '数据库操作失败: ' . $e->getMessage()]);
}
