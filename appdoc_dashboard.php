<?php
/**
 * AppDoc 综合管理仪表盘
 * 功能：管理员登录、卡密管理、用户管理、统计概览
 */

session_start();

// 数据库配置
$mysql = [
    "host" => "localhost",
    "username" => "appdoc",
    "password" => "123456", 
    "dbname" => "appdoc",
];

// 管理密码配置（请务必修改）
$admin_password = "zxc123.0";

// 连接数据库
function connectDB() {
    global $mysql;
    $link = new mysqli($mysql["host"], $mysql["username"], $mysql["password"], $mysql["dbname"]);
    if($link->connect_error) {
        die("数据库连接失败: " . $link->connect_error);
    }
    mysqli_set_charset($link, 'utf8');
    return $link;
}

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

// 处理 AJAX 请求
if (isset($_GET['action']) || isset($_POST['action'])) {
    header('Content-Type: application/json; charset=utf-8');
    
    // 检查登录状态
    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        // 检查URL密码参数（兼容旧方式）
        if (!isset($_GET['pass']) || $_GET['pass'] !== $admin_password) {
            die(json_encode(['code' => 0, 'msg' => '未登录或会话已过期']));
        }
    }
    
    $link = connectDB();
    $action = isset($_GET['action']) ? $_GET['action'] : $_POST['action'];
    
    switch($action) {
        case 'login':
            $password = isset($_POST['password']) ? $_POST['password'] : '';
            if ($password === $admin_password) {
                $_SESSION['admin_logged_in'] = true;
                die(json_encode(['code' => 1, 'msg' => '登录成功']));
            } else {
                die(json_encode(['code' => 0, 'msg' => '密码错误']));
            }
            break;
            
        case 'logout':
            session_destroy();
            die(json_encode(['code' => 1, 'msg' => '已退出登录']));
            break;
            
        case 'generate_km':
            $num = isset($_POST['num']) ? intval($_POST['num']) : 0;
            $time = isset($_POST['time']) ? $_POST['time'] : '';
            $type = isset($_POST['type']) ? $_POST['type'] : '';
            
            if ($num <= 0 || empty($time) || empty($type) || !in_array($type, ['vip', 'money'])) {
                die(json_encode(['code' => 0, 'msg' => '参数错误']));
            }
            
            $kms = [];
            for ($i = 0; $i < $num; $i++) {
                $km = strtoupper(substr(md5(uniqid() . mt_rand()), 0, 16));
                $kms[] = $km;
                
                $sql = "INSERT INTO `vip_km` (`km`, `type`, `time`) VALUES ('$km', '$type', '$time')";
                $link->query($sql);
            }
            
            die(json_encode(['code' => 1, 'msg' => '生成成功', 'data' => $kms]));
            break;
            
        case 'km_list':
            $type = isset($_GET['type']) ? $_GET['type'] : 'all';
            
            if ($type == 'all') {
                $sql = "SELECT * FROM `vip_km` ORDER BY `type` ASC";
            } else {
                $type = $link->real_escape_string($type);
                $sql = "SELECT * FROM `vip_km` WHERE `type` = '$type' ORDER BY `time` ASC";
            }
            
            $result = $link->query($sql);
            $data = [];
            
            if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    $row['display_time'] = ($row['type'] == 'vip') ? formatSeconds($row['time']) : $row['time'] . ' (个/元)';
                    $row['type_name'] = ($row['type'] == 'vip') ? '会员时长' : '余额/文档';
                    $data[] = $row;
                }
            }
            
            die(json_encode(['code' => 1, 'data' => $data]));
            break;
            
        case 'delete_km':
            $kms = isset($_POST['kms']) ? $_POST['kms'] : [];
            
            if (empty($kms)) {
                die(json_encode(['code' => 0, 'msg' => '请选择要删除的卡密']));
            }
            
            $deleted_count = 0;
            foreach ($kms as $km) {
                $km = $link->real_escape_string($km);
                $sql = "DELETE FROM `vip_km` WHERE `km` = '$km'";
                if ($link->query($sql)) {
                    $deleted_count++;
                }
            }
            
            die(json_encode(['code' => 1, 'msg' => "成功删除 $deleted_count 张卡密"]));
            break;
            
        case 'get_stats':
            // 获取卡密统计
            $vip_count = 0;
            $money_count = 0;
            $total_count = 0;
            
            $result = $link->query("SELECT COUNT(*) as count FROM `vip_km` WHERE `type` = 'vip'");
            if ($result) $vip_count = $result->fetch_assoc()['count'];
            
            $result = $link->query("SELECT COUNT(*) as count FROM `vip_km` WHERE `type` = 'money'");
            if ($result) $money_count = $result->fetch_assoc()['count'];
            
            $result = $link->query("SELECT COUNT(*) as count FROM `vip_km`");
            if ($result) $total_count = $result->fetch_assoc()['count'];
            
            die(json_encode([
                'code' => 1,
                'data' => [
                    'vip_count' => $vip_count,
                    'money_count' => $money_count,
                    'total_count' => $total_count
                ]
            ]));
            break;
    }
    
    $link->close();
    exit;
}

