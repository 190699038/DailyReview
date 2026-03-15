<?php
require __DIR__ . '/db_connect.php';

$action = $_REQUEST['action'] ?? '';

try {
    switch ($action) {

        // 获取汇报列表（按日期）
        case 'list':
            $report_date = $_GET['report_date'] ?? date('Ymd');
            $reporter = $_GET['reporter'] ?? '';

            $sql = "SELECT * FROM follow_up_reports WHERE report_date = ?";
            $params = [$report_date];

            if (!empty($reporter)) {
                $sql .= " AND reporter = ?";
                $params[] = $reporter;
            }
            $sql .= " ORDER BY created_at DESC";

            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
            echo json_encode(['data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
            break;

        // 获取汇报详情（含明细）
        case 'get':
            $id = $_GET['id'] ?? '';
            $report_date = $_GET['report_date'] ?? '';
            $reporter = $_GET['reporter'] ?? '';

            if (!empty($id)) {
                $stmt = $conn->prepare("SELECT * FROM follow_up_reports WHERE id = ?");
                $stmt->execute([$id]);
            } elseif (!empty($report_date) && !empty($reporter)) {
                $stmt = $conn->prepare("SELECT * FROM follow_up_reports WHERE report_date = ? AND reporter = ?");
                $stmt->execute([$report_date, $reporter]);
            } else {
                http_response_code(400);
                echo json_encode(['error' => '缺少必要参数（id 或 report_date+reporter）']);
                break;
            }

            $report = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$report) {
                echo json_encode(['data' => null]);
                break;
            }

            // 查询明细
            $itemStmt = $conn->prepare("SELECT * FROM follow_up_report_items WHERE report_id = ? ORDER BY category, section_order");
            $itemStmt->execute([$report['id']]);
            $report['items'] = $itemStmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode(['data' => $report]);
            break;

        // 保存汇报（新增或更新）
        case 'save':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode(['error' => '仅支持POST方法']);
                break;
            }

            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input) {
                http_response_code(400);
                echo json_encode(['error' => '无效的JSON数据']);
                break;
            }

            $reporter = $input['reporter'] ?? '';
            $report_date = $input['report_date'] ?? '';
            $week_day = $input['week_day'] ?? '';
            $coordination_matters = $input['coordination_matters'] ?? '';
            $status = $input['status'] ?? 0;
            $items = $input['items'] ?? [];

            if (empty($reporter) || empty($report_date)) {
                http_response_code(400);
                echo json_encode(['error' => '汇报人和日期不能为空']);
                break;
            }

            $conn->beginTransaction();

            // Upsert 主表
            $stmt = $conn->prepare("SELECT id FROM follow_up_reports WHERE reporter = ? AND report_date = ?");
            $stmt->execute([$reporter, $report_date]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($existing) {
                $reportId = $existing['id'];
                $stmt = $conn->prepare("UPDATE follow_up_reports SET week_day = ?, coordination_matters = ?, status = ? WHERE id = ?");
                $stmt->execute([$week_day, $coordination_matters, $status, $reportId]);
            } else {
                $stmt = $conn->prepare("INSERT INTO follow_up_reports (reporter, report_date, week_day, coordination_matters, status) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$reporter, $report_date, $week_day, $coordination_matters, $status]);
                $reportId = $conn->lastInsertId();
            }

            // 删除旧明细，重新插入
            $stmt = $conn->prepare("DELETE FROM follow_up_report_items WHERE report_id = ?");
            $stmt->execute([$reportId]);

            if (!empty($items)) {
                $stmt = $conn->prepare("INSERT INTO follow_up_report_items (report_id, category, section_title, responsible_person, section_order, content, risk_level) VALUES (?, ?, ?, ?, ?, ?, ?)");
                foreach ($items as $item) {
                    $stmt->execute([
                        $reportId,
                        $item['category'] ?? '',
                        $item['section_title'] ?? '',
                        $item['responsible_person'] ?? '',
                        $item['section_order'] ?? 0,
                        is_array($item['content']) ? json_encode($item['content'], JSON_UNESCAPED_UNICODE) : ($item['content'] ?? '{}'),
                        $item['risk_level'] ?? 0
                    ]);
                }
            }

            $conn->commit();
            echo json_encode(['success' => true, 'id' => $reportId, 'message' => '保存成功']);
            break;

        // 提交汇报
        case 'submit':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode(['error' => '仅支持POST方法']);
                break;
            }

            $input = json_decode(file_get_contents('php://input'), true);
            $id = $input['id'] ?? '';

            if (empty($id)) {
                http_response_code(400);
                echo json_encode(['error' => '缺少汇报ID']);
                break;
            }

            $stmt = $conn->prepare("UPDATE follow_up_reports SET status = 1 WHERE id = ?");
            $stmt->execute([$id]);
            echo json_encode(['success' => true, 'message' => '提交成功']);
            break;

        // 删除汇报
        case 'delete':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode(['error' => '仅支持POST方法']);
                break;
            }

            $input = json_decode(file_get_contents('php://input'), true);
            $id = $input['id'] ?? '';

            if (empty($id)) {
                http_response_code(400);
                echo json_encode(['error' => '缺少汇报ID']);
                break;
            }

            $conn->beginTransaction();
            $stmt = $conn->prepare("DELETE FROM follow_up_report_items WHERE report_id = ?");
            $stmt->execute([$id]);
            $stmt = $conn->prepare("DELETE FROM follow_up_reports WHERE id = ?");
            $stmt->execute([$id]);
            $conn->commit();

            echo json_encode(['success' => true, 'message' => '删除成功']);
            break;

        // 复制历史汇报
        case 'copy':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode(['error' => '仅支持POST方法']);
                break;
            }

            $input = json_decode(file_get_contents('php://input'), true);
            $source_date = $input['source_date'] ?? '';
            $target_date = $input['target_date'] ?? '';
            $reporter = $input['reporter'] ?? '';

            if (empty($source_date) || empty($target_date) || empty($reporter)) {
                http_response_code(400);
                echo json_encode(['error' => '缺少必要参数']);
                break;
            }

            // 查找源记录
            $stmt = $conn->prepare("SELECT * FROM follow_up_reports WHERE reporter = ? AND report_date = ?");
            $stmt->execute([$reporter, $source_date]);
            $source = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$source) {
                http_response_code(400);
                echo json_encode(['error' => '源日期没有汇报记录']);
                break;
            }

            // 查找源明细
            $itemStmt = $conn->prepare("SELECT * FROM follow_up_report_items WHERE report_id = ? ORDER BY category, section_order");
            $itemStmt->execute([$source['id']]);
            $sourceItems = $itemStmt->fetchAll(PDO::FETCH_ASSOC);

            $conn->beginTransaction();

            // 检查目标日期是否已存在
            $stmt = $conn->prepare("SELECT id FROM follow_up_reports WHERE reporter = ? AND report_date = ?");
            $stmt->execute([$reporter, $target_date]);
            $existTarget = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($existTarget) {
                // 更新已有记录
                $targetId = $existTarget['id'];
                $stmt = $conn->prepare("UPDATE follow_up_reports SET week_day = ?, coordination_matters = ?, status = 0 WHERE id = ?");
                $stmt->execute([$source['week_day'], $source['coordination_matters'], $targetId]);

                $stmt = $conn->prepare("DELETE FROM follow_up_report_items WHERE report_id = ?");
                $stmt->execute([$targetId]);
            } else {
                $stmt = $conn->prepare("INSERT INTO follow_up_reports (reporter, report_date, week_day, coordination_matters, status) VALUES (?, ?, ?, ?, 0)");
                $stmt->execute([$reporter, $target_date, $source['week_day'], $source['coordination_matters']]);
                $targetId = $conn->lastInsertId();
            }

            // 复制明细
            if (!empty($sourceItems)) {
                $stmt = $conn->prepare("INSERT INTO follow_up_report_items (report_id, category, section_title, responsible_person, section_order, content, risk_level) VALUES (?, ?, ?, ?, ?, ?, ?)");
                foreach ($sourceItems as $item) {
                    $stmt->execute([
                        $targetId,
                        $item['category'],
                        $item['section_title'],
                        $item['responsible_person'],
                        $item['section_order'],
                        $item['content'],
                        $item['risk_level']
                    ]);
                }
            }

            $conn->commit();
            echo json_encode(['success' => true, 'id' => $targetId, 'message' => '复制成功']);
            break;

        // 导出 Markdown
        case 'export_md':
            $id = $_GET['id'] ?? '';
            $report_date = $_GET['report_date'] ?? '';
            $reporter = $_GET['reporter'] ?? '';

            if (!empty($id)) {
                $stmt = $conn->prepare("SELECT * FROM follow_up_reports WHERE id = ?");
                $stmt->execute([$id]);
            } elseif (!empty($report_date) && !empty($reporter)) {
                $stmt = $conn->prepare("SELECT * FROM follow_up_reports WHERE report_date = ? AND reporter = ?");
                $stmt->execute([$report_date, $reporter]);
            } else {
                http_response_code(400);
                echo json_encode(['error' => '缺少必要参数']);
                break;
            }

            $report = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$report) {
                http_response_code(400);
                echo json_encode(['error' => '未找到汇报记录']);
                break;
            }

            $itemStmt = $conn->prepare("SELECT * FROM follow_up_report_items WHERE report_id = ? ORDER BY category, section_order");
            $itemStmt->execute([$report['id']]);
            $items = $itemStmt->fetchAll(PDO::FETCH_ASSOC);

            // 按分类分组
            $grouped = [];
            foreach ($items as $item) {
                $grouped[$item['category']][] = $item;
            }

            // 格式化日期
            $dateStr = $report['report_date'];
            $formattedDate = substr($dateStr, 0, 4) . '-' . substr($dateStr, 4, 2) . '-' . substr($dateStr, 6, 2);

            $md = "# 每日工作汇报 — {$report['reporter']}\n";
            $md .= "> 日期：{$formattedDate}（{$report['week_day']}）\n\n";

            // 业务事项
            if (!empty($grouped['business'])) {
                $md .= "## 今日业务事项进展\n\n";
                foreach ($grouped['business'] as $idx => $item) {
                    $num = $idx + 1;
                    $title = $item['section_title'];
                    $person = $item['responsible_person'] ? "（{$item['responsible_person']}）" : '';
                    $md .= "### {$num}. {$title}{$person}\n";
                    $content = json_decode($item['content'], true) ?: [];
                    foreach ($content as $key => $val) {
                        $md .= "- {$key}：{$val}\n";
                    }
                    $md .= "\n";
                }
            }

            // AI事项
            if (!empty($grouped['ai'])) {
                $md .= "## 今日AI事项进展\n\n";
                foreach ($grouped['ai'] as $item) {
                    $title = $item['section_title'];
                    $md .= "### {$title}\n";
                    $content = json_decode($item['content'], true) ?: [];
                    foreach ($content as $key => $val) {
                        $md .= "- {$key}：{$val}\n";
                    }
                    $md .= "\n";
                }
            }

            // 跨部门协调
            $md .= "## 三、跨部门协调事项\n";
            $coordination = $report['coordination_matters'] ?: '无';
            $md .= "- {$coordination}\n\n";

            // 风险预警
            if (!empty($grouped['risk'])) {
                $md .= "## 四、风险与异常预警\n";
                foreach ($grouped['risk'] as $item) {
                    $content = json_decode($item['content'], true) ?: [];
                    $desc = $content['description'] ?? '';
                    $icon = $item['risk_level'] == 2 ? '🔴' : '🟡';
                    $md .= "- {$icon} {$desc}\n";
                }
                $md .= "\n";
            } else {
                $md .= "## 四、风险与异常预警\n- 无\n";
            }

            echo json_encode(['data' => $md]);
            break;

        default:
            http_response_code(400);
            echo json_encode(['error' => '无效的操作类型']);
    }
} catch (PDOException $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    http_response_code(500);
    echo json_encode(['error' => '数据库操作失败: ' . $e->getMessage()]);
}
