<?php
// 支持跨域
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// 处理OPTIONS请求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit();
}

// 导入数据库连接文件
require __DIR__ . '/db_connect.php';

// 钉钉webhook地址
$webhook = 'https://oapi.dingtalk.com/robot/send?access_token=0593d0dcf7172f6d6239c5c21ebc3cd6ea6bd80083ba162afeebb15960a20a97'; //测试钉钉群
// $webhook = 'https://oapi.dingtalk.com/robot/send?access_token=521d66766b9b7d738f2d67ca01f265fe1b45ad1ae3287858908d04a37a6d6a0e'; //正式钉钉群

// 获取mondayDate参数
$mondayDate = isset($_REQUEST['mondayDate']) ? $_REQUEST['mondayDate'] : '';
if (empty($mondayDate)) {
    echo json_encode(['code' => 1, 'message' => '缺少mondayDate参数']);
    exit();
}

// 部门与负责人映射关系
$departments = [
    '游戏技术组' => '陈苏熙',
    '奇胜技术组' => '钱贵祥',
    '产品组' => '张梁',
    '奇胜调研' => '朱军丹',
    '奇胜流量' => '王威',
    '投放组' => '梁浩风',
    '技术组' => '董陈刚',
    '大富组' => '杨绍銮',
    '用人组' => '章志雄',
    '选人组' => '孙晓远',
    '财务组' => '杨秀玲',
];

// 部门对应的图标（优化版 - 更具代表性和区分度）
$deptIcons = [
    '游戏技术组' => '🎮',    // 游戏手柄 - 游戏相关
    '奇胜技术组' => '💻',    // 电脑 - 技术开发
    '产品组' => '📱',        // 手机 - 产品设计
    '奇胜调研' => '📊',      // 条形图 - 数据调研
    '奇胜流量' => '📈',      // 上升趋势 - 流量增长
    '投放组' => '🚀',        // 火箭 - 快速投放
    '技术组' => '⚙️',       // 齿轮 - 技术支持
    '大富组' => '💰',        // 钱袋 - 财富管理
    '用人组' => '👥',        // 人群 - 人力资源
    '选人组' => '🎯',        // 靶心 - 精准选择
    '财务组' => '💹'         // 股票图表 - 财务分析
];

// 部门对应的颜色（用于区分不同部门）
$deptColors = [
    '游戏技术组' => '#1E90FF',  // 道奇蓝
    '奇胜技术组' => '#32CD32',  // 酸橙绿
    '产品组' => '#FF6347',      // 番茄红
    '奇胜调研' => '#9370DB',    // 中紫色
    '奇胜流量' => '#FF8C00',    // 深橙色
    '投放组' => '#20B2AA',      // 浅海绿
    '技术组' => '#4169E1',      // 皇家蓝
    '大富组' => '#FFD700',      // 金色
    '用人组' => '#DC143C',      // 深红色
    '选人组' => '#8A2BE2',      // 蓝紫色
    '财务组' => '#00CED1'       // 深绿松石色
];

// priority映射关系（带颜色标识）
$priorityMap = [
    10 => ['name' => 'S', 'color' => '#FF0000'],   // 红色
    9 => ['name' => 'A', 'color' => '#FF7D00'],    // 橙色
    8 => ['name' => 'B', 'color' => '#007FFF'],    // 蓝色
    7 => ['name' => 'C', 'color' => '#00B42A']     // 绿色
];

// country映射关系
$countryMap = [
    "OA" => "OA系统",
    "US1" => "美国1",
    "US2" => "美国2",
    "US3" => "美国3",
    "BR1" => "巴西1",
    "BR2" => "巴西2",
    "MX" => "墨西哥",
    "PE" => "秘鲁",
    "CL" => "智利",
    "AU" => "澳大利亚",
    "CA" => "加拿大",
    "PH" => "菲律宾",
    "ALL" => "所有地区",
    "QSJS" => "奇胜-技术",
    "QSDY" => "奇胜-调研",
    "QSLL" => "奇胜-流量",
    "YXJS" => "游戏技术",
    "XR" => "选人",
    "YR" => "用人",
    "YW" => "运维",
    "FK" => "风控",
    "MVP" => "MVP",
    "CW" => "财务",
    "TF" => "投放",
    "DF" => "支付",
    "QT" => "其它"
];

