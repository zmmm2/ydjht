<?php
// dashboard_settings.php - 后台账号设置页面（文件系统版本）

session_start();

if (!isset($_SESSION['admin'])) {
    header("Location: dashboard_login.php");
    exit;
}

$admin = $_SESSION['admin'];
$admin_path = "userss/" . $admin;

if (!is_dir($admin_path)) {
    session_destroy();
    header("Location: dashboard_login.php");
    exit;
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_pass = $_POST['current_pass'] ?? '';
    $new_pass = $_POST['new_pass'] ?? '';
    $confirm_pass = $_POST['confirm_pass'] ?? '';
    
    $pass_file = $admin_path . "/admin/passprotect556";
    $stored_pass = trim(file_get_contents($pass_file));
    
    if ($current_pass !== $stored_pass) {
        $message = '<div class="alert error">✗ 当前密码错误！</div>';
    } elseif ($new_pass !== $confirm_pass) {
        $message = '<div class="alert error">✗ 新密码和确认密码不一致！</div>';
    } elseif (strlen($new_pass) < 1) {
        $message = '<div class="alert error">✗ 新密码不能为空！</div>';
    } else {
        if (file_put_contents($pass_file, $new_pass) !== false) {
            $message = '<div class="alert success">✓ 密码修改成功！请使用新密码重新登录。</div>';
            // 销毁 session，强制重新登录
            session_destroy();
            echo '<meta http-equiv="refresh" content="2;url=dashboard_login.php">';
        } else {
            $message = '<div class="alert error">✗ 密码修改失败，请检查目录权限。</div>';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>账号设置 - 易对接</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f5f7fa; }
        .container { display: flex; min-height: 100vh; }
        .sidebar { width: 250px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; position: fixed; height: 100vh; overflow-y: auto; }
        .sidebar h2 { margin-bottom: 30px; font-size: 20px; }
        .sidebar a { color: white; text-decoration: none; display: block; padding: 12px 15px; margin-bottom: 5px; border-radius: 5px; transition: background 0.3s; }
        .sidebar a:hover { background-color: rgba(255, 255, 255, 0.2); }
        .content { margin-left: 250px; flex-grow: 1; padding: 30px; }
        h1 { color: #333; margin-bottom: 20px; }
        .settings-form { background-color: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1); max-width: 500px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: bold; color: #555; }
        .form-group input[type="text"], .form-group input[type="password"] { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 5px; box-sizing: border-box; font-size: 14px; }
        .form-group input:focus { outline: none; border-color: #667eea; box-shadow: 0 0 5px rgba(102, 126, 234, 0.3); }
        .btn-submit { padding: 12px 25px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 14px; font-weight: bold; }
        .btn-submit:hover { opacity: 0.9; }
        .alert { padding: 12px 15px; border-radius: 5px; margin-bottom: 20px; }
        .alert.success { background-color: #c8e6c9; color: #2e7d32; }
        .alert.error { background-color: #ffcdd2; color: #c62828; }
    </style>
</head>
<body>
    <div class="container">
        <div class="sidebar">
            <h2>易对接管理</h2>
            <a href="dashboard_index.php">📊 仪表板</a>
            <a href="dashboard_user_manage.php">👥 用户管理</a>
            <a href="dashboard_settings.php">⚙️ 账号设置</a>
            <a href="dashboard_logout.php">🚪 退出登录</a>
        </div>

        <div class="content">
            <h1>⚙️ 账号设置</h1>

            <?php echo $message; ?>

            <div class="settings-form">
                <h2 style="margin-bottom: 20px;">修改密码</h2>
                <form method="POST">
                    <div class="form-group">
                        <label for="username">当前管理员账号</label>
                        <input type="text" id="username" value="<?php echo htmlspecialchars($admin); ?>" disabled>
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
