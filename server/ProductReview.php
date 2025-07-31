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
             // 获取当前日期作为文件名
            $currentDate = date('Y-m-d');
            
            // 创建product目录（如果不存在）
            $productDir = __DIR__ . '/product';
            if (!is_dir($productDir)) {
                if (!mkdir($productDir, 0755, true)) {
                    throw new Exception('无法创建product目录');
                }
            }
            
            // 获取POST数据
            $postData = $_POST;
            
            // 如果是JSON数据，尝试解析
            $rawInput = file_get_contents('php://input');
            if (!empty($rawInput)) {
                $jsonData = json_decode($rawInput, true);
                if ($jsonData !== null) {
                    $postData = array_merge($postData, $jsonData);
                }
            }
            
            // 构建文件路径
            $fileName = $currentDate . '.json';
            $filePath = $productDir . '/' . $fileName;
            
            // 准备保存的数据
            $saveData = [
                'timestamp' => date('Y-m-d H:i:s'),
                'date' => $currentDate,
                'data' => $postData,
                'request_method' => $_SERVER['REQUEST_METHOD'],
                'content_type' => $_SERVER['CONTENT_TYPE'] ?? '',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
            ];
            
            // 直接覆盖文件，不追加数据
            $fileData = [
                'records' => [$saveData]
            ];
            
            // 保存到文件
            $jsonContent = json_encode($fileData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            if (file_put_contents($filePath, $jsonContent) === false) {
                throw new Exception('文件保存失败');
            }
            
            parseProduct();
            
            echo json_encode([
                'success' => true, 
                'message' => '数据已保存', 
                'file_path' => $fileName,
                'record_count' => 1
            ]);
            break;
            
        case 'query':
            $result = parseProduct();
            echo json_encode(['success' => true, 'data' => $result, 'count' => 1]);
            break;
            
        case 'parse':
            $result = parseProduct();
            echo json_encode($result);
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

/**
 * 将Excel日期序列号转换为标准日期格式
 * @param mixed $excelDate Excel日期序列号或日期字符串
 * @return string 格式化的日期字符串 (Y-m-d)
 */
function convertExcelDate($excelDate) {
    // 如果已经是日期格式，直接返回
    if (is_string($excelDate) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $excelDate)) {
        return $excelDate;
    }
    
    // 如果是数字，转换Excel序列号
    if (is_numeric($excelDate)) {
        // Excel的日期序列号从1900年1月1日开始计算
        // 但Excel错误地认为1900年是闰年，所以需要减去2天
        $unixTimestamp = ($excelDate - 25569) * 86400; // 25569是1970年1月1日在Excel中的序列号
        return date('Y-m-d', $unixTimestamp);
    }
    
    // 其他情况返回空字符串
    return '';
}

/**
 * 解析产品数据并生成报告
 */
