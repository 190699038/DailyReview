<?php
require_once __DIR__ . '/db_connect.php';

// 接收POST数据
$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

// 输入校验
$required = ['priority', 'country', 'type', 'uid', 'describe', 'customer', 'time'];
foreach ($required as $field) {
    if (!isset($input[$field]) || trim($input[$field]) === '') {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => "缺少必填字段: $field"]);
        exit;
    }
}

// 安全过滤函数
$safe = function($val) { return htmlspecialchars($val ?? '', ENT_QUOTES, 'UTF-8'); };

// 构建Markdown消息内容
$markdownText = "### ⚠️ 玩家问题上报 \n\n";
$markdownText .= "**📍 基础信息**\n";
$markdownText .= "- 🔖 优先级：**{$safe($input['priority'])}**\n";
$markdownText .= "- 🌎 国家：**{$safe($input['country'])}**\n";
$markdownText .= "- 🧾 问题类型：`{$safe($input['type'])}`\n";
$markdownText .= "- ⏰ 发生时间：{$safe($input['time'])}\n\n";

$markdownText .= "**👤 玩家信息**\n";
$markdownText .= "- 🆔 ID：`{$safe($input['uid'])}`\n";
$markdownText .= "- 💎 VIP点数：{$safe($input['vippoints'] ?? '')}\n";
$markdownText .= "- ✈️ 飞机昵称：@{$safe($input['telegram_nick'] ?? '')}\n\n";

$markdownText .= "**📝 问题详情**\n";
$markdownText .= "- {$safe($input['describe'])}\n\n";
$markdownText .= "**🔍 自查结果**\n";
$markdownText .= "```\n{$safe($input['self_test'] ?? '')}\n```\n\n";

$markdownText .= "**📊 后台查询**\n";
$markdownText .= "`{$safe($input['query_result'] ?? '')}`\n\n";

// 添加媒体链接（非必填）
if (!empty($input['vedio_url'])) {
    $markdownText .= "▶️ [查看视频]({$safe($input['vedio_url'])})\n";
}
if (!empty($input['picture'])) {
    $markdownText .= "🖼️ [查看截图]({$safe($input['picture'])})\n";
}

$markdownText .= "---\n";
$markdownText .= "👨‍💻 值班客服：**{$safe($input['customer'])}**\n";
$markdownText .= "🔗 [工单详情]({$safe($input['url'] ?? '')})";

// 钉钉消息数据结构
$data = [
    'msgtype' => 'markdown',
    'markdown' => [
        'title' => "{$safe($input['type'])}问题报告",
        'text' => $markdownText
    ],
    'at' => [
        'isAtAll' => false  // 不@所有人
    ]
];

// 发送到钉钉机器人
$webhookUrl = $_ENV['DINGTALK_WEBHOOK_TEST'] ?? '';
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $webhookUrl);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
curl_close($ch);

// 返回操作结果
$result = json_decode($response, true);
echo json_encode([
    'status' => (isset($result['errcode']) && $result['errcode'] === 0) ? 'success' : 'error',
    'message' => (isset($result['errcode']) && $result['errcode'] === 0) ? '发送成功' : '发送失败'
]);
?>