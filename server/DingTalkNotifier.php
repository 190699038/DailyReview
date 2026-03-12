<?php
/**
 * 钉钉消息通知类
 * 用于格式化消息内容并发送到钉钉群
 */
class DingTalkNotifier {
    private $accessTokens = [
        "0593d0dcf7172f6d6239c5c21ebc3cd6ea6bd80083ba162afeebb15960a20a97",
        "2c4d5bdfb77e6752beb9be657bda0f1d7f463e2d5526dae9b427054c31f54921"
    ];

    private $countryTokens = [
        'US' => [
            "d0f74870e7cab00a8184f496f4e535c29ceddb48383bdacd3cbc2bb748da8adc",
            "3f74c9f5284c71dcdfd673264fbadf54884aa84eb7ac07857c0862853006b8b9",
            "18108fad6df4de3968113af4f5f6f317665ab554289867e2eb3632635131a236"
        ],
        'US2' => [
            "c6600a792999edc2b8b5840edcdb5ed4efb271dd597433d2d63fdc7c76879388",
            "3f74c9f5284c71dcdfd673264fbadf54884aa84eb7ac07857c0862853006b8b9",
            "18108fad6df4de3968113af4f5f6f317665ab554289867e2eb3632635131a236"
        ],
        'US3' => [
            "2c4d5bdfb77e6752beb9be657bda0f1d7f463e2d5526dae9b427054c31f54921",
            "3f74c9f5284c71dcdfd673264fbadf54884aa84eb7ac07857c0862853006b8b9",
            "18108fad6df4de3968113af4f5f6f317665ab554289867e2eb3632635131a236"
        ],
        'OZ' => [
            "1810761dca43ce70f4e627f7ca2af1719cc903fe66835f4bf69a8c9964382e6c",
            "3f74c9f5284c71dcdfd673264fbadf54884aa84eb7ac07857c0862853006b8b9",
            "18108fad6df4de3968113af4f5f6f317665ab554289867e2eb3632635131a236"
        ],
        'ZD' => [
            "6bd2060f803ba89332e69a82222ef7a826ce30b907208cd6dc26d3d255129439",
            "3f74c9f5284c71dcdfd673264fbadf54884aa84eb7ac07857c0862853006b8b9",
            "18108fad6df4de3968113af4f5f6f317665ab554289867e2eb3632635131a236"
        ],
        'CA' => [
            "813c10af9c2ccf1d23cd4167cf7b67cbf10ebd604e628ed364c38a243897aece",
            "3f74c9f5284c71dcdfd673264fbadf54884aa84eb7ac07857c0862853006b8b9",
            "18108fad6df4de3968113af4f5f6f317665ab554289867e2eb3632635131a236"
        ],
        'PE' => [
            "438cc8032f3e0dbe20dba6d913af288c5763e16c1ac598917c89a55b8a7ee57a",
            "3f74c9f5284c71dcdfd673264fbadf54884aa84eb7ac07857c0862853006b8b9",
            "18108fad6df4de3968113af4f5f6f317665ab554289867e2eb3632635131a236"
        ],
        'MX' => [
            "97261620a430f15f2d2fd83671fb2fcace63930c5748779c565ddca19e15fb7b",
            "3f74c9f5284c71dcdfd673264fbadf54884aa84eb7ac07857c0862853006b8b9",
            "18108fad6df4de3968113af4f5f6f317665ab554289867e2eb3632635131a236"
        ],
        'CL' => [
            "3347be13c1ff41e823652705b449bdbdbb856cedcd546d622d22bf31eccd401a",
            "3f74c9f5284c71dcdfd673264fbadf54884aa84eb7ac07857c0862853006b8b9",
            "18108fad6df4de3968113af4f5f6f317665ab554289867e2eb3632635131a236"
        ],
        'AU' => [
            "7fbf062aaee8b3192bb718ba9aa03da540e46a485f5de605689c224a161d8f07",
            "3f74c9f5284c71dcdfd673264fbadf54884aa84eb7ac07857c0862853006b8b9",
            "18108fad6df4de3968113af4f5f6f317665ab554289867e2eb3632635131a236"
        ],
        'BR' => [
            "49cd389e751b1facdb4102a26b28ba13e98d66f66074942d725cb1f18a516ce4",
            "3f74c9f5284c71dcdfd673264fbadf54884aa84eb7ac07857c0862853006b8b9",
            "18108fad6df4de3968113af4f5f6f317665ab554289867e2eb3632635131a236"
        ],
        'BR2' => [
            "49cd389e751b1facdb4102a26b28ba13e98d66f66074942d725cb1f18a516ce4",
            "3f74c9f5284c71dcdfd673264fbadf54884aa84eb7ac07857c0862853006b8b9",
            "18108fad6df4de3968113af4f5f6f317665ab554289867e2eb3632635131a236"
        ],
        'ALL' => [
            "d0f74870e7cab00a8184f496f4e535c29ceddb48383bdacd3cbc2bb748da8adc",
            "c6600a792999edc2b8b5840edcdb5ed4efb271dd597433d2d63fdc7c76879388",
            "2c4d5bdfb77e6752beb9be657bda0f1d7f463e2d5526dae9b427054c31f54921",
            "813c10af9c2ccf1d23cd4167cf7b67cbf10ebd604e628ed364c38a243897aece",
            "438cc8032f3e0dbe20dba6d913af288c5763e16c1ac598917c89a55b8a7ee57a",
            "97261620a430f15f2d2fd83671fb2fcace63930c5748779c565ddca19e15fb7b",
            "3347be13c1ff41e823652705b449bdbdbb856cedcd546d622d22bf31eccd401a",
            "7fbf062aaee8b3192bb718ba9aa03da540e46a485f5de605689c224a161d8f07",
            "49cd389e751b1facdb4102a26b28ba13e98d66f66074942d725cb1f18a516ce4",
            "18108fad6df4de3968113af4f5f6f317665ab554289867e2eb3632635131a236"
        ]
    ];

