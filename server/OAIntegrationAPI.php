<?php

use function PHPSTORM_META\type;

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

require_once 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$department_ids = [
    1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16
];


session_start();

const LOGIN_URL = 'https://oa.aizyun.com/admin/login';
const DAILY_URL = 'https://oa.aizyun.com/admin/dailyplan/list';
const GROUP_URL = 'https://oa.aizyun.com/admin/sys/user/deptlist/';

$headers = [
    'Content-Type: application/json;charset=UTF-8',
    'Authorization: '
];

function loginOA() {
    global $headers;
    $headers = ['Content-Type' => 'application/json;charset=UTF-8'];
    // if (!isset($_SESSION['token'])) {

    if ( 1 == 1) {

        $loginData = json_encode([
            'username' => 'xuexizhanshi',
            'password' => '123456'
        ]);

        $ch = curl_init(LOGIN_URL);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $loginData);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json;charset=UTF-8']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FAILONERROR, true);
        curl_setopt($ch, CURLOPT_VERBOSE, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 测试环境跳过SSL验证
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);

        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        if ($response === false) {
            die("cURL Error: " . curl_error($ch));
        }
        
        if(curl_errno($ch)) {
            error_log('CURL错误: ' . curl_error($ch));
        }
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $data = json_decode($response, true);
        
        if ($data['code'] !== 200) {
            throw new Exception('登录失败');
        }

        $_SESSION['token'] = $data['data']['token'];
    }

    $headers['Authorization'] = $_SESSION['token'];
    return $_SESSION['token'];
}

function formatDate($date) {
    return date('Y-m-d H:i:s', strtotime($date));
}

function handleSync($isToday) {
    global $department_ids, $conn;
    try {
        $token = loginOA();
        // $user_list = getUserList($token);
        $stmt = $conn->prepare("SELECT u.*, d.department_name FROM users u 
                                  JOIN departments d ON u.department_id = d.id ");
        $stmt->execute();
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
       
        $ch = curl_init(GROUP_URL.'0');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json;charset=UTF-8',
            'Authorization: ' . $token
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || !$response) {
            throw new Exception('部门用户获取失败');
        }

        $data = json_decode($response, true);
        if ($data['code'] !== 200) {
            throw new Exception($data['msg'] ?? '接口返回异常');
        }

        $oa_dailytask = [];

        for($i=0;$i<count($users);$i++){
            for($j=0;$j<count($data['data']);$j++){
                if($users[$i]['partner_name'] == $data['data'][$j]['label']){
                    $users[$i]['oa_id'] =  $data['data'][$j]['value'];
                    $users[$i]['oa_deptid'] =  $data['data'][$j]['deptid'];

                    $tasks = getDailyPlan($isToday,$users[$i]['oa_id']);
                    for($k = 0; $k < count($tasks); $k++){
                        $obj = $tasks[$k];
                        $task = [];
                        $task['executor'] = $users[$i]['partner_name'];
                        $task['executor_id'] = $users[$i]['id'];
                        $task['date'] = date('Ymd', strtotime($obj['createdAt']));
                        $task['progress'] = $obj['complete'] == null || $obj['complete'] == 'null'? '0%' : $obj['complete'].'%';
                        $task['time_spent'] = $obj['r_time'] == null? '-1' : $obj['r_time'];
                        $task['day_goal'] = $obj['d_describe'];
                        $task['task_content'] = $obj['p_describe'];
                        $task['mondayDate'] = getMondayDate($obj['createdAt']);
                        $task['oa_task_id'] = $obj['id'];

                        $oa_dailytask[] = $task;
                      }

                }   
            }    
        }
        try {
            $conn->beginTransaction();

            if($isToday){
                $checkStmtToday = $conn->prepare("SELECT id FROM daily_tasks_today WHERE oa_taskid = ? ");
                foreach ($oa_dailytask as $item) {
                    $checkStmtToday->execute([$item['oa_task_id']]);
                    if ($checkStmtToday->fetch()) {
                        continue;
                    }

                    $daily_goals_id = 0;
                    $stmt = $conn->prepare("INSERT INTO daily_tasks_today 
                        (date, day_goal, executor_id, task_content, progress, time_spent, is_new_goal,daily_goals_id, createdate,mondayDate)
                        VALUES (?, ?, ?, ?, ?, ?, ?,?, ?,?)");
                    
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
            }else{
                $checkStmt = $conn->prepare("SELECT id FROM daily_tasks WHERE oa_taskid = ?");
                foreach ($oa_dailytask as $item) {
                    $checkStmt->execute([$item['oa_task_id']]);
                    if ($checkStmt->fetch()) {
                        continue;
                    }

                    $daily_goals_id = 0;
                    $stmt = $conn->prepare("INSERT INTO daily_tasks 
                        (date, day_goal, executor_id, task_content, progress, time_spent, is_new_goal,daily_goals_id, createdate,mondayDate)
                        VALUES (?, ?, ?, ?, ?, ?, ?,?, ?,?)");
                    
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
            }

            $conn->commit();
         
        } catch (Exception $e) {
            $conn->rollBack();
            http_response_code(400);
            echo json_encode(['error' => $e->getMessage()]);
        }




        return $oa_dailytask;
    } catch (Exception $e) {
        error_log('同步失败: '.$e->getMessage());
        return ['error' => $e->getMessage()];
    }
}

function getDailyPlan($isToday, $uid) {
    $token = loginOA();
    
    $todayStart = date('Y-m-d 00:00:00');
    $todayEnd = date('Y-m-d 23:59:59');
    
    if (!$isToday) {
        $todayStart = date('Y-m-d 00:00:00', strtotime('-1 day'));
        $todayEnd = date('Y-m-d 23:59:59', strtotime('-1 day'));
    }

    return getSingleUserDailyPlan($uid, $token, $todayStart, $todayEnd);
}

function getMondayDate($date) {
    return date('Ymd', strtotime('this week monday'));
}

function getSingleUserDailyPlan($uid, $token, $startTime, $endTime) {
    $dailyData = json_encode([
        'startDate' => $startTime,
        'endDate' => $endTime,
        'userid' => $uid
    ]);

    $ch = curl_init(DAILY_URL);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $dailyData);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json;charset=UTF-8',
        'Authorization: ' . $token
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    curl_close($ch);

    if ($response === false) {
        error_log("CURL请求失败: " . curl_error($ch));
        throw new Exception("API请求失败");
    }

    $data = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("JSON解析失败: " . json_last_error_msg());
        throw new Exception("响应数据格式错误");
    }
    usort($data['data'], function($a, $b) {
        return $a['sort'] - $b['sort'];
    });

    return $data['data'];
}

$action = $_REQUEST['action'] ?? '';

if ($action === 'sync_users') {
    echo json_encode(handleSync(false));
    exit;
}else if ($action === 'sync_users_today') {
    echo json_encode(handleSync(true));
    exit;
}




