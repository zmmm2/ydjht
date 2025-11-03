<?php
// admin_config.php - 数据库连接和管理员认证配置

// 数据库连接配置
define('DB_HOST', 'localhost');
define('DB_USER', 'ydjht');        // 改为您的数据库用户名
define('DB_PASS', 'ydjht');        // 改为您的数据库密码
define('DB_NAME', 'appdoc');       // 数据库名称

// 创建数据库连接
function get_db_connection() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        die('数据库连接失败: ' . $conn->connect_error);
    }
    
    $conn->set_charset("utf8mb4");
    return $conn;
}

// 检查管理员登录状态
function check_admin_login() {
    session_start();
    
    if (!isset($_SESSION['admin_id']) || !isset($_SESSION['admin_username'])) {
        header("Location: admin_login.php");
        exit;
    }
}

// 验证管理员登录
function verify_admin_login($username, $password) {
    $conn = get_db_connection();
    
    // 查询管理员信息
    $stmt = $conn->prepare("SELECT id, username, password FROM admin_users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $conn->close();
        return false;
    }
    
    $row = $result->fetch_assoc();
    
    // 验证密码（使用 password_verify 进行安全的密码验证）
    if (password_verify($password, $row['password'])) {
        $conn->close();
        return array(
            'id' => $row['id'],
            'username' => $row['username']
        );
    }
    
    $conn->close();
    return false;
}

// 获取管理员信息
function get_admin_info($admin_id) {
    $conn = get_db_connection();
    
    $stmt = $conn->prepare("SELECT id, username, created_at FROM admin_users WHERE id = ?");
    $stmt->bind_param("i", $admin_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $conn->close();
        return false;
    }
    
    $admin = $result->fetch_assoc();
    $conn->close();
    return $admin;
}

// 更新管理员密码
function update_admin_password($admin_id, $new_password) {
    $conn = get_db_connection();
    
    $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);
    
    $stmt = $conn->prepare("UPDATE admin_users SET password = ? WHERE id = ?");
    $stmt->bind_param("si", $hashed_password, $admin_id);
    $success = $stmt->execute();
    
    $conn->close();
    return $success;
}

// 获取所有管理员（用于管理员管理）
function get_all_admins() {
    $conn = get_db_connection();
    
    $result = $conn->query("SELECT id, username, created_at FROM admin_users ORDER BY created_at DESC");
    $admins = [];
    
    while ($row = $result->fetch_assoc()) {
        $admins[] = $row;
    }
    
    $conn->close();
    return $admins;
}

// 创建新管理员
function create_admin($username, $password) {
    $conn = get_db_connection();
    
    // 检查用户名是否已存在
    $stmt = $conn->prepare("SELECT id FROM admin_users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $conn->close();
        return false; // 用户名已存在
    }
    
    $hashed_password = password_hash($password, PASSWORD_BCRYPT);
    
    $stmt = $conn->prepare("INSERT INTO admin_users (username, password) VALUES (?, ?)");
    $stmt->bind_param("ss", $username, $hashed_password);
    $success = $stmt->execute();
    
    $conn->close();
    return $success;
}

// 删除管理员
function delete_admin($admin_id) {
    $conn = get_db_connection();
    
    $stmt = $conn->prepare("DELETE FROM admin_users WHERE id = ?");
    $stmt->bind_param("i", $admin_id);
    $success = $stmt->execute();
    
    $conn->close();
    return $success;
}

// 读取用户数据（从文件系统）
function get_user_data($admin_username, $user, $file) {
    $user_path = "userss/" . $admin_username . "/userss/" . $user . "/" . $file;
    
    if (file_exists($user_path)) {
        return trim(file_get_contents($user_path));
    }
    
    return null;
}

// 设置用户数据（写入文件系统）
function set_user_data($admin_username, $user, $file, $data) {
    $user_path = "userss/" . $admin_username . "/userss/" . $user . "/" . $file;
    $dir = dirname($user_path);
    
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
    
    return file_put_contents($user_path, $data);
}

?>