// 如果是直接访问，显示登录页面或主界面
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    // 检查URL密码参数（兼容旧方式）
    if (isset($_GET['pass']) && $_GET['pass'] === $admin_password) {
        $_SESSION['admin_logged_in'] = true;
    } else {
        showLoginPage();
        exit;
    }
}

// 显示主界面
showDashboard();

function showLoginPage() {
    echo '<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AppDoc 管理登录</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-container {
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 400px;
        }
        .login-title {
            text-align: center;
            margin-bottom: 30px;
            color: #333;
            font-size: 1.5em;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #666;
        }
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 1em;
        }
        .login-btn {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1em;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        .notification {
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
            text-align: center;
            display: none;
        }
        .notification.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h1 class="login-title">🔐 AppDoc 管理登录</h1>
        <div class="notification error" id="error-msg"></div>
        <form id="login-form">
            <div class="form-group">
                <label for="password">管理员密码</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" class="login-btn">登录</button>
        </form>
    </div>

    <script>
        document.getElementById("login-form").addEventListener("submit", function(e) {
            e.preventDefault();
            const password = document.getElementById("password").value;
            const errorMsg = document.getElementById("error-msg");
            
            fetch("?", {
                method: "POST",
                headers: {"Content-Type": "application/x-www-form-urlencoded"},
                body: "action=login&password=" + encodeURIComponent(password)
            })
            .then(res => res.json())
            .then(data => {
                if (data.code === 1) {
                    window.location.reload();
                } else {
                    errorMsg.textContent = data.msg;
                    errorMsg.style.display = "block";
                }
            })
            .catch(err => {
                errorMsg.textContent = "登录失败，请重试";
                errorMsg.style.display = "block";
            });
        });
    </script>
</body>
</html>';
}

