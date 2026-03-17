<?php
require __DIR__ . '/db_connect.php';

$action = $_REQUEST['action'] ?? '';

try {
    switch ($action) {

        // 获取汇报详情（含跟进事项明细）
        case 'get':
            $report_date = $_GET['report_date'] ?? '';
            $reporter = $_GET['reporter'] ?? '';

            if (empty($report_date) || empty($reporter)) {
                http_response_code(400);
                echo json_encode(['error' => '缺少必要参数（report_date + reporter）']);
                break;
            }

            $stmt = $conn->prepare("SELECT * FROM follow_up_reports WHERE report_date = ? AND reporter = ?");
            $stmt->execute([$report_date, $reporter]);
            $report = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$report) {
                echo json_encode(['data' => null]);
                break;
            }

            // 仅查询跟进事项
            $itemStmt = $conn->prepare("SELECT * FROM follow_up_report_items WHERE report_id = ? AND category = 'followup' ORDER BY section_order");
            $itemStmt->execute([$report['id']]);
            $report['items'] = $itemStmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode(['data' => $report]);
            break;

        // 获取S级任务（按部门分组 + 统计）
        case 'sync_s_goals':
            $monday_date = $_GET['monday_date'] ?? '';
            if (empty($monday_date)) {
                http_response_code(400);
                echo json_encode(['error' => '缺少monday_date参数']);
                break;
            }

            $stmt = $conn->prepare("SELECT id, weekly_goal, executor, country, department_id, status, process, cross_week FROM weekly_goals WHERE mondayDate = ? AND priority = 10 AND department_id IN (2, 5) ORDER BY department_id, id");
            $stmt->execute([$monday_date]);
            $goals = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // 按部门分组（状态：0=未开始,1=进行中,2=测试中,3=已上线,4=已暂停,5=已完成）
            // 统计：3+5=已完成，4=已暂停，其余=未完成
            $grouped = [];
            $stats = [];
            foreach ($goals as $g) {
                $deptId = (string)$g['department_id'];
                if (!isset($grouped[$deptId])) {
                    $grouped[$deptId] = [];
                    $stats[$deptId] = ['total' => 0, 'completed' => 0, 'in_progress' => 0, 'paused' => 0];
                }
                $grouped[$deptId][] = $g;
                $stats[$deptId]['total']++;
                $st = (int)$g['status'];
                if ($st === 3 || $st === 5) {
                    $stats[$deptId]['completed']++;
                } elseif ($st === 4) {
                    $stats[$deptId]['paused']++;
                } else {
                    $stats[$deptId]['in_progress']++;
                }
            }

            // 部门ID → 部门名称映射
            $deptMap = ['2' => '游戏技术组', '5' => '产品组'];

            // country code → name 映射
            $mapStmt = $conn->prepare("SELECT group_code, group_name FROM project_groups WHERE status = 1");
            $mapStmt->execute();
            $codeMap = [];
            foreach ($mapStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $codeMap[$row['group_code']] = $row['group_name'];
            }

            echo json_encode(['grouped' => $grouped, 'stats' => $stats, 'deptMap' => $deptMap, 'codeMap' => $codeMap]);
            break;

        // 保存汇报（仅跟进事项）
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
                $stmt = $conn->prepare("UPDATE follow_up_reports SET week_day = ?, status = ? WHERE id = ?");
                $stmt->execute([$week_day, $status, $reportId]);
            } else {
                $stmt = $conn->prepare("INSERT INTO follow_up_reports (reporter, report_date, week_day, status) VALUES (?, ?, ?, ?)");
                $stmt->execute([$reporter, $report_date, $week_day, $status]);
                $reportId = $conn->lastInsertId();
            }

            // 删除旧跟进事项明细，重新插入
            $stmt = $conn->prepare("DELETE FROM follow_up_report_items WHERE report_id = ? AND category = 'followup'");
            $stmt->execute([$reportId]);

            if (!empty($items)) {
                $stmt = $conn->prepare("INSERT INTO follow_up_report_items (report_id, category, section_title, section_order, content) VALUES (?, 'followup', ?, ?, ?)");
                foreach ($items as $item) {
                    $stmt->execute([
                        $reportId,
                        $item['section_title'] ?? '',
                        $item['section_order'] ?? 0,
                        is_array($item['content']) ? json_encode($item['content'], JSON_UNESCAPED_UNICODE) : ($item['content'] ?? '{}')
                    ]);
                }
            }

            $conn->commit();
            echo json_encode(['success' => true, 'id' => $reportId, 'message' => '保存成功']);
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

        // 复制昨日跟进事项
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

            // 查找源跟进事项
            $itemStmt = $conn->prepare("SELECT * FROM follow_up_report_items WHERE report_id = ? AND category = 'followup' ORDER BY section_order");
            $itemStmt->execute([$source['id']]);
            $sourceItems = $itemStmt->fetchAll(PDO::FETCH_ASSOC);

            $conn->beginTransaction();

            // 检查目标日期是否已存在
            $stmt = $conn->prepare("SELECT id FROM follow_up_reports WHERE reporter = ? AND report_date = ?");
            $stmt->execute([$reporter, $target_date]);
            $existTarget = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($existTarget) {
                $targetId = $existTarget['id'];
                $stmt = $conn->prepare("UPDATE follow_up_reports SET status = 0 WHERE id = ?");
                $stmt->execute([$targetId]);

                $stmt = $conn->prepare("DELETE FROM follow_up_report_items WHERE report_id = ? AND category = 'followup'");
                $stmt->execute([$targetId]);
            } else {
                $stmt = $conn->prepare("INSERT INTO follow_up_reports (reporter, report_date, week_day, status) VALUES (?, ?, ?, 0)");
                $stmt->execute([$reporter, $target_date, $source['week_day']]);
                $targetId = $conn->lastInsertId();
            }

            // 复制跟进事项
            if (!empty($sourceItems)) {
                $stmt = $conn->prepare("INSERT INTO follow_up_report_items (report_id, category, section_title, section_order, content) VALUES (?, 'followup', ?, ?, ?)");
                foreach ($sourceItems as $item) {
                    $stmt->execute([
                        $targetId,
                        $item['section_title'],
                        $item['section_order'],
                        $item['content']
                    ]);
                }
            }

            $conn->commit();
            echo json_encode(['success' => true, 'id' => $targetId, 'message' => '复制成功']);
            break;

        // 导出 Markdown（合并 S级任务 + 跟进事项）
        case 'export_md':
            $report_date = $_GET['report_date'] ?? '';
            $reporter = $_GET['reporter'] ?? '';
            $monday_date = $_GET['monday_date'] ?? '';

            if (empty($report_date) || empty($reporter)) {
                http_response_code(400);
                echo json_encode(['error' => '缺少必要参数']);
                break;
            }

            // 格式化日期
            $formattedDate = substr($report_date, 0, 4) . '-' . substr($report_date, 4, 2) . '-' . substr($report_date, 6, 2);
            $weekNames = ['周日', '周一', '周二', '周三', '周四', '周五', '周六'];
            $weekDay = $weekNames[date('w', strtotime($formattedDate))];

            $md = "# 每日跟进汇报 — {$reporter}\n";
            $md .= "> 日期：{$formattedDate}（{$weekDay}）\n\n";

            // S级任务
            if (!empty($monday_date)) {
                $stmt = $conn->prepare("SELECT id, weekly_goal, executor, country, department_id, status, process, cross_week FROM weekly_goals WHERE mondayDate = ? AND priority = 10 AND department_id IN (2, 5) ORDER BY department_id, id");
                $stmt->execute([$monday_date]);
                $goals = $stmt->fetchAll(PDO::FETCH_ASSOC);

                $mapStmt = $conn->prepare("SELECT group_code, group_name FROM project_groups WHERE status = 1");
                $mapStmt->execute();
                $codeMap = [];
                foreach ($mapStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                    $codeMap[$row['group_code']] = $row['group_name'];
                }

                $deptMap = ['2' => '游戏技术组', '5' => '产品组'];
                $deptOrder = ['2', '5'];
                $statusMap = [0 => '未开始', 1 => '进行中', 2 => '测试中', 3 => '已上线', 4 => '已暂停', 5 => '已完成'];

                // 按部门分组统计
                $grouped = [];
                $stats = [];
                foreach ($goals as $g) {
                    $d = (string)$g['department_id'];
                    $grouped[$d][] = $g;
                    if (!isset($stats[$d])) $stats[$d] = ['completed' => 0, 'in_progress' => 0, 'paused' => 0];
                    $st = (int)$g['status'];
                    if ($st === 3 || $st === 5) $stats[$d]['completed']++;
                    elseif ($st === 4) $stats[$d]['paused']++;
                    else $stats[$d]['in_progress']++;
                }

                $md .= "## 业务部门S级任务\n\n";
                foreach ($deptOrder as $deptId) {
                    if (empty($grouped[$deptId])) continue;
                    $deptName = $deptMap[$deptId] ?? $deptId;
                    $s = $stats[$deptId];
                    $md .= "### {$deptName}\n";
                    $md .= "> 本周已完成 {$s['completed']}个，未完成 {$s['in_progress']}个，已暂停 {$s['paused']}个\n\n";

                    $num = 1;
                    foreach ($grouped[$deptId] as $g) {
                        $regionName = $codeMap[$g['country']] ?? $g['country'];
                        $statusText = $statusMap[(int)$g['status']] ?? '未知状态';
                        $crossWeek = ($g['cross_week'] == 1) ? '跨周' : '当周完成';
                        $processPercent = round($g['process'] * 100);
                        $md .= "{$num}、【S】{$g['weekly_goal']} - {$regionName} - {$processPercent}% - {$deptName} - {$g['executor']} - {$crossWeek} - {$statusText}\n";
                        $num++;
                    }
                    $md .= "\n";
                }
            }

            // 跟进事项
            $stmt = $conn->prepare("SELECT * FROM follow_up_reports WHERE report_date = ? AND reporter = ?");
            $stmt->execute([$report_date, $reporter]);
            $report = $stmt->fetch(PDO::FETCH_ASSOC);

            $md .= "## 跟进事项\n\n";
            if ($report) {
                $itemStmt = $conn->prepare("SELECT * FROM follow_up_report_items WHERE report_id = ? AND category = 'followup' ORDER BY section_order");
                $itemStmt->execute([$report['id']]);
                $items = $itemStmt->fetchAll(PDO::FETCH_ASSOC);

                if (!empty($items)) {
                    $fNum = 1;
                    foreach ($items as $item) {
                        $content = json_decode($item['content'], true) ?: [];
                        $status = !empty($content['status']) ? "【{$content['status']}】" : '';
                        $person = !empty($content['responsible_person']) ? " - {$content['responsible_person']}" : '';
                        $md .= "{$fNum}、{$status} {$item['section_title']}{$person}\n";
                        if (!empty($content['progress'])) $md .= "  - 今日已跟进：是\n";
                        if (!empty($content['next_step'])) $md .= "  - 下一步：{$content['next_step']}\n";
                        if (!empty($content['exception'])) $md .= "  - 异常汇报：{$content['exception']}\n";
                        $md .= "\n";
                        $fNum++;
                    }
                } else {
                    $md .= "- 无\n";
                }
            } else {
                $md .= "- 无\n";
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
