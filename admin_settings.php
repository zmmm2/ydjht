<?php
// admin_settings.php - 管理员设置页面（数据库版本）

session_start();
require_once 'admin_config.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit;
}

$admin_id = $_SESSION['admin_id'];
$admin_username = $_SESSION['admin_username'];
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_pass = $_POST['current_pass'] ?? '';
    $new_pass = $_POST['new_pass'] ?? '';
    $confirm_pass = $_POST['confirm_pass'] ?? '';
    
    // 验证当前密码
    $admin_info = verify_admin_login($admin_username, $current_pass);
    
    if (!$admin_info) {
        $message = '<div class="alert alert-danger">✗ 当前密码错误！</div>';
    } elseif ($new_pass !== $confirm_pass) {
        $message = '<div class="alert alert-danger">✗ 新密码和确认密码不一致！</div>';
    } elseif (strlen($new_pass) < 6) {
        $message = '<div class="alert alert-danger">✗ 新密码长度不能少于6位！</div>';
    } else {
        if (update_admin_password($admin_id, $new_pass)) {
            $message = '<div class="alert alert-success">✓ 密码修改成功！请使用新密码重新登录。</div>';
            echo '<meta http-equiv="refresh" content="2;url=admin_logout.php">';
        } else {
            $message = '<div class="alert alert-danger">✗ 密码修改失败，请重试。</div>';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>账号设置 - 易对接</title>
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
        .settings-form { 
            background-color: white; 
            padding: 30px; 
            border-radius: 10px; 
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1); 
            max-width: 500px; 
        }
        .form-group { margin-bottom: 20px; }
        .form-group label { 
            display: block; 
            margin-bottom: 8px; 
            font-weight: bold; 
            color: #555; 
        }
        .form-group input[type="text"], 
        .form-group input[type="password"] { 
            width: 100%; 
            padding: 12px; 
            border: 1px solid #ddd; 
            border-radius: 5px; 
            box-sizing: border-box; 
            font-size: 14px; 
        }
        .form-group input:focus { 
            outline: none; 
            border-color: #667eea; 
            box-shadow: 0 0 5px rgba(102, 126, 234, 0.3); 
        }
        .btn-submit { 
            padding: 12px 25px; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
            color: white; 
            border: none; 
            border-radius: 5px; 
            cursor: pointer; 
            font-size: 14px; 
            font-weight: bold; 
        }
        .btn-submit:hover { opacity: 0.9; }
        .alert { padding: 12px 15px; border-radius: 5px; margin-bottom: 20px; }
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
            <h1>⚙️ 账号设置</h1>

            <?php echo $message; ?>

            <div class="settings-form">
                <h2 style="margin-bottom: 20px;">修改密码</h2>
                <form method="POST">
                    <div class="form-group">
                        <label for="username">当前管理员账号</label>
                        <input type="text" id="username" value="<?php echo htmlspecialchars($admin_username); ?>" disabled>
                    </div>
                    <div class="form-group">
                        <label for="current_pass">当前密码</label>
                        <input type="password" id="current_pass" name="current_pass" required>
                    </div>
                    <div class="form-group">
                        <label for="new_pass">新密码</label>
                        <input type="password" id="new_pass" name="new_pass" required>
                    </div>
                    <div class="form-group">
                        <label for="confirm_pass">确认新密码</label>
                        <input type="password" id="confirm_pass" name="confirm_pass" required>
                    </div>
                    <button type="submit" class="btn-submit">修改密码</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