function showDashboard() {
    echo '<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AppDoc 综合管理仪表盘</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", "Helvetica Neue", Arial, sans-serif; background: #f5f7fa; color: #333; }
        .wrapper { display: flex; min-height: 100vh; }
        .sidebar { width: 250px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px 20px; box-shadow: 2px 0 10px rgba(0,0,0,0.1); }
        .sidebar-title { font-size: 1.5em; font-weight: bold; margin-bottom: 30px; text-align: center; text-shadow: 1px 1px 2px rgba(0,0,0,0.2); }
        .sidebar-menu { list-style: none; }
        .sidebar-menu li { margin-bottom: 15px; }
        .sidebar-menu a { display: block; padding: 12px 15px; color: white; text-decoration: none; border-radius: 8px; transition: all 0.3s ease; cursor: pointer; font-size: 0.95em; }
        .sidebar-menu a:hover, .sidebar-menu a.active { background: rgba(255,255,255,0.2); transform: translateX(5px); }
        .main-content { flex: 1; padding: 30px; overflow-y: auto; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; background: white; padding: 20px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
        .header h1 { font-size: 1.8em; color: #333; }
        .header-actions { display: flex; gap: 10px; }
        .btn { padding: 10px 20px; border: none; border-radius: 8px; cursor: pointer; font-size: 0.95em; transition: all 0.3s ease; font-weight: 500; }
        .btn-primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4); }
        .btn-danger { background: #f5576c; color: white; }
        .btn-danger:hover { background: #e63946; transform: translateY(-2px); box-shadow: 0 5px 15px rgba(245, 87, 108, 0.4); }
        .btn-danger:disabled { background: #ccc; cursor: not-allowed; transform: none; }
        .stats-container { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: white; padding: 20px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); text-align: center; cursor: pointer; transition: all 0.3s ease; }
        .stat-card:hover { transform: translateY(-5px); box-shadow: 0 8px 20px rgba(0,0,0,0.1); }
        .stat-card.active { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
        .stat-icon { font-size: 2em; margin-bottom: 10px; }
        .stat-number { font-size: 2em; font-weight: bold; margin-bottom: 5px; }
        .stat-label { font-size: 0.9em; opacity: 0.8; }
        .list-container { background: white; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); overflow: hidden; }
        .list-header { padding: 20px; background: #f8f9fa; border-bottom: 1px solid #e9ecef; display: flex; justify-content: space-between; align-items: center; }
        .list-header-left { display: flex; align-items: center; gap: 15px; }
        .checkbox-all { width: 20px; height: 20px; cursor: pointer; }
        .list-header h2 { font-size: 1.1em; color: #333; }
        .list-body { max-height: 600px; overflow-y: auto; }
        .km-row { padding: 15px 20px; border-bottom: 1px solid #e9ecef; display: flex; align-items: center; gap: 15px; transition: all 0.2s ease; }
        .km-row:hover { background: #f8f9fa; }
        .km-row.selected { background: #e7f3ff; }
        .km-checkbox { width: 20px; height: 20px; cursor: pointer; }
        .km-code { flex: 1; font-family: "Courier New", monospace; background: #f5f7fa; padding: 8px 12px; border-radius: 6px; word-break: break-all; cursor: pointer; transition: all 0.2s ease; user-select: all; }
        .km-code:hover { background: #e9ecef; box-shadow: 0 2px 6px rgba(0,0,0,0.1); }
        .km-code.copied { background: #d4edda; color: #155724; }
        .km-type { min-width: 80px; text-align: center; font-weight: 500; }
        .km-type.vip { color: #667eea; background: #f0f4ff; padding: 5px 10px; border-radius: 6px; }
        .km-type.money { color: #f5576c; background: #ffe9ed; padding: 5px 10px; border-radius: 6px; }
        .km-time { min-width: 120px; text-align: center; color: #666; }
        .km-actions { display: flex; gap: 8px; }
        .btn-small { padding: 6px 12px; font-size: 0.85em; border: none; border-radius: 6px; cursor: pointer; transition: all 0.2s ease; }
        .btn-copy { background: #667eea; color: white; }
        .btn-copy:hover { background: #764ba2; }
        .btn-delete { background: #f5576c; color: white; }
        .btn-delete:hover { background: #e63946; }
        .empty-state { text-align: center; padding: 60px 20px; color: #999; }
        .empty-icon { font-size: 3em; margin-bottom: 15px; }
        .loading { text-align: center; padding: 40px; color: #666; }
        .spinner { border: 3px solid #f3f3f3; border-top: 3px solid #667eea; border-radius: 50%; width: 30px; height: 30px; animation: spin 1s linear infinite; margin: 0 auto 15px; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        .notification { position: fixed; top: 20px; right: 20px; padding: 15px 20px; border-radius: 8px; color: white; font-weight: 500; animation: slideIn 0.3s ease; z-index: 1000; }
        .notification.success { background: #28a745; }
        .notification.error { background: #dc3545; }
        @keyframes slideIn { from { transform: translateX(400px); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 2000; }
        .modal-content { background: white; margin: 5% auto; padding: 30px; border-radius: 12px; width: 90%; max-width: 500px; }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .modal-title { font-size: 1.3em; font-weight: bold; }
        .modal-close { background: none; border: none; font-size: 1.5em; cursor: pointer; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 500; }
        .form-group input, .form-group select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; font-size: 1em; }
        .modal-actions { display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px; }
        .tab-container { margin-bottom: 20px; }
        .tab-buttons { display: flex; background: #f8f9fa; border-radius: 8px; padding: 5px; }
        .tab-button { flex: 1; padding: 10px; text-align: center; border: none; background: none; cursor: pointer; border-radius: 6px; transition: all 0.3s ease; }
        .tab-button.active { background: white; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        .filter-buttons { display: flex; gap: 10px; margin-bottom: 20px; }
        .filter-btn { padding: 8px 16px; border: 1px solid #ddd; background: white; border-radius: 6px; cursor: pointer; transition: all 0.3s ease; }
        .filter-btn.active { background: #667eea; color: white; border-color: #667eea; }
        @media (max-width: 768px) { 
            .wrapper { flex-direction: column; } 
            .sidebar { width: 100%; padding: 20px; } 
            .sidebar-menu { display: flex; flex-wrap: wrap; gap: 10px; } 
            .sidebar-menu li { margin-bottom: 0; flex: 1; min-width: 100px; } 
            .sidebar-menu a { padding: 8px 10px; text-align: center; font-size: 0.85em; } 
            .main-content { padding: 15px; } 
            .header { flex-direction: column; gap: 15px; text-align: center; } 
            .header h1 { font-size: 1.3em; } 
            .stats-container { grid-template-columns: repeat(2, 1fr); } 
            .km-row { flex-wrap: wrap; gap: 10px; } 
            .km-code { flex-basis: 100%; } 
            .km-type, .km-time { min-width: auto; flex: 1; } 
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="sidebar">
            <div class="sidebar-title">🎛️ AppDoc 管理</div>
            <ul class="sidebar-menu">
                <li><a class="menu-item active" data-page="dashboard">📊 仪表盘</a></li>
                <li><a class="menu-item" data-page="km">🎫 卡密管理</a></li>
                <li><a class="menu-item" onclick="logout()">🚪 退出登录</a></li>
            </ul>
        </div>
        
        <div class="main-content">
            <div class="header">
                <h1 id="page-title">仪表盘概览</h1>
                <div class="header-actions">
                    <button class="btn btn-primary" onclick="showGenerateModal()">生成卡密</button>
                </div>
            </div>
            
            <div id="dashboard-page" class="page-content active">
                <div class="stats-container" id="stats-container"></div>
                <div class="list-container">
                    <div class="list-header">
                        <h2>最近卡密</h2>
                    </div>
                    <div class="list-body" id="recent-km-list"></div>
                </div>
            </div>
            
            <div id="km-page" class="page-content">
                <div class="filter-buttons">
                    <button class="filter-btn active" data-type="all">全部卡密</button>
                    <button class="filter-btn" data-type="vip">会员卡密</button>
                    <button class="filter-btn" data-type="money">余额卡密</button>
                </div>
                <div class="list-container">
                    <div class="list-header">
                        <div class="list-header-left">
                            <input type="checkbox" class="checkbox-all" id="checkbox-all" onchange="toggleAll(this)">
                            <h2>卡密列表</h2>
                        </div>
                        <div class="header-actions">
                            <button class="btn btn-danger" id="batch-delete-btn" onclick="batchDelete()" disabled>🗑️ 批量删除</button>
                        </div>
                    </div>
                    <div class="list-body" id="km-list">
                        <div class="loading">
                            <div class="spinner"></div>
                            <p>加载中...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- 生成卡密模态框 -->
    <div class="modal" id="generate-modal">
        <div class="modal-content">
            <div class="modal-header">
                <div class="modal-title">生成卡密</div>
                <button class="modal-close" onclick="closeModal(\'generate-modal\')">&times;</button>
            </div>
            <div class="form-group">
                <label>卡密类型</label>
                <select id="km-type">
                    <option value="vip">VIP会员卡密</option>
                    <option value="money">余额卡密</option>
                </select>
            </div>
            <div class="form-group">
                <label>数量</label>
                <input type="number" id="km-num" placeholder="输入生成数量" min="1" value="1">
            </div>
            <div class="form-group">
                <label id="time-label">时长（秒）</label>
                <input type="number" id="km-time" placeholder="输入时长（秒）" min="1">
            </div>
            <div class="modal-actions">
                <button class="btn btn-primary" onclick="generateKm()">生成</button>
                <button class="btn" onclick="closeModal(\'generate-modal\')">取消</button>
            </div>
        </div>
    </div>
    
    <script>
        let currentPage = "dashboard";
        let currentKmType = "all";
        let selectedKms = [];
        
        // 页面初始化
        document.addEventListener("DOMContentLoaded", function() {
            loadDashboard();
            loadKmList("all");
            setupEventListeners();
        });
        
        function setupEventListeners() {
            // 菜单切换
            document.querySelectorAll(".menu-item[data-page]").forEach(item => {
                item.addEventListener("click", function() {
                    document.querySelectorAll(".menu-item").forEach(i => i.classList.remove("active"));
                    this.classList.add("active");
                    
                    document.querySelectorAll(".page-content").forEach(page => page.classList.remove("active"));
                    document.getElementById(this.dataset.page + "-page").classList.add("active");
                    
                    currentPage = this.dataset.page;
                    document.getElementById("page-title").textContent = this.textContent.trim();
                    
                    if (currentPage === "km") {
                        loadKmList(currentKmType);
                    } else if (currentPage === "dashboard") {
                        loadDashboard();
                    }
                });
            });
            
            // 筛选按钮
            document.querySelectorAll(".filter-btn").forEach(btn => {
                btn.addEventListener("click", function() {
                    const type = this.dataset.type;
                    document.querySelectorAll(".filter-btn").forEach(b => b.classList.remove("active"));
                    this.classList.add("active");
                    currentKmType = type;
                    loadKmList(type);
                });
            });
            
            // 卡密类型切换
            document.getElementById("km-type").addEventListener("change", function() {
                document.getElementById("time-label").textContent = this.value === "vip" ? "时长（秒）" : "数量";
                document.getElementById("km-time").placeholder = this.value === "vip" ? "输入时长（秒）" : "输入数量";
            });
        }
        
        function loadDashboard() {
            // 加载统计信息
            fetch("?action=get_stats")
                .then(res => res.json())
                .then(res => {
                    if (res.code === 1) {
                        const stats = res.data;
                        document.getElementById("stats-container").innerHTML = `
                            <div class="stat-card" onclick="switchPage(\'km\')">
                                <div class="stat-icon">📊</div>
                                <div class="stat-number">${stats.total_count}</div>
                                <div class="stat-label">全部卡密</div>
                            </div>
                            <div class="stat-card" onclick="switchPage(\'km\')">
                                <div class="stat-icon">👑</div>
                                <div class="stat-number">${stats.vip_count}</div>
                                <div class="stat-label">会员卡密</div>
                            </div>
                            <div class="stat-card" onclick="switchPage(\'km\')">
                                <div class="stat-icon">📄</div>
                                <div class="stat-number">${stats.money_count}</div>
                                <div class="stat-label">余额卡密</div>
                            </div>
                        `;
                    }
                });
            
            // 加载最近卡密
            fetch("?action=km_list&type=all")
                .then(res => res.json())
                .then(res => {
                    if (res.code === 1) {
                        const kms = res.data.slice(0, 10);
                        const listBody = document.getElementById("recent-km-list");
                        
                        if (kms.length === 0) {
                            listBody.innerHTML = '<div class="empty-state"><div class="empty-icon">📭</div><p>暂无卡密</p></div>';
                            return;
                        }
                        
                        let html = "";
                        kms.forEach(km => {
                            const typeClass = km.type === "vip" ? "vip" : "money";
                            
                            html += `
                                <div class="km-row">
                                    <div class="km-code" onclick="copySingleKm(this, \'${km.km}\')" title="点击复制">${km.km}</div>
                                    <div class="km-type ${typeClass}">${km.type_name}</div>
                                    <div class="km-time">${km.display_time}</div>
                                </div>
                            `;
                        });
                        
                        listBody.innerHTML = html;
                    }
                });
        }
        
        function loadKmList(type) {
            const listBody = document.getElementById("km-list");
            listBody.innerHTML = '<div class="loading"><div class="spinner"></div><p>加载中...</p></div>';
            currentKmType = type;
            
            fetch(`?action=km_list&type=${type}`)
                .then(res => res.json())
                .then(res => {
                    if (res.code === 1) {
                        renderKmList(res.data);
                    } else {
                        listBody.innerHTML = '<div class="empty-state"><div class="empty-icon">❌</div><p>' + res.msg + '</p></div>';
                    }
                })
                .catch(err => {
                    listBody.innerHTML = '<div class="empty-state"><div class="empty-icon">⚠️</div><p>加载失败</p></div>';
                });
        }
        
        function renderKmList(data) {
            const listBody = document.getElementById("km-list");
            
            if (data.length === 0) {
                listBody.innerHTML = '<div class="empty-state"><div class="empty-icon">📭</div><p>暂无卡密</p></div>';
                return;
            }
            
            let html = "";
            data.forEach(km => {
                const typeClass = km.type === "vip" ? "vip" : "money";
                
                html += `
                    <div class="km-row" data-km="${km.km}">
                        <input type="checkbox" class="km-checkbox" onchange="updateSelection()">
                        <div class="km-code" onclick="copySingleKm(this, \'${km.km}\')" title="点击复制">${km.km}</div>
                        <div class="km-type ${typeClass}">${km.type_name}</div>
                        <div class="km-time">${km.display_time}</div>
                        <div class="km-actions">
                            <button class="btn-small btn-delete" onclick="deleteSingleKm(\'${km.km}\')">删除</button>
                        </div>
                    </div>
                `;
            });
            
            listBody.innerHTML = html;
            document.getElementById("checkbox-all").checked = false;
            selectedKms = [];
            updateBatchDeleteBtn();
        }
        
        function copySingleKm(element, km) {
            navigator.clipboard.writeText(km).then(() => {
                showNotification("已复制到剪贴板", "success");
                if (element.classList && element.classList.contains("km-code")) {
                    element.classList.add("copied");
                    setTimeout(() => element.classList.remove("copied"), 1500);
                }
            });
        }
        
        function deleteSingleKm(km) {
            if (confirm("确定要删除这张卡密吗？")) {
                performDelete([km]);
            }
        }
        
        function toggleAll(checkbox) {
            document.querySelectorAll(".km-checkbox").forEach(cb => {
                cb.checked = checkbox.checked;
            });
            updateSelection();
        }
        
        function updateSelection() {
            selectedKms = [];
            document.querySelectorAll(".km-checkbox").forEach(cb => {
                const row = cb.closest(".km-row");
                if (cb.checked) {
                    const km = row.dataset.km;
                    selectedKms.push(km);
                    row.classList.add("selected");
                } else {
                    row.classList.remove("selected");
                }
            });
            
            const allCheckboxes = document.querySelectorAll(".km-checkbox");
            const allChecked = allCheckboxes.length > 0 && selectedKms.length === allCheckboxes.length;
            document.getElementById("checkbox-all").checked = allChecked;
            updateBatchDeleteBtn();
        }
        
        function updateBatchDeleteBtn() {
            const btn = document.getElementById("batch-delete-btn");
            btn.disabled = selectedKms.length === 0;
            btn.textContent = `🗑️ 批量删除 (${selectedKms.length})`;
        }
        
        function batchDelete() {
            if (selectedKms.length === 0) {
                showNotification("请先选择要删除的卡密", "error");
                return;
            }
            
            if (confirm(`确定要删除 ${selectedKms.length} 张卡密吗？此操作不可撤销！`)) {
                performDelete(selectedKms);
            }
        }
        
        function performDelete(kms) {
            const formData = new FormData();
            formData.append("action", "delete_km");
            kms.forEach(km => formData.append("kms[]", km));
            
            fetch("?", { method: "POST", body: formData })
                .then(res => res.json())
                .then(res => {
                    if (res.code === 1) {
                        showNotification(res.msg, "success");
                        loadKmList(currentKmType);
                    } else {
                        showNotification(res.msg, "error");
                    }
                })
                .catch(err => showNotification("删除失败", "error"));
        }
        
        function showGenerateModal() {
            document.getElementById("generate-modal").style.display = "block";
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = "none";
        }
        
        function generateKm() {
            const type = document.getElementById("km-type").value;
            const num = parseInt(document.getElementById("km-num").value);
            const time = document.getElementById("km-time").value;
            
            if (num <= 0 || !time) {
                showNotification("请填写完整信息", "error");
                return;
            }
            
            const formData = new FormData();
            formData.append("action", "generate_km");
            formData.append("type", type);
            formData.append("num", num);
            formData.append("time", time);
            
            fetch("?", { method: "POST", body: formData })
                .then(res => res.json())
                .then(res => {
                    if (res.code === 1) {
                        showNotification(res.msg, "success");
                        closeModal("generate-modal");
                        if (currentPage === "km") {
                            loadKmList(currentKmType);
                        } else {
                            loadDashboard();
                        }
                        
                        // 显示生成的卡密
                        alert("生成的卡密：\\n" + res.data.join("\\n"));
                    } else {
                        showNotification(res.msg, "error");
                    }
                })
                .catch(err => showNotification("生成失败", "error"));
        }
        
        function switchPage(page) {
            document.querySelector(`[data-page="${page}"]`).click();
        }
        
        function logout() {
            if (confirm("确定要退出登录吗？")) {
                fetch("?action=logout", { method: "POST" })
                    .then(res => res.json())
                    .then(res => {
                        window.location.reload();
                    });
            }
        }
        
        function showNotification(msg, type) {
            const notification = document.createElement("div");
            notification.className = `notification ${type}`;
            notification.textContent = msg;
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.style.opacity = "0";
                notification.style.transition = "opacity 0.3s ease";
                setTimeout(() => notification.remove(), 300);
            }, 2000);
        }
    </script>
</body>
</html>';
}
?>
