<?php
// admin_user_list.php - 用户管理列表页面（数据库版本）

session_start();
require_once 'admin_config.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit;
}

$admin_username = $_SESSION['admin_username'];

// 获取用户列表
$user_list = [];
$users_dir = "userss/" . $admin_username . "/userss";

if (is_dir($users_dir)) {
    $users = scandir($users_dir);
    foreach ($users as $user) {
        if ($user === '.' || $user === '..') continue;
        
        $user_path = $users_dir . "/" . $user;
        if (!is_dir($user_path)) continue;
        
        // 读取用户数据
        $name_file = $user_path . "/name";
        $grade_file = $user_path . "/grade";
        $money_file = $user_path . "/money";
        $viptime_file = $user_path . "/viptime";
        $sealtime_file = $user_path . "/seal";
        $registertime_file = $user_path . "/registertime";
        $doc_file = $user_path . "/file_count";
        
        $data = [
            'user' => $user,
            'name' => file_exists($name_file) ? trim(file_get_contents($name_file)) : '未设置',
            'grade' => file_exists($grade_file) ? trim(file_get_contents($grade_file)) : '普通用户',
            'money' => file_exists($money_file) ? trim(file_get_contents($money_file)) : '0',
            'viptime' => file_exists($viptime_file) ? (int)trim(file_get_contents($viptime_file)) : 0,
            'sealtime' => file_exists($sealtime_file) ? (int)trim(file_get_contents($sealtime_file)) : 0,
            'registertime' => file_exists($registertime_file) ? (int)trim(file_get_contents($registertime_file)) : 0,
            'doc_count' => file_exists($doc_file) ? (int)trim(file_get_contents($doc_file)) : 0,
        ];
        
        $data['vip_status'] = ($data['viptime'] > time()) ? date("Y-m-d H:i:s", $data['viptime']) : '已过期';
        $data['seal_status'] = ($data['sealtime'] > time()) ? date("Y-m-d H:i:s", $data['sealtime']) : '未封禁';
        $data['register_time_str'] = ($data['registertime'] > 0) ? date("Y-m-d H:i:s", $data['registertime']) : '未知';
        
        $user_list[] = $data;
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>用户管理 - 易对接</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f5f7fa; }
        .container { display: flex; min-height: 100vh; }
        .sidebar { 
            width: 250px; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
            color: white; 
            padding: 20px; 
            position: fixed; 
            height: 100vh; 
            overflow-y: auto; 
        }
        .sidebar h2 { margin-bottom: 30px; font-size: 20px; }
        .sidebar a { 
            color: white; 
            text-decoration: none; 
            display: block; 
            padding: 12px 15px; 
            margin-bottom: 5px; 
            border-radius: 5px; 
            transition: background 0.3s; 
        }
        .sidebar a:hover { background-color: rgba(255, 255, 255, 0.2); }
        .content { margin-left: 250px; flex-grow: 1; padding: 30px; }
        h1 { color: #333; margin-bottom: 20px; }
        table { 
            width: 100%; 
            border-collapse: collapse; 
            background-color: white; 
            border-radius: 10px; 
            overflow: hidden; 
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1); 
        }
        th, td { padding: 15px; text-align: left; border-bottom: 1px solid #eee; }
        th { background-color: #f5f5f5; font-weight: bold; color: #333; }
        tr:hover { background-color: #f9f9f9; }
        .action-form { display: flex; gap: 8px; align-items: center; margin-bottom: 8px; flex-wrap: wrap; }
        .action-form input { padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 12px; width: 100px; }
        .action-form button { padding: 8px 12px; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 12px; font-weight: bold; }
        .seal-btn { background-color: #d32f2f; }
        .seal-btn:hover { background-color: #b71c1c; }
        .unseal-btn { background-color: #f57c00; }
        .unseal-btn:hover { background-color: #e65100; }
        .money-btn { background-color: #388e3c; }
        .money-btn:hover { background-color: #2e7d32; }
        .vip-btn { background-color: #1976d2; }
        .vip-btn:hover { background-color: #1565c0; }
        .delete-btn { background-color: #c62828; }
        .delete-btn:hover { background-color: #ad1457; }
        .vip-active { color: #388e3c; font-weight: bold; }
        .vip-expired { color: #d32f2f; font-weight: bold; }
        .seal-active { color: #d32f2f; font-weight: bold; }
        .seal-inactive { color: #388e3c; }
    </style>
    <script>
        function manageUser(user, action) {
            const durationInput = document.getElementById('duration_' + user);
            const amountInput = document.getElementById('amount_' + user);
            const viptimeInput = document.getElementById('viptime_' + user);
            
            let url = 'admin_user_action.php?user=' + encodeURIComponent(user) + '&action=' + action;
            
            if (action === 'seal') {
                const duration = durationInput ? durationInput.value : 86400;
                if (!duration || isNaN(duration) || duration <= 0) {
                    alert('请输入有效的封禁时长（秒）。');
                    return;
                }
                if (!confirm('确定要对用户 ' + user + ' 封禁 ' + duration + ' 秒吗？')) return;
                url += '&duration=' + duration;
            } else if (action === 'unseal') {
                if (!confirm('确定要对用户 ' + user + ' 进行解封操作吗？')) return;
            } else if (action === 'add_money') {
                const amount = amountInput ? amountInput.value : 0;
                if (!amount || isNaN(amount) || amount <= 0) {
                    alert('请输入有效的增加金额。');
                    return;
                }
                if (!confirm('确定要为用户 ' + user + ' 增加 ' + amount + ' 余额吗？')) return;
                url += '&amount=' + amount;
            } else if (action === 'add_viptime') {
                const viptime = viptimeInput ? viptimeInput.value : 2592000;
                if (!viptime || isNaN(viptime) || viptime <= 0) {
                    alert('请输入有效的增加会员时长（秒）。');
                    return;
                }
                if (!confirm('确定要为用户 ' + user + ' 增加 ' + viptime + ' 秒会员时长吗？')) return;
                url += '&amount=' + viptime;
            } else if (action === 'delete') {
                if (!confirm('警告：确定要永久删除用户 ' + user + ' 吗？此操作不可逆！')) return;
            }
            
            window.location.href = url;
        }
    </script>
</head>
<body>
    <div class="container">
        <div class="sidebar">
            <h2>易对接管理</h2>
            <a href="admin_dashboard.php">📊 仪表板</a>
            <a href="admin_user_list.php">👥 用户管理</a>
            <a href="admin_settings.php">⚙️ 账号设置</a>
            <a href="admin_logout.php">🚪 退出登录</a>
        </div>

        <div class="content">
            <h1>👥 用户管理</h1>

            <table>
                <thead>
                    <tr>
                        <th>账号</th>
                        <th>昵称</th>
                        <th>等级</th>
                        <th>余额</th>
                        <th>文档数</th>
                        <th>注册时间</th>
                        <th>会员到期</th>
                        <th>封禁状态</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($user_list)): ?>
                        <tr><td colspan="9" style="text-align: center; padding: 30px;">未找到任何用户数据。</td></tr>
                    <?php else: ?>
                        <?php foreach ($user_list as $user): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user['user']); ?></td>
                                <td><?php echo htmlspecialchars($user['name']); ?></td>
                                <td><?php echo htmlspecialchars($user['grade']); ?></td>
                                <td>¥<?php echo htmlspecialchars($user['money']); ?></td>
                                <td><?php echo htmlspecialchars($user['doc_count']); ?></td>
                                <td><?php echo htmlspecialchars($user['register_time_str']); ?></td>
                                <td class="<?php echo $user['viptime'] > time() ? 'vip-active' : 'vip-expired'; ?>"><?php echo htmlspecialchars($user['vip_status']); ?></td>
                                <td class="<?php echo $user['sealtime'] > time() ? 'seal-active' : 'seal-inactive'; ?>"><?php echo htmlspecialchars($user['seal_status']); ?></td>
                                <td>
                                    <div class="action-form">
                                        <input type="number" id="duration_<?php echo $user['user']; ?>" placeholder="封禁秒数" value="86400">
                                        <button class="seal-btn" onclick="manageUser('<?php echo addslashes($user['user']); ?>', 'seal')">封禁</button>
                                        <button class="unseal-btn" onclick="manageUser('<?php echo addslashes($user['user']); ?>', 'unseal')">解封</button>
                                    </div>
                                    <div class="action-form">
                                        <input type="number" id="amount_<?php echo $user['user']; ?>" placeholder="增加金额" value="10">
                                        <button class="money-btn" onclick="manageUser('<?php echo addslashes($user['user']); ?>', 'add_money')">加余额</button>
                                    </div>
                                    <div class="action-form">
                                        <input type="number" id="viptime_<?php echo $user['user']; ?>" placeholder="增加会员秒数" value="2592000">
                                        <button class="vip-btn" onclick="manageUser('<?php echo addslashes($user['user']); ?>', 'add_viptime')">加会员</button>
                                    </div>
                                    <div class="action-form">
                                        <button class="delete-btn" onclick="manageUser('<?php echo addslashes($user['user']); ?>', 'delete')">删除用户</button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
