<?php
// admin_dashboard.php - 管理员仪表板（数据库版本）

session_start();
require_once 'admin_config.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit;
}

$admin_id = $_SESSION['admin_id'];
$admin_username = $_SESSION['admin_username'];
$admin_info = get_admin_info($admin_id);

if (!$admin_info) {
    session_destroy();
    header("Location: admin_login.php");
    exit;
}

$message = '';

// 处理公告更新
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['announcement_content'])) {
    $announcement_file = "userss/" . $admin_username . "/admin/set/announcement";
    $dir = dirname($announcement_file);
    
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
    
    if (file_put_contents($announcement_file, $_POST['announcement_content']) !== false) {
        $message = '<div class="alert alert-success">✓ 公告更新成功！</div>';
    } else {
        $message = '<div class="alert alert-danger">✗ 公告更新失败，请检查目录权限。</div>';
    }
}

// 读取公告
$announcement_file = "userss/" . $admin_username . "/admin/set/announcement";
$announcement = file_exists($announcement_file) ? file_get_contents($announcement_file) : "欢迎使用易对接平台！";

// 统计数据
$stats = [
    'total_users' => 0,
    'vip_active' => 0,
    'vip_expired' => 0,
    'total_money' => 0,
    'total_docs' => 0,
];

$users_dir = "userss/" . $admin_username . "/userss";
if (is_dir($users_dir)) {
    $users = scandir($users_dir);
    foreach ($users as $user) {
        if ($user === '.' || $user === '..') continue;
        
        $user_path = $users_dir . "/" . $user;
        if (!is_dir($user_path)) continue;
        
        $stats['total_users']++;
        
        // 读取会员时间
        $viptime_file = $user_path . "/viptime";
        $viptime = file_exists($viptime_file) ? (int)trim(file_get_contents($viptime_file)) : 0;
        if ($viptime > time()) {
            $stats['vip_active']++;
        } else {
            $stats['vip_expired']++;
        }
        
        // 读取余额
        $money_file = $user_path . "/money";
        $money = file_exists($money_file) ? (float)trim(file_get_contents($money_file)) : 0;
        $stats['total_money'] += $money;
        
        // 读取文档数量
        $doc_file = $user_path . "/file_count";
        $doc_count = file_exists($doc_file) ? (int)trim(file_get_contents($doc_file)) : 0;
        $stats['total_docs'] += $doc_count;
    }
}

$stats['total_money'] = number_format($stats['total_money'], 2);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>管理员仪表板 - 易对接</title>
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
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .header h1 { color: #333; font-size: 28px; }
        .logout-btn { 
            background-color: #d32f2f; 
            color: white; 
            padding: 10px 20px; 
            border: none; 
            border-radius: 5px; 
            cursor: pointer; 
            text-decoration: none; 
        }
        .logout-btn:hover { background-color: #b71c1c; }
        .stats-grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); 
            gap: 20px; 
            margin-bottom: 30px; 
        }
        .stat-card { 
            background-color: white; 
            padding: 20px; 
            border-radius: 10px; 
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1); 
        }
        .stat-card h3 { color: #666; font-size: 14px; margin-bottom: 10px; }
        .stat-card p { font-size: 32px; font-weight: bold; color: #667eea; }
        .announcement-section { 
            background-color: white; 
            padding: 25px; 
            border-radius: 10px; 
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1); 
        }
        .announcement-section h2 { color: #333; margin-bottom: 15px; font-size: 20px; }
        .announcement-section textarea { 
            width: 100%; 
            height: 150px; 
            padding: 12px; 
            border: 1px solid #ddd; 
            border-radius: 5px; 
            resize: vertical; 
            margin-bottom: 15px; 
            font-family: Arial, sans-serif; 
        }
        .announcement-section button { 
            padding: 12px 25px; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
            color: white; 
            border: none; 
            border-radius: 5px; 
            cursor: pointer; 
            font-size: 14px; 
            font-weight: bold; 
        }
        .announcement-section button:hover { opacity: 0.9; }
        .alert { padding: 12px 15px; border-radius: 5px; margin-bottom: 15px; }
        .alert-success { background-color: #c8e6c9; color: #2e7d32; }
        .alert-danger { background-color: #ffcdd2; color: #c62828; }
    </style>
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
            <div class="header">
                <h1>欢迎，<?php echo htmlspecialchars($admin_username); ?></h1>
                <a href="admin_logout.php" class="logout-btn">退出登录</a>
            </div>

            <?php echo $message; ?>

            <!-- 数据统计 -->
            <h2 style="color: #333; margin-bottom: 20px;">平台数据概览</h2>
            <div class="stats-grid">
                <div class="stat-card">
                    <h3>总用户数</h3>
                    <p><?php echo $stats['total_users']; ?></p>
                </div>
                <div class="stat-card">
                    <h3>活跃会员数</h3>
                    <p><?php echo $stats['vip_active']; ?></p>
                </div>
                <div class="stat-card">
                    <h3>会员到期用户数</h3>
                    <p><?php echo $stats['vip_expired']; ?></p>
                </div>
                <div class="stat-card">
                    <h3>平台总余额</h3>
                    <p>¥<?php echo $stats['total_money']; ?></p>
                </div>
                <div class="stat-card">
                    <h3>总文档数量</h3>
                    <p><?php echo $stats['total_docs']; ?></p>
                </div>
            </div>

            <!-- 公告管理 -->
            <div class="announcement-section">
                <h2>公告管理</h2>
                <form method="POST">
                    <textarea name="announcement_content" placeholder="输入新的平台公告内容..."><?php echo htmlspecialchars($announcement); ?></textarea>
                    <button type="submit">更新公告</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
