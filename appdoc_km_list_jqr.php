<?php
/**
 * 文件名建议: appdoc_km_list.php
 */

if($_GET['pass'] != 'zxc123.0') die('管理密码错误');

$mysql = [
    "host" => "localhost",
    "username" => "appdoc",
    "password" => "123456",
    "dbname" => "appdoc",
];

// 连接数据库
$link = new mysqli($mysql["host"], $mysql["username"], $mysql["password"], $mysql["dbname"]);
if($link->connect_error) die("数据库连接失败: " . $link->connect_error);
mysqli_set_charset($link, 'utf8');

// 查询所有卡密
$sql = "SELECT * FROM `vip_km` ORDER BY `type` ASC";
$result = $link->query($sql);

if ($result->num_rows > 0) {
    echo "--- 未使用卡密列表 ---<br>";
    while($row = $result->fetch_assoc()) {
        $type_name = ($row["type"] == 'vip') ? "会员时长" : "余额/文档";
        echo "卡密: " . $row["km"] . " | 类型: " . $type_name . " | 数值: " . $row["time"] . "<br>";
    }
} else {
    echo "暂无未使用的卡密";
}

$link->close();
?>