function parseProduct() {
    global $conn;
    
    try {
        // 1. 读取当前日期的JSON文件
        $currentDate = date('Y-m-d');
        $productDir = __DIR__ . '/product';
        $filePath = $productDir . '/' . $currentDate . '.json';
        
        if (!file_exists($filePath)) {
            return ['error' => '当前日期的数据文件不存在: ' . $currentDate . '.json'];
        }
        
        $jsonContent = file_get_contents($filePath);
        $data = json_decode($jsonContent, true);
        
        if (!$data || !isset($data['records'])) {
            return ['error' => '数据文件格式错误或为空'];
        }
        
        $result = [];

        // 处理所有记录中的数据
        $allProductData = [];
        foreach ($data['records'] as $record) {
            if (isset($record['data']) && is_array($record['data'])) {
                $allProductData = $record['data'];
            }
        }
                // 2. 数据分类处理
        // ① 筛选出上线待复盘的内容项目
        // var_dump($allProductData);
        $waitingReview = [];
        foreach ($allProductData as $item) {
            // 假设数据是数组格式，按列索引访问
            if (is_array($item) && count($item) >= 21) {
                $progress = $item[7] ; // 第8列（索引7）- 进度
                $progress = is_array($progress) ? '' : (string)$progress;
                if (strpos($progress, '上线待复盘') !== false) {
                    $requirementName = $item[1] ?? ''; // 第2列 - 需求名称
                    $requirementName = is_array($requirementName) ? '' : (string)$requirementName;
                    $project = $item[3] ?? ''; // 第4列 - 项目
                    $project = is_array($project) ? '' : (string)$project;
                    $onlineTime = $item[16] ?? ''; // 第17列 - 上线时间
                    $onlineTime = is_array($onlineTime) ? '' : (string)$onlineTime;
                    $onlineTime = convertExcelDate($onlineTime); // 转换Excel日期格式
                    
                    $waitingReview[] = [
                        'project' => $project,
                        'requirement_name' => $requirementName,
                        'online_time' => '上线时间:'.$onlineTime
                    ];
                }
            }
        }
        

        // ② 已上线时间计算，近一个月内状态为'完成复盘'的数据
        $oneMonthAgo = date('Y-m-d', strtotime('-1 month'));
        $reviewCompleted = [];
        
        foreach ($allProductData as $item) {
            if (is_array($item) && count($item) >= 21) {
                $progress = $item[7] ?? ''; // 第8列 - 进度
                $progress = is_array($progress) ? '' : (string)$progress;
                $onlineTime = $item[16] ?? ''; // 第17列 - 上线时间
                $onlineTime = is_array($onlineTime) ? '' : (string)$onlineTime;
                $onlineTime = convertExcelDate($onlineTime); // 转换Excel日期格式
                
                if (strpos($progress, '完成复盘') !== false && $onlineTime >= $oneMonthAgo) {
                    $requirementName = $item[1] ?? ''; // 第2列 - 需求名称
                    $requirementName = is_array($requirementName) ? '' : (string)$requirementName;
                    $project = $item[3] ?? ''; // 第4列 - 项目
                    $project = is_array($project) ? '' : (string)$project;
                    $onlineEffect = $item[18] ?? ''; // 第19列 - 上线效果
                    $onlineEffect = is_array($onlineEffect) ? '' : (string)$onlineEffect;
                    $userReview = $item[19] ?? ''; // 第20列 - 用人组复核
                    $userReview = is_array($userReview) ? '' : (string)$userReview;
                    
                    // 判断用人组复核状态
                    $reviewStatus = '其它';
                    if (strpos($userReview, '有效') !== false) {
                        $reviewStatus = '有效';
                    } elseif (strpos($userReview, '无效') !== false) {
                        $reviewStatus = '无效';
                    } elseif (empty($userReview) || strpos($userReview, '未知') !== false) {
                        $reviewStatus = '其它';
                    }
                    
                    $reviewCompleted[] = [
                        'review_status' => $reviewStatus,
                        'project' => $project,
                        'requirement_name' => $requirementName,
                        'online_effect' => $onlineEffect
                    ];
                }
            }
        }
        
        // 按有效-无效-其它排序
         usort($reviewCompleted, function($a, $b) {
             $order = ['有效' => 1, '无效' => 2, '其它' => 3];
             $aOrder = $order[$a['review_status']] ?? 3;
             $bOrder = $order[$b['review_status']] ?? 3;
             return $aOrder - $bOrder;
         });
        
        // ③ 读取数据库昨日上线内容
        $yesterday = date('Ymd', strtotime('-1 day'));
        // 检查数据库连接是否有效
        if (!$conn || !($conn instanceof PDO)) {
            throw new Exception('数据库连接无效');
        }
        $stmt = $conn->prepare("SELECT priority, weekly_goal FROM weekly_goals WHERE department_id = 2 AND real_finish_date = ?");
        $stmt->execute([$yesterday]);
        $weeklyGoals = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $yesterdayOnline = [];
        if ($weeklyGoals && is_array($weeklyGoals)) {
            foreach ($weeklyGoals as $goal) {
                if (is_array($goal)) {
                    $priority = $goal['priority'] ?? 1;
                    $priorityMap = [10 => 'S', 9 => 'S', 8 => 'A', 7 => 'B', 6 => 'C', 5 => 'C', 4 => 'C', 3 => 'C', 2 => 'C', 1 => 'C'];
                    $priorityLabel = $priorityMap[$priority] ?? 'C';
                    $weeklyGoal = $goal['weekly_goal'] ?? '';
                    
                    $yesterdayOnline[] = [
                        'priority' => $priorityLabel,
                        'weekly_goal' => $weeklyGoal
                    ];
                }
            }
        }
        
        // 3. 格式化输出
        $result['上线待复盘'] = [];
        foreach ($waitingReview as $index => $item) {
            $result['上线待复盘'][] = ($index + 1) . '、【' . $item['project'] . '】' . $item['requirement_name'] . ' (' . $item['online_time'] . ')';
        }
        
        $result['一个月内新功能有效性复盘'] = [];
        foreach ($reviewCompleted as $index => $item) {
            if ($item['review_status'] === '其它') {
                $result['一个月内新功能有效性复盘'][] = ($index + 1) . '、【' . $item['review_status'] . '】【' . $item['project'] . '】' . $item['requirement_name'] . ' 👉  (待复盘完成)';
            } else {
                $result['一个月内新功能有效性复盘'][] = ($index + 1) . '、【' . $item['review_status'] . '】【' . $item['project'] . '】' . $item['requirement_name'] . ' 👉  (' . $item['online_effect'] . ')';
            }
        }
        
        $result['昨日上线内容'] = [];
        foreach ($yesterdayOnline as $index => $item) {
            $result['昨日上线内容'][] = ($index + 1) . '、【' . $item['priority'] . '】- ' . $item['weekly_goal'];
        }
        
        $result['提醒'] = '产品需求记录表每日填写 @张梁@叶积建@徐胜';
        
       $response = sendDingTalkMarkdown($result);
        
        return ['success' => true, 'data' => $response];
        
    } catch (Exception $e) {
        return ['error' => '解析失败: ' . $e->getMessage()];
    }
}
function sendDingTalkMarkdown($data) {
    $webhook = 'https://oapi.dingtalk.com/robot/send?access_token=0593d0dcf7172f6d6239c5c21ebc3cd6ea6bd80083ba162afeebb15960a20a97';
    // $webhook = 'https://oapi.dingtalk.com/robot/send?access_token=5d88fd617ede030a0d55e705d522a6b2242c07cdf16bd634e188f3db7a01cf29';
    // 增强版换行处理（合并连续换行+统一缩进）
    $processContent = function($items) {
        return array_map(function($item) {
            // 合并连续换行并添加引用符号
            return '' . str_replace(["\r\r\n", "\r\n", "\r","\n"], "  \n> ", 
                   preg_replace('/(\r\n|\n|\r){2,}/', "\n", $item));
        }, $items);
    };

    // 创建Markdown消息体
    $markdown = [
        'msgtype' => 'markdown',
        'markdown' => [
            'title' => '产品需求记录表每日同步',
            'text' => "### ⏰ **产品需求记录表每日同步**  \n" // 标题加粗
        ]
    ];

    // 1. 上线待复盘（添加双换行间隔+标题加粗）
    if (!empty($data['上线待复盘'])) {
        $markdown['markdown']['text'] .= "  \n  \n"; // 双换行间隔
        $markdown['markdown']['text'] .= "#### **🔴 上线待复盘**  \n"; // 标题加粗
        foreach ($processContent($data['上线待复盘']) as $item) {
            $markdown['markdown']['text'] .= "- {$item}  \n"; // 统一列表符号
        }
    }
    $markdown['markdown']['text'] .= "↠↠↠↠↠↠↠↠↠↠↠↠↠↠↠↠↠↠↠↠↠↠↠↠↠↠↠↠↠↠↠↠↠↠↠↠↠↠\n";
    // 2. 上线后用人组复核结果（同上优化）
    if (!empty($data['一个月内新功能有效性复盘'])) {
        $markdown['markdown']['text'] .= "  \n  \n";
        $markdown['markdown']['text'] .= "#### **🟢 一个月内新功能有效性复盘**  \n";
        foreach ($processContent($data['一个月内新功能有效性复盘']) as $item) {
            $icon = match(true) {
                str_contains($item, '【有效】') => '✅',
                str_contains($item, '【无效】') => '❌',
                default => 'ℹ️'
            };
            $markdown['markdown']['text'] .= "{$icon} {$item}  \n"; // 图标与内容合并
        }
    }
    $markdown['markdown']['text'] .= "↠↠↠↠↠↠↠↠↠↠↠↠↠↠↠↠↠↠↠↠↠↠↠↠↠↠↠↠↠↠↠↠↠↠↠↠↠↠\n";
    // 3. 昨日上线内容（固定显示）
    $markdown['markdown']['text'] .= "  \n  \n";
    $markdown['markdown']['text'] .= "#### **🟡 昨日上线内容**  \n";
    if (!empty($data['昨日上线内容'])) {
        foreach ($data['昨日上线内容'] as $item) {
            $markdown['markdown']['text'] .= "🚀 {$item}  \n"; // 简化符号
        }
    } else {
        $markdown['markdown']['text'] .= "📭 无新上线内容  \n";
    }

    // 4. 提醒事项
    $markdown['markdown']['text'] .= "↠↠↠↠↠↠↠↠↠↠↠↠↠↠↠↠↠↠↠↠↠↠↠↠↠↠↠↠↠↠↠↠↠↠↠↠↠↠\n";
    $markdown['markdown']['text'] .= "#### **🔔 提醒事项**  \n";
    $markdown['markdown']['text'] .= "⚠️ **{$data['提醒']}**";

    // 发送请求
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $webhook);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($markdown));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);

    return $response;
}




?>