// 获取部门ID
function getDepartmentId($deptName) {
    $deptIdMap = [
        '游戏技术组' => 2,
        '奇胜技术组' => 3,
        '产品组' => 5,
        '奇胜调研' => 15,
        '奇胜流量' => 16,
        '投放组' => 4,
        '技术组' => 1,
        '大富组' => 13,
        '用人组' => 7,
        '选人组' => 6,
        '财务组' => 8
    ];
    return isset($deptIdMap[$deptName]) ? $deptIdMap[$deptName] : 0;
}

// 查询指定负责人的数据（PDO方式）
function queryExecutorData($mondayDate, $executor) {
    global $conn;
    
    try {
        $sql = "SELECT weekly_goal, executor, priority, pre_finish_date, country 
                FROM weekly_goals 
                WHERE mondayDate = :mondayDate AND executor LIKE :executor order by priority desc";
        
        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            return [];
        }
        
        $executorParam = "%{$executor}%";
        if ($stmt->bindParam(':mondayDate', $mondayDate, PDO::PARAM_STR) === false) {
            return [];
        }
        if ($stmt->bindParam(':executor', $executorParam, PDO::PARAM_STR) === false) {
            return [];
        }
        if ($stmt->execute() === false) {
            return [];
        }
        
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return is_array($result) ? $result : [];
    } catch (Exception $e) {
        return [];
    }
}

// 查询指定部门的数据（PDO方式）
function queryDepartmentData($mondayDate, $deptId) {
    global $conn;
    
    try {
        if($deptId == 16){
            $sql = "SELECT weekly_goal, executor, priority, pre_finish_date, country 
                FROM weekly_goals 
                WHERE mondayDate = :mondayDate AND department_id = :deptId  and executor not LIKE '%王旭%' and  country = 'QSLL'   order by priority desc";
        }else{
            $sql = "SELECT weekly_goal, executor, priority, pre_finish_date, country 
                FROM weekly_goals 
                WHERE mondayDate = :mondayDate AND department_id = :deptId  and executor not LIKE '%王旭%' and  executor not LIKE '%梁超%'   order by priority desc";
        }
        
        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            return [];
        }
        
        if ($stmt->bindParam(':mondayDate', $mondayDate, PDO::PARAM_STR) === false) {
            return [];
        }
        if ($stmt->bindParam(':deptId', $deptId, PDO::PARAM_INT) === false) {
            return [];
        }
        if ($stmt->execute() === false) {
            return [];
        }
        
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return is_array($result) ? $result : [];
    } catch (Exception $e) {
        return [];
    }
}

// 转换数据格式
function transformData($rawData, $priorityMap, $countryMap) {
    $transformed = [];
    foreach ($rawData as $item) {
        // 转换priority
        $priorityInfo = isset($priorityMap[$item['priority']]) ? 
                        $priorityMap[$item['priority']] : ['name' => '未知', 'color' => '#888888'];
        
        // 转换country
        $countryName = isset($countryMap[$item['country']]) ? 
                      $countryMap[$item['country']] : $item['country'];
        
        $transformed[] = [
            'weekly_goal' => $item['weekly_goal'],
            'executor' => $item['executor'],
            'priority' => $item['priority'],
            'priority_name' => $priorityInfo['name'],
            'priority_color' => $priorityInfo['color'],
            'pre_finish_date' => $item['pre_finish_date'],
            'country' => $item['country'],
            'country_name' => $countryName
        ];
    }
    return $transformed;
}

