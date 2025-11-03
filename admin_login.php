<?php
// admin_login.php - 管理员登录页面（数据库版本）

session_start();
require_once 'admin_config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = '用户名和密码不能为空。';
    } else {
        $admin_info = verify_admin_login($username, $password);
        
        if ($admin_info) {
            // 登录成功
            $_SESSION['admin_id'] = $admin_info['id'];
            $_SESSION['admin_username'] = $admin_info['username'];
            header("Location: admin_dashboard.php");
            exit;
        } else {
            $error = '用户名或密码错误。';
        }
    }
}

// 如果已经登录，直接跳转到仪表板
if (isset($_SESSION['admin_id'])) {
    header("Location: admin_dashboard.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>管理员登录 - 易对接</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex; 
            justify-content: center; 
            align-items: center; 
            height: 100vh; 
        }
        .login-container { 
            background-color: white; 
            padding: 50px; 
            border-radius: 10px; 
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3); 
            width: 100%; 
            max-width: 400px; 
        }
        .login-container h1 { 
            text-align: center; 
            color: #333; 
            margin-bottom: 10px; 
            font-size: 28px; 
        }
        .login-container p { 
            text-align: center; 
            color: #999; 
            margin-bottom: 30px; 
            font-size: 14px; 
        }
        .error { 
            color: #d32f2f; 
            text-align: center; 
            margin-bottom: 20px; 
            padding: 12px; 
            background-color: #ffebee; 
            border-radius: 5px; 
            border-left: 4px solid #d32f2f; 
        }
        .form-group { 
            margin-bottom: 20px; 
        }
        .form-group label { 
            display: block; 
            margin-bottom: 8px; 
            font-weight: bold; 
            color: #555; 
            font-size: 14px; 
        }
        .form-group input { 
            width: 100%; 
            padding: 12px; 
            border: 1px solid #ddd; 
            border-radius: 5px; 
            font-size: 14px; 
            transition: border-color 0.3s; 
        }
        .form-group input:focus { 
            outline: none; 
            border-color: #667eea; 
            box-shadow: 0 0 5px rgba(102, 126, 234, 0.3); 
        }
        .btn-login { 
            width: 100%; 
            padding: 12px; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
            color: white; 
            border: none; 
            border-radius: 5px; 
            cursor: pointer; 
            font-size: 16px; 
            font-weight: bold; 
            transition: opacity 0.3s; 
        }
        .btn-login:hover { 
            opacity: 0.9; 
        }
        .login-info { 
            color: #999; 
            font-size: 12px; 
            text-align: center; 
            margin-top: 20px; 
            line-height: 1.6; 
        }
        .login-info strong { 
            color: #667eea; 
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h1>易对接</h1>
        <p>后台管理系统</p>
        
        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label for="username">管理员账号</label>
                <input type="text" id="username" name="username" required placeholder="输入管理员账号" autofocus>
            </div>
            <div class="form-group">
                <label for="password">密码</label>
                <input type="password" id="password" name="password" required placeholder="输入密码">
            </div>
            <button type="submit" class="btn-login">登录</button>
        </form>
        
        <div class="login-info">
            <strong>默认账号：</strong> admin<br>
            <strong>默认密码：</strong> 123456<br>
            <small>登录后可在设置中修改密码</small>
        </div>
    </div>
</body>
</html>
