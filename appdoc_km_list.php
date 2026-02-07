<?php
/**
 * 查询全部未使用卡密接口 (增强版)
 * 功能：查询所有未使用卡密，并自动转换秒数为易读的时间格式
 */

if($_GET['pass'] != 'zxc123.0') die('管理密码错误');

$mysql = [
    "host" => "localhost",
    "username" => "appdoc",
    "password" => "123456",
    "dbname" => "appdoc",
];

// 时间转换函数：将秒数转换为 年/月/天/小时/分钟/秒 的易读格式
function formatSeconds($seconds) {
    if (!is_numeric($seconds)) return $seconds;
    $seconds = (int)$seconds;
    if ($seconds < 1) return "0秒";
    
    $tokens = [
        31536000 => '年', // 365 * 24 * 3600
        2592000 => '月',  // 30 * 24 * 3600
        86400 => '天',   // 24 * 3600
        3600 => '小时',
        60 => '分钟',
        1 => '秒'
    ];

    $result = [];
    foreach ($tokens as $unit => $text) {
        if ($seconds < $unit) continue;
        $numberOfUnits = floor($seconds / $unit);
        $result[] = $numberOfUnits . $text;
        $seconds -= $numberOfUnits * $unit;
    }
    
    return implode('', $result);
}

// 连接数据库
$link = new mysqli($mysql["host"], $mysql["username"], $mysql["password"], $mysql["dbname"]);
if($link->connect_error) die("数据库连接失败: " . $link->connect_error);
mysqli_set_charset($link, 'utf8');

// 查询所有卡密
$sql = "SELECT * FROM `vip_km` ORDER BY `type` ASC";
$result = $link->query($sql);

if ($result->num_rows > 0) {
    echo "--- 未使用卡密列表 (已自动转换时长) ---<br>";
    while($row = $result->fetch_assoc()) {
        if ($row["type"] == 'vip') {
            $type_name = "会员时长";
            $display_time = formatSeconds($row["time"]);
        } else {
            $type_name = "余额/文档";
            $display_time = $row["time"] . " (个/元)";
        }
        echo "卡密: " . $row["km"] . " | 类型: " . $type_name . " | 时长/数值: " . $display_time . "<br>";
    }
} else {
    echo "暂无未使用的卡密";
}

$link->close();
?>