// 美化任务内容，处理包含多个子项的任务
function beautifyTaskContent($content) {
    // 处理以数字+、开头的子项
    // $pattern = '/(\d+)、/';
    // if (preg_match($pattern, $content, $matches)) {
    //     // 如果是第一个字符就是数字+、，则拆分处理
    //     if (strpos($content, $matches[0]) === 0) {
    //         $parts = preg_split($pattern, $content, -1, PREG_SPLIT_DELIM_CAPTURE);
    //         $result = [];
    //         for ($i = 1; $i < count($parts); $i += 2) {
    //             if (!empty($parts[$i+1])) {
    //                 $result[] = "  - {$parts[$i]}、{$parts[$i+1]}";
    //             }
    //         }
    //         return "\n\n" . implode("\n\n", $result);
    //     }
    // }
    
    // // 处理普通换行符，转换为钉钉支持的格式
    // $content = str_replace("\n", "\n\n", $content);
    
    return $content;
}

// 按部门和负责人组织数据
function organizeByDeptAndExecutor($allData, $departments) {
    $organized = [];
    
    // 初始化组织架构
    foreach ($departments as $dept => $executors) {
        if (!is_array($executors)) {
            $executors = [$executors];
        }
        foreach ($executors as $executor) {
            $key = "$dept-$executor";
            $organized[$key] = [
                'department' => $dept,
                'executor' => $executor,
                'tasks' => []
            ];
        }
    }
    
    // 分配任务
    foreach ($allData as $task) {
        foreach ($organized as $key => &$group) {
            if (strpos($task['executor'], $group['executor']) !== false) {
                $group['tasks'][] = $task;
                break;
            }
        }
    }
    
    return $organized;
}

// 生成钉钉消息内容（任务描述加粗版）
function generateDingTalkContent($organizedData, $deptIcons, $deptColors, $mondayDate) {
    $mdContent = "## 周目标清单\n\n";
    $mdContent .= "### 任务清单查看地址: [周目标清单](https://daily.gameyzy.com/#/week-goal)\n\n";
    
    foreach ($organizedData as $group) {
        if (empty($group['tasks'])) {
            continue; // 跳过没有任务的组
        }
        
        // 项目组标题加粗显示，带颜色区分
        $deptIcon = isset($deptIcons[$group['department']]) ? $deptIcons[$group['department']] : '📌';
        $deptColor = isset($deptColors[$group['department']]) ? $deptColors[$group['department']] : '#333333';
        $mdContent .= "#### {$deptIcon}  <font color='{$deptColor}'>**{$group['department']}-{$group['executor']}**</font>\n\n";
        
        $taskNum = 1;
        foreach ($group['tasks'] as $task) {
            // 优先级标签带颜色
            $priorityLabel = "<font color='{$task['priority_color']}'>【{$task['priority_name']}】</font>";
            $countryLabel = $task['country_name'];
            // 任务描述加粗显示
            // $goal = "**" . beautifyTaskContent($task['weekly_goal']) . "**";
            

            
            // 处理换行符，统一为 \n
            $weeklyGoal = str_replace("\r\n", "\n", $task['weekly_goal']);
            // 按换行分割成数组
            $lines = explode("\n", $weeklyGoal);
            
            if (count($lines) === 1) {
                // 单行内容，直接全加粗
                $goal = "**" . trim($lines[0]) . "**";
            } else {
                // 多行内容：第一行加粗，后续行换行并缩进2个空格
                $goal = "**" . trim($lines[0]) . "**";
                for ($i = 1; $i < count($lines); $i++) {
                    $goal .= "\n\n&nbsp;&nbsp;&nbsp;&nbsp;" . trim($lines[$i]);
                }
            }
                        
            
            $executor = "[{$task['executor']}]";
            
            $deadline = '';
            if (!empty($task['pre_finish_date'])) {
                // 预计时间加粗显示
                if($group['department']=='游戏技术组' || $group['department']=='奇胜技术组'){
                     $deadline = " <font color='#888888'>- 预计上线时间: **{$task['pre_finish_date']}**</font>";
                }else{
                     $deadline = " <font color='#888888'>- 预计完成时间: **{$task['pre_finish_date']}**</font>";
                }
               
            }
            
            // 每个任务单独一行输出，增加可读性
            $mdContent .= "{$taskNum}、{$priorityLabel} {$countryLabel} - {$goal} {$executor}{$deadline}\n\n";
            $taskNum++;
        }
        
        // 组之间增加分割线
        $mdContent .= "---\n\n";
                $mdContent .= "---\n\n";

    }
    
    return $mdContent;
}

