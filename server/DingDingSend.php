<?php
// 解决跨域问题（允许所有域名访问，实际部署可改为指定域名）
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, X-Requested-With");
header("Content-Type: application/json; charset=utf-8");

// 处理预检请求（OPTIONS）
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

$filename = "20250813.txt";
// 记录初始状态（可选）
file_put_contents($filename, "=== 脚本启动 ===" . PHP_EOL, FILE_APPEND);

// --- 新增调试日志功能 ---
// 获取原始请求数据和请求头
$rawInput = file_get_contents('php://input');
$requestHeaders = getallheaders();

// 构建日志内容
$logEntry = "[DEBUG] " . date('Y-m-d H:i:s') . " 收到请求" . PHP_EOL;
$logEntry .= "请求方法: " . $_SERVER['REQUEST_METHOD'] . PHP_EOL;
$logEntry .= "请求头: " . json_encode($requestHeaders, JSON_PRETTY_PRINT) . PHP_EOL;
$logEntry .= "原始输入: " . $rawInput . PHP_EOL;
$logEntry .= "POST数组: " . print_r($_POST, true) . PHP_EOL;
$logEntry .= "--- END DEBUG ---" . PHP_EOL . PHP_EOL;

// 写入日志文件（追加模式+文件锁定）
file_put_contents($filename, $logEntry, FILE_APPEND | LOCK_EX);
// 接收POST数据
$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;


// 构建Markdown消息内容
$markdownText = "### ⚠️ 玩家问题上报 \n\n";
$markdownText .= "**📍 基础信息**\n";
$markdownText .= "- 🔖 优先级：**{$input['priority']}**\n";
$markdownText .= "- 🌎 国家：**{$input['country']}**\n";
$markdownText .= "- 🧾 问题类型：`{$input['type']}`\n";
$markdownText .= "- ⏰ 发生时间：{$input['time']}\n\n";

$markdownText .= "**👤 玩家信息**\n";
$markdownText .= "- 🆔 ID：`{$input['uid']}`\n";
$markdownText .= "- 💎 VIP点数：{$input['vippoints']}\n";
$markdownText .= "- ✈️ 飞机昵称：@{$input['telegram_nick']}\n\n";

$markdownText .= "**📝 问题详情**\n";
$markdownText .= "- {$input['describe']}\n\n";
$markdownText .= "**🔍 自查结果**\n";
$markdownText .= "```\n{$input['self_test']}\n```\n\n";

$markdownText .= "**📊 后台查询**\n";
$markdownText .= "`{$input['query_result']}`\n\n";

// 添加媒体链接（非必填）
if (!empty($input['vedio_url'])) {
    $markdownText .= "▶️ [查看视频]({$input['vedio_url']})\n";
}
if (!empty($input['picture'])) {
    $markdownText .= "🖼️ [查看截图]({$input['picture']})\n";
}

$markdownText .= "---\n";
$markdownText .= "👨‍💻 值班客服：**{$input['customer']}**\n";
$markdownText .= "🔗 [工单详情]({$input['url']})";

// 钉钉消息数据结构
$data = [
    'msgtype' => 'markdown',
    'markdown' => [
        'title' => "{$input['type']}问题报告",
        'text' => $markdownText
    ],
    'at' => [
        'isAtAll' => false  // 不@所有人
    ]
];

// 发送到钉钉机器人
$webhookUrl = 'https://oapi.dingtalk.com/robot/send?access_token=0593d0dcf7172f6d6239c5c21ebc3cd6ea6bd80083ba162afeebb15960a20a97';
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $webhookUrl);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
curl_close($ch);

// 返回操作结果
echo json_encode([
    'status' => !empty($response) ? 'success' : 'error',
    'dingtalk_response' => json_decode($response, true)
]);
?>