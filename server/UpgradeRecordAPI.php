<?php
require __DIR__ . '/db_connect.php';

$action = $_REQUEST['action'] ?? '';

try {
    switch ($action) {
        case 'list':
            $conditions = [];
            $params = [];

            // 时间范围筛选
            if (!empty($_REQUEST['start_time'])) {
                $conditions[] = 'update_time >= ?';
                $params[] = $_REQUEST['start_time'];
            }
            if (!empty($_REQUEST['end_time'])) {
                $conditions[] = 'update_time <= ?';
                $params[] = $_REQUEST['end_time'];
            }

            // 国家筛选
            if (!empty($_REQUEST['country']) && $_REQUEST['country'] !== 'ALL') {
                $conditions[] = 'country = ?';
                $params[] = $_REQUEST['country'];
            }

            $sql = "SELECT * FROM upgrade_record";
            if (!empty($conditions)) {
                $sql .= ' WHERE ' . implode(' AND ', $conditions);
            }
            $sql .= ' ORDER BY update_time DESC';

            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
            break;

        case 'create':
            $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

            $sql = "INSERT INTO upgrade_record (country, content, update_time, update_time_out, updater, tester, type, platform, impact, remark) 
                    VALUES (:country, :content, :update_time, :update_time_out, :updater, :tester, :type, :platform, :impact, :remark)";
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                ':country'         => $input['country'] ?? '',
                ':content'         => $input['content'] ?? '',
                ':update_time'     => $input['update_time'] ?? null,
                ':update_time_out' => $input['update_time_out'] ?? null,
                ':updater'         => $input['updater'] ?? '',
                ':tester'          => $input['tester'] ?? '',
                ':type'            => $input['type'] ?? '',
                ':platform'        => $input['platform'] ?? '',
                ':impact'          => $input['impact'] ?? '',
                ':remark'          => $input['remark'] ?? '',
            ]);
            echo json_encode(['success' => true, 'id' => $conn->lastInsertId()]);
            break;

        case 'update':
            $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

            if (empty($input['id'])) {
                throw new Exception('缺少必传参数 id');
            }

            $sql = "UPDATE upgrade_record SET 
                    country = :country, content = :content, update_time = :update_time, update_time_out = :update_time_out,
                    updater = :updater, tester = :tester, type = :type, platform = :platform, 
                    impact = :impact, remark = :remark
                    WHERE id = :id";
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                ':country'         => $input['country'] ?? '',
                ':content'         => $input['content'] ?? '',
                ':update_time'     => $input['update_time'] ?? null,
                ':update_time_out' => $input['update_time_out'] ?? null,
                ':updater'         => $input['updater'] ?? '',
                ':tester'          => $input['tester'] ?? '',
                ':type'            => $input['type'] ?? '',
                ':platform'        => $input['platform'] ?? '',
                ':impact'          => $input['impact'] ?? '',
                ':remark'          => $input['remark'] ?? '',
                ':id'              => intval($input['id']),
            ]);
            echo json_encode(['success' => $stmt->rowCount() > 0]);
            break;

        case 'delete':
            $id = intval($_REQUEST['id'] ?? 0);
            if ($id <= 0) {
                throw new Exception('无效的记录ID');
            }
            $stmt = $conn->prepare("DELETE FROM upgrade_record WHERE id = ?");
            $stmt->execute([$id]);
            echo json_encode(['success' => $stmt->rowCount() > 0]);
            break;

        case 'get_review':
            $id = intval($_REQUEST['id'] ?? 0);
            if ($id <= 0) {
                throw new Exception('无效的记录ID');
            }
            $stmt = $conn->prepare("SELECT id, content, is_review, review_conclusion, review_person FROM upgrade_record WHERE id = ?");
            $stmt->execute([$id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            echo json_encode($row ?: []);
            break;

        case 'save_review':
            $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

            if (empty($input['id'])) {
                throw new Exception('缺少必传参数 id');
            }

            $stmt = $conn->prepare("UPDATE upgrade_record SET is_review = 1, review_conclusion = :conclusion, review_person = :person WHERE id = :id");
            $stmt->execute([
                ':conclusion' => $input['review_conclusion'] ?? '',
                ':person'     => $input['review_person'] ?? '',
                ':id'         => intval($input['id']),
            ]);
            echo json_encode(['success' => $stmt->rowCount() > 0]);
            break;

        case 'upload_image':
            // 复盘编辑器图片上传
            if (!isset($_FILES['file'])) {
                throw new Exception('未接收到文件');
            }

            $file = $_FILES['file'];
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            if (!in_array($file['type'], $allowedTypes)) {
                throw new Exception('不支持的文件类型');
            }

            $maxSize = 5 * 1024 * 1024; // 5MB
            if ($file['size'] > $maxSize) {
                throw new Exception('文件大小不能超过5MB');
            }

            $uploadDir = __DIR__ . '/uploads/review/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $allowedExt = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            if (!in_array(strtolower($ext), $allowedExt)) {
                throw new Exception('不支持的文件扩展名');
            }

            $filename = bin2hex(random_bytes(16)) . '.' . $ext;
            $filepath = $uploadDir . $filename;

            if (!move_uploaded_file($file['tmp_name'], $filepath)) {
                throw new Exception('文件上传失败');
            }

            $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
            $url = $baseUrl . '/server/uploads/review/' . $filename;
            echo json_encode(['success' => true, 'url' => $url]);
            break;

        case 'send_dingding':
            $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

            $content = $input['content'] ?? '';
            $country = $input['country'] ?? '';
            $updater = $input['updater'] ?? '';
            $updateTime = $input['update_time'] ?? '';
            $impact = $input['impact'] ?? '';

            $markdownText = "### 📦 升级记录通知 \n\n";
            $markdownText .= "- 🌎 国家：**{$country}**\n";
            $markdownText .= "- 📝 升级内容：{$content}\n";
            $markdownText .= "- 📍 影响范围：{$impact}\n";
            $markdownText .= "- 👨‍💻 研发：{$updater}\n";
            $markdownText .= "- ⏰ 更新时间：{$updateTime}\n";

            $data = [
                'msgtype' => 'markdown',
                'markdown' => [
                    'title' => "升级记录 - {$country}",
                    'text' => $markdownText
                ],
                'at' => ['isAtAll' => false]
            ];

            $webhookUrl = $_ENV['DINGTALK_WEBHOOK_TEST'] ?? '';
            if (empty($webhookUrl)) {
                throw new Exception('钉钉Webhook未配置');
            }

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $webhookUrl);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            $response = curl_exec($ch);
            curl_close($ch);

            $result = json_decode($response, true);
            echo json_encode([
                'success' => (isset($result['errcode']) && $result['errcode'] === 0),
                'message' => (isset($result['errcode']) && $result['errcode'] === 0) ? '发送成功' : '发送失败'
            ]);
            break;

        default:
            http_response_code(400);
            echo json_encode(['error' => '无效的操作类型']);
            exit;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

$conn = null;