// 发送到钉钉
function sendToDingTalk($webhook, $content) {
    $data = [
        'msgtype' => 'markdown',
        'markdown' => [
            'title' => '周目标清单',
            'text' => $content
        ],
        'at' => [
            'isAtAll' => false
        ]
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $webhook);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data, JSON_UNESCAPED_UNICODE));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json;charset=utf-8']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $result = curl_exec($ch);
    curl_close($ch);
    
    return $result;
}

// 主流程
try {
    // 获取数据库连接
    global $conn;
    
    if (!$conn instanceof PDO) {
        throw new Exception("数据库连接不是有效的PDO实例");
    }
    
    // 获取所有数据
    $allData = [];
    
    // 处理特殊负责人：梁超和王旭
    $organizedData = [];



    
    // 处理其他部门
    $otherDepartments = [
        '游戏技术组',
        '奇胜技术组',
        '产品组',
        '奇胜调研',
        '奇胜流量',
        '投放组',
        '技术组' ,
        '大富组',
        '用人组' ,
        '选人组',
        '财务组'
    ];
    foreach ($otherDepartments as $dept) {
        $deptId = getDepartmentId($dept);
        if ($deptId > 0) {
            $rawData = queryDepartmentData($mondayDate, $deptId);
            $transformed = transformData($rawData, $priorityMap, $countryMap);
            $allData = array_merge($allData, $transformed);

            $key = $dept . '-' . $departments[$dept];
            $data = [];
            $data['department'] = $dept;
            $data['executor'] = $departments[$dept];
            $data['tasks'] = $transformed;
            $organizedData[$key] = $data;
        }
    }
    
    
    $specialExecutors = ['梁超', '王旭'];
    foreach ($specialExecutors as $executor) {
        $rawData = queryExecutorData($mondayDate, $executor);
        $transformed = transformData($rawData, $priorityMap, $countryMap);
        $allData = array_merge($allData, $transformed);

        if($executor == '梁超'){
            $key = '奇胜流量-梁超';
            $data = [];
            $data['department'] = '奇胜流量';
            $data['executor'] = '梁超';
            $data['tasks'] = $transformed;
            $organizedData[$key] = $data;
        }
        if($executor == '王旭'){
            $key = '财务组-王旭';
            $data = [];
            $data['department'] = '财务组';
            $data['executor'] = '王旭';
            $data['tasks'] = $transformed;
            $organizedData[$key] = $data;
        }
    }    
    
    
    // var_dump($allData);
    // 按部门和负责人组织数据
    // $organizedData = organizeByDeptAndExecutor($allData, $departments);
        // echo(json_encode($organizedData));

    // 生成钉钉消息内容
    $dingTalkContent = generateDingTalkContent($organizedData, $deptIcons, $deptColors, $mondayDate);
    
    // var_dump($dingTalkContent);
    
    //发送到钉钉
    $response = sendToDingTalk($webhook, $dingTalkContent);
    
    // 输出结果
    echo json_encode([
        'code' => 0,
        'message' => '发送成功',
        'data' => [
            'mondayDate' => $mondayDate,
            'response' => json_decode($response, true)
        ]
    ]);
} catch (Exception $e) {
    echo json_encode([
        'code' => 1,
        'message' => '发送失败: ' . $e->getMessage()
    ]);
}
?>
