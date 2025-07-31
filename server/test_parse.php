<?php
// 测试parseProduct()函数
require __DIR__ . '/db_connect.php';
require __DIR__ . '/ProductReview.php';

// 直接调用parseProduct函数进行测试
echo "Testing parseProduct() function...\n";
echo "================================\n";

$result = parseProduct();

if (isset($result['success']) && $result['success']) {
    echo "解析成功！\n\n";
    
    foreach ($result['data'] as $section => $items) {
        echo $section . ":\n";
        if (is_array($items)) {
            foreach ($items as $item) {
                echo "  " . $item . "\n";
            }
        } else {
            echo "  " . $items . "\n";
        }
        echo "\n";
    }
} else {
    echo "解析失败: " . ($result['error'] ?? '未知错误') . "\n";
}

echo "测试完成。\n";
?>