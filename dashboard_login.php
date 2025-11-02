<?php
// dashboard_login.php - 后台登录页面（文件系统版本）
// 直接从 /userss/ 目录中读取管理员账号和密码

session_start();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // 验证管理员账号和密码
    $admin_path = "userss/" . $username;
    $pass_file = $admin_path . "/admin/passprotect556";
    
    if (!is_dir($admin_path)) {
        $error = '管理员账号不存在。';
    } elseif (!file_exists($pass_file)) {
        $error = '管理员密码文件丢失。';
    } else {
        $stored_pass = trim(file_get_contents($pass_file));
        if ($password === $stored_pass) {
            // 登录成功
            $_SESSION['admin'] = $username;
            $_SESSION['admin_pass'] = $password;
            header("Location: dashboard_index.php");
            exit;
        } else {
            $error = '密码错误。';
        }
    }
}

// 如果已经登录，直接跳转到仪表板
if (isset($_SESSION['admin'])) {
    header("Location: dashboard_index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>后台登录 - 易对接</title>
    <style>
        body { font-family: Arial, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .login-container { background-color: #fff; padding: 40px; border-radius: 10px; box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2); width: 350px; }
        h2 { text-align: center; color: #333; margin-bottom: 30px; font-size: 24px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: bold; color: #555; }
        .form-group input[type="text"], .form-group input[type="password"] { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 5px; box-sizing: border-box; font-size: 14px; }
        .form-group input:focus { outline: none; border-color: #667eea; box-shadow: 0 0 5px rgba(102, 126, 234, 0.3); }
        .error { color: #d32f2f; text-align: center; margin-bottom: 15px; padding: 10px; background-color: #ffebee; border-radius: 5px; }
        .btn-login { width: 100%; padding: 12px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; font-weight: bold; }
        .btn-login:hover { opacity: 0.9; }
        .info { color: #666; font-size: 12px; text-align: center; margin-top: 15px; }
    </style>
</head>
<body>
    <div class="login-container">
        <h2>易对接后台登录</h2>
        <?php if ($error): ?>
            <p class="error"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>
        <form method="POST">
            <div class="form-group">
                <label for="username">管理员账号</label>
                <input type="text" id="username" name="username" required placeholder="输入管理员账号">
            </div>
            <div class="form-group">
                <label for="password">密码</label>
                <input type="password" id="password" name="password" required placeholder="输入密码">
            </div>
            <button type="submit" class="btn-login">登录</button>
            <p class="info">示例账号：zxcv25 (密码从 userss/zxcv25/admin/passprotect556 文件中读取)</p>
        </form>
    </div>
</body>
</html>
