<?php
// 浏览器cookie
$cookie = "";

// 跨站crsf
$crsf = '';

// server酱 key
$server_chan_key = '';

// 请求头
$header = [
    'Cookie: '.$cookie,
    'Host: api.bilibili.com',
    'https://www.bilibili.com',
    //'Referer: https://www.bilibili.com/bangumi/play/ep285064',
    'Sec-Fetch-Mode: cors',
    'Sec-Fetch-Site: same-site',
    'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/79.0.3945.79 Safari/537.36'
];

// 获取今日已经投币的经验值
console_msg('获取今日剩余投币奖励...');
$today_exp = php_curl('https://api.bilibili.com/x/web-interface/coin/today/exp', $header);
$today_exp = json_decode($today_exp, true);
// 如果获取投币经验值失败
if(!isset($today_exp['data'])) {
    // 如果登录已失效
    if($today_exp['code'] == -101) {
        console_msg('获取今日剩余投币奖励失败：登录失效了。', 0);
        if($server_chan_key) {
            serverchan_send('今日投币运行结果：登录失效了。', '', $server_chan_key);
        }
    }
    else {
        console_msg('获取今日剩余投币奖励失败！可能接口已经失效。', 0);
        if($server_chan_key) {
            serverchan_send('今日投币运行结果：获取今日剩余投币奖励失败！可能接口已经失效。', '', $server_chan_key);
        }
    }
    exit;
}
// 如果今日经验达到上限（50）
$today_exp['data'] = 30;
if($today_exp['data'] >= 50) {
    console_msg('今日投币奖励已经达到最大上限：'.$today_exp['data'], 2);
    if($server_chan_key) {
        serverchan_send('今日投币运行结果：今日投币奖励已经达到最大上限：'.$today_exp['data'], '', $server_chan_key);
    }
    exit;
}
// 剩余可获取经验数
$need_exp = 50 - $today_exp['data'];
console_msg('今日已获取投币经验：'.$today_exp['data'].', 还可以获取投币经验：'.($need_exp));
// 获取番剧更新列表
console_msg('获取最新番剧列表中，请稍后...');
$anime_newlist = php_curl('https://api.bilibili.com/x/web-interface/newlist?rid=33');
$anime_newlist = json_decode($anime_newlist, true);
// 如果获取列表失败
if(!isset($anime_newlist['data']['archives'])) {
    console_msg('获取最新番剧列表失败！可能接口已经失效。', 0);
    if($server_chan_key) {
        serverchan_send('今日投币运行结果：获取最新番剧列表失败！可能接口已经失效。', '', $server_chan_key);
    }
    exit;
}
// 番剧列表 aid 数组
$aid_list = [];
foreach ($anime_newlist['data']['archives'] as $v) {
    $aid_list[] = $v['aid'];
}
console_msg('获取番剧列表 aid 成功！共计'.count($aid_list).'个。', 2);
// 自动投币
console_msg('开始投币...');
$today_coin = 0;
foreach ($aid_list as $v) {
    $send_coin_result = php_curl('https://api.bilibili.com/x/web-interface/coin/add', $header, 1, [
        'aid' => $v,
        'multiply' => 1,
        'csrf' => $crsf
    ]);
    $send_coin_result = json_decode($send_coin_result, true);
    // 如果该视频已经投过币
    if($send_coin_result['code'] == 34005) {
        console_msg('视频 '.$v.' 已经投过硬币，跳过...', 3);
    }
    // 如果投币成功
    else if($send_coin_result['code'] == 0) {
        console_msg('视频 '.$v.' 投币成功！', 2);
        $today_coin++;
        $need_exp -= 10;
        // 如果今日经验已经刷满
        if($need_exp <= 0) {
            console_msg('今日投币任务已完成！', 2);
            if($server_chan_key) {
                serverchan_send('今日投币运行结果：今日投币任务已完成！一共投了'.($today_coin).'个硬币。', '', $server_chan_key);
            }
            exit;
        }
    }
    // 投币失败情况
    else {
        console_msg('视频 '.$v.' 投币失败，原因：'.json_encode($send_coin_result), 0);
        exit;
    }
}
// 循环完经验没刷满情况
if($server_chan_key) {
    serverchan_send('今日投币运行结果：投币没有全部完成。', '', $server_chan_key);
}
console_msg('投币没有全部完成。', 3);
exit;

// server酱微信推送
function serverchan_send($text, $desp = '', $key) {
    $postdata = http_build_query([
        'text' => $text,
        'desp' => $desp
    ]);
    $opts = [
        'http' => [
            'method'  => 'POST',
            'header'  => 'Content-type: application/x-www-form-urlencoded',
            'content' => $postdata
        ]
    ];
    $context = stream_context_create($opts);
    // 调用server酱推送接口
    return $result = file_get_contents('https://sc.ftqq.com/'.$key.'.send', false, $context);
}

// 打印日志到屏幕
function console_msg($msg, $level=1) {
    $m = '';
    // 日志等级
    if($level == 0) $m .= '[ERROR]';
    if($level == 1) $m .= '[INFO]';
    if($level == 2) $m .= '[SUCCESS]';
    if($level == 3) $m .= '[WARNING]';
    // 颜色样式
    $styles = [
        "\033[31;1m%s\033[0m", 
        "\033[36;1m%s\033[0m", 
        "\033[32;1m%s\033[0m",
        "\033[33;1m%s\033[0m"
    ];
    $format = '%s';
    if (isset($styles[$level])) {
        $format = $styles[$level];
    }
    $m .= "[".date("Y-m-d H:i:s")."] ";
    $m .= $msg;
    $m .= PHP_EOL;
    printf($format, $m);
}

// curl方法
function php_curl($url, $header = [], $is_post = 0, $postdata = []) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt ($ch, CURLOPT_HEADER, 0);
    // 如果是POST方法
    if ($is_post) {
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
    }
    // 如果定义了请求头
    if($header) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
    }
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);  
    $output = curl_exec($ch);
    $info = curl_getinfo($ch);
    curl_close($ch);
    return $output;
}