<?php
// admin_user_action.php - 用户操作处理（数据库版本）

session_start();
require_once 'admin_config.php';

if (!isset($_SESSION['admin_id'])) {
    die('未授权');
}

$admin_username = $_SESSION['admin_username'];
$user = isset($_GET['user']) ? $_GET['user'] : '';
$action = isset($_GET['action']) ? $_GET['action'] : '';

function output_result($success, $message) {
    $color = $success ? '#4caf50' : '#f44336';
    echo "<!DOCTYPE html><html lang='zh-CN'><head><meta charset='UTF-8'><title>操作结果</title><style>body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background-color: #f5f5f5; } .container { background-color: white; padding: 30px; border-radius: 8px; max-width: 500px; margin: 50px auto; box-shadow: 0 2px 8px rgba(0,0,0,0.1); } h1 { color: {$color}; } .message { margin: 20px 0; line-height: 1.6; } .back-link { display: inline-block; margin-top: 20px; padding: 10px 20px; background-color: #667eea; color: white; text-decoration: none; border-radius: 5px; }</style><meta http-equiv='refresh' content='2;url=admin_user_list.php'></head><body><div class='container'><h1>" . ($success ? '✓ 操作成功' : '✗ 操作失败') . "</h1><div class='message'>" . htmlspecialchars($message) . "</div><p>2秒后自动返回用户管理页...</p><a href='admin_user_list.php' class='back-link'>立即返回</a></div></body></html>";
    exit;
}

if (empty($user) || empty($action)) {
    output_result(false, '参数不完整。');
}

$user_path = "userss/" . $admin_username . "/userss/" . $user;

if (!is_dir($user_path)) {
    output_result(false, '用户不存在。');
}

switch ($action) {
    case 'seal':
        $duration = isset($_GET['duration']) ? (int)$_GET['duration'] : 0;
        if ($duration <= 0) {
            output_result(false, '封禁时长必须大于0秒。');
        }
        
        $seal_file = $user_path . "/seal";
        $seal_time = time() + $duration;
        
        if (file_put_contents($seal_file, $seal_time) !== false) {
            output_result(true, "用户 **{$user}** 已成功封禁，解封时间：" . date("Y-m-d H:i:s", $seal_time));
        } else {
            output_result(false, '写入封禁文件失败，请检查目录权限。');
        }
        break;

    case 'unseal':
        $seal_file = $user_path . "/seal";
        
        if (file_put_contents($seal_file, '0') !== false) {
            output_result(true, "用户 **{$user}** 已成功解封。");
        } else {
            output_result(false, '写入解封文件失败，请检查目录权限。');
        }
        break;
        
    case 'add_money':
        $amount = isset($_GET['amount']) ? (float)$_GET['amount'] : 0;
        if ($amount <= 0) {
            output_result(false, '增加金币数量必须大于0。');
        }
        
        $money_file = $user_path . "/money";
        $current_money = file_exists($money_file) ? (float)trim(file_get_contents($money_file)) : 0;
        $new_money = $current_money + $amount;
        
        if (file_put_contents($money_file, $new_money) !== false) {
            output_result(true, "已成功为用户 **{$user}** 增加 **{$amount}** 余额。当前余额：{$new_money}");
        } else {
            output_result(false, '写入余额文件失败，请检查目录权限。');
        }
        break;

    case 'add_viptime':
        $duration = isset($_GET['amount']) ? (int)$_GET['amount'] : 0;
        if ($duration <= 0) {
            output_result(false, '增加会员时长必须大于0秒。');
        }
        
        $viptime_file = $user_path . "/viptime";
        $current_viptime = file_exists($viptime_file) ? (int)trim(file_get_contents($viptime_file)) : 0;
        
        // 如果已过期，从现在开始算
        $viptime = ($current_viptime > time()) ? $current_viptime : time();
        $new_viptime = $viptime + $duration;
        
        if (file_put_contents($viptime_file, $new_viptime) !== false) {
            output_result(true, "已成功为用户 **{$user}** 增加 **{$duration}** 秒会员时长。新的到期时间：" . date("Y-m-d H:i:s", $new_viptime));
        } else {
            output_result(false, '写入会员时长文件失败，请检查目录权限。');
        }
        break;

    case 'delete':
        // 删除用户目录
        function deleteDir($dir) {
            if (!is_dir($dir)) return false;
            $files = scandir($dir);
            foreach ($files as $file) {
                if ($file === '.' || $file === '..') continue;
                $path = $dir . '/' . $file;
                if (is_dir($path)) {
                    deleteDir($path);
                } else {
                    unlink($path);
                }
            }
            return rmdir($dir);
        }
        
        if (deleteDir($user_path)) {
            output_result(true, "用户 **{$user}** 已被永久删除。");
        } else {
            output_result(false, '删除用户失败，请检查目录权限。');
        }
        break;

    default:
        output_result(false, '未知的操作类型。');
        break;
}

?>