    private $dingtalkApiUrl = "https://oapi.dingtalk.com/robot/send?access_token=";

    private $countryMap = [
        'ALL' => '所有',
        'US' => '美国1',
        'US1' => '美国1',
        'US2' => '美国2',
        'US3' => '美国3',
        'US4' => '美国4',
        'OZ' => '欧洲',
        'ZD' => '中东',
        'BR' => '巴西1',
        'BR1' => '巴西1',
        'BR2' => '巴西2',
        'MX' => '墨西哥',
        'PE' => '秘鲁',
        'CL' => '智利',
        'AU' => '澳大利亚',
        'CA' => '加拿大',
        'PH' => '菲律宾',
        'OA' => 'OA',
        'pay_br_all' => '巴西1、2',
        'pay_in_phi' => '印度、菲律宾',
        'pay_other_all' => '美国1、美国2、美国3、墨西哥、智利、加拿大、秘鲁'
    ];

    public function __construct() {
    }

    /**
     * 发送钉钉文本消息
     * @param array $productInfo 产品信息数组
     * @return bool|array 成功返回结果数组，失败返回错误信息数组
     */
    public function sendDingTalkText($productInfo) {
        try {
            if (!is_array($productInfo) || empty($productInfo)) {
                throw new Exception("产品信息不能为空");
            }

            $requiredFields = ['update_time', 'update_time_out', 'country', 'content', 'impact', 'updater', 'tester'];
            foreach ($requiredFields as $field) {
                if (!isset($productInfo[$field]) || empty($productInfo[$field])) {
                    throw new Exception("缺少必要字段：{$field}");
                }
            }

            $messageContent = $this->buildMessageContent($productInfo);

            $results = [];
            $tokens = $this->getTokens($productInfo['country']);
            foreach ($tokens as $token) {
                $result = $this->sendToDingTalk($token, $messageContent);
                $results[] = $result;
            }

            return $results;
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    private function getTokens($country) {
        // US1 映射到 US
        if ($country === 'US1') {
            $country = 'US';
        }
        // BR1 映射到 BR
        if ($country === 'BR1') {
            $country = 'BR';
        }

        if (isset($this->countryTokens[$country])) {
            return $this->countryTokens[$country];
        }

        // 未配置的国家使用默认tokens
        return $this->accessTokens;
    }

    private function buildMessageContent(array $productInfo) {
        $updateTimeLocal = $this->formatDateTime($productInfo['update_time']);
        $updateTimeOut = $this->formatDateTime($productInfo['update_time_out']);
        $country = $this->getCountryLabel($productInfo['country']);
        $content = $this->formatContent($productInfo['content']);

        $markdownContent = "## {$updateTimeLocal} {$country} 产品更新通知\n\n";
        $markdownContent .= "**【地区】**：{$country}\n\n";
        $markdownContent .= "**【产品】**：H5\n\n";
        $markdownContent .= "**【上线时间（当地）】**：{$updateTimeOut}\n\n";
        $markdownContent .= "**【上线时间（国内）】**：{$updateTimeLocal}\n\n";
        $markdownContent .= "**【更新内容】**：\n{$content}\n\n";
        $markdownContent .= "**【影响范围】**：{$productInfo['impact']}\n\n";
        $markdownContent .= "**【开发人员】**：{$productInfo['updater']}\n\n";
        $markdownContent .= "**【测试人员】**：{$productInfo['tester']}\n\n";
        $markdownContent .= "**【更新记录】**：[查看详情](https://record.gameyzy.com/#/upgrade-record)\n\n";

        $title = "{$updateTimeLocal} 产品更新";

        return [
            'msgtype' => 'markdown',
            'markdown' => [
                'title' => $title,
                'text' => $markdownContent
            ],
            'at' => [
                'isAtAll' => false
            ]
        ];
    }

    private function formatDateTime(string $datetime) {
        try {
            $date = new DateTime($datetime);
            return $date->format('Y-m-d H:i');
        } catch (Exception $e) {
            return $datetime;
        }
    }

    private function getCountryLabel(string $countryCode) {
        return $this->countryMap[$countryCode] ?? $countryCode;
    }

    private function formatContentLink($lines) {
        $formattedLines = [];
        foreach ($lines as $line) {
            $trimmedLine = trim($line);
            if (empty($trimmedLine)) {
                $formattedLines[] = ">";
                continue;
            }

            $url = '';
            $title = '';

            $httpPos = strpos($trimmedLine, 'http');
            if ($httpPos !== false) {
                $urlEndPos = strpos($trimmedLine, ' ', $httpPos);
                if ($urlEndPos === false) {
                    $urlEndPos = strlen($trimmedLine);
                }
                $url = substr($trimmedLine, $httpPos, $urlEndPos - $httpPos);
                $url = str_replace('&amp;', '&', $url);

                $titleStart = strpos($trimmedLine, '（', $urlEndPos);
                if ($titleStart === false) {
                    $titleStart = strpos($trimmedLine, '(', $urlEndPos);
                }
                if ($titleStart !== false) {
                    $titleEnd = strpos($trimmedLine, '）', $titleStart);
                    if ($titleEnd === false) {
                        $titleEnd = strpos($trimmedLine, ')', $titleStart);
                    }
                    if ($titleEnd !== false) {
                        $title = substr($trimmedLine, $titleStart + 3, $titleEnd - $titleStart - 3);
                        $title = trim($title);
                    }
                }
            }

            if (!empty($url) && !empty($title)) {
                $formattedLines[] = "> [{$title}]({$url})\n";
            } else {
                $formattedLines[] = "> " . $trimmedLine . "\n";
            }
        }
        return $formattedLines;
    }

    private function formatContent(string $content) {
        $linesTemp = explode("\n", $content);
        $formattedLines = $this->formatContentLink($linesTemp);
        return implode("\n", $formattedLines);
    }

    private function sendToDingTalk(string $token, array $message) {
        $url = $this->dingtalkApiUrl . $token;
        $jsonMessage = json_encode($message, JSON_UNESCAPED_UNICODE);

        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonMessage);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Content-Length: ' . strlen($jsonMessage)
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            if (curl_errno($ch)) {
                throw new Exception("CURL错误：" . curl_error($ch));
            }

            curl_close($ch);

            $result = json_decode($response, true);
            return [
                'token' => $token,
                'success' => $httpCode == 200 && isset($result['errcode']) && $result['errcode'] == 0,
                'errcode' => $result['errcode'] ?? $httpCode,
                'errmsg' => $result['errmsg'] ?? 'unknown error'
            ];
        } catch (Exception $e) {
            return [
                'token' => $token,
                'success' => false,
                'errcode' => -1,
                'errmsg' => $e->getMessage()
            ];
        }
    }
}
