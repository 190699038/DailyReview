<?php
require __DIR__ . '/db_connect.php';

$action = $_REQUEST['action'] ?? '';

try {
    switch ($action) {
        case 'list':
            $conditions = [];
            $params = [];

            if (!empty($_REQUEST['start_date'])) {
                $conditions[] = 'date >= ?';
                $params[] = $_REQUEST['start_date'];
            }
            if (!empty($_REQUEST['end_date'])) {
                $conditions[] = 'date <= ?';
                $params[] = $_REQUEST['end_date'];
            }

            $sql = "SELECT * FROM chen_yaopu_review";
            if (!empty($conditions)) {
                $sql .= ' WHERE ' . implode(' AND ', $conditions);
            }
            $sql .= ' ORDER BY date DESC';

            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
            break;

        case 'create':
            $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

            $sql = "INSERT INTO chen_yaopu_review (date, purpose, initiator, participants, conclusion, content, next_step, valuable, value_content)
                    VALUES (:date, :purpose, :initiator, :participants, :conclusion, :content, :next_step, :valuable, :value_content)";
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                ':date'          => $input['date'] ?? '',
                ':purpose'       => $input['purpose'] ?? '',
                ':initiator'     => $input['initiator'] ?? '',
                ':participants'  => $input['participants'] ?? '',
                ':conclusion'    => $input['conclusion'] ?? '',
                ':content'       => $input['content'] ?? '',
                ':next_step'     => $input['next_step'] ?? '',
                ':valuable'      => intval($input['valuable'] ?? 2),
                ':value_content' => $input['value_content'] ?? '',
            ]);
            echo json_encode(['success' => true, 'id' => $conn->lastInsertId()]);
            break;

        case 'update':
            $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

            if (empty($input['id'])) {
                throw new Exception('缺少必传参数 id');
            }

            $sql = "UPDATE chen_yaopu_review SET
                    date = :date, purpose = :purpose, initiator = :initiator, participants = :participants,
                    conclusion = :conclusion, content = :content, next_step = :next_step,
                    valuable = :valuable, value_content = :value_content
                    WHERE id = :id";
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                ':date'          => $input['date'] ?? '',
                ':purpose'       => $input['purpose'] ?? '',
                ':initiator'     => $input['initiator'] ?? '',
                ':participants'  => $input['participants'] ?? '',
                ':conclusion'    => $input['conclusion'] ?? '',
                ':content'       => $input['content'] ?? '',
                ':next_step'     => $input['next_step'] ?? '',
                ':valuable'      => intval($input['valuable'] ?? 2),
                ':value_content' => $input['value_content'] ?? '',
                ':id'            => intval($input['id']),
            ]);
            echo json_encode(['success' => $stmt->rowCount() > 0]);
            break;

        case 'delete':
            $id = intval($_REQUEST['id'] ?? 0);
            if ($id <= 0) {
                throw new Exception('无效的记录ID');
            }
            $stmt = $conn->prepare("DELETE FROM chen_yaopu_review WHERE id = ?");
            $stmt->execute([$id]);
            echo json_encode(['success' => $stmt->rowCount() > 0]);
            break;

        case 'upload_image':
            if (!isset($_FILES['file'])) {
                throw new Exception('未接收到文件');
            }

            $file = $_FILES['file'];
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            if (!in_array($file['type'], $allowedTypes)) {
                throw new Exception('不支持的文件类型');
            }

            $maxSize = 5 * 1024 * 1024;
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

        default:
            throw new Exception('未知操作: ' . $action);
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
