<?php
/**
 * AppDoc 综合管理仪表盘 (修复版)
 * 功能：管理员登录、卡密管理、用户管理、统计概览、对接 admin 目录
 */

session_start();

// 数据库配置
// 建议：实际部署时请确保数据库已创建且包含 vip_km 表
$mysql = [
    "host" => "localhost",
    "username" => "appdoc",
    "password" => "123456",
    "dbname" => "appdoc"
];

// 管理密码配置
$admin_password = "zxc123.0";

// 连接数据库
function connectDB() {
    global $mysql;
    // 使用 @ 抑制错误输出，避免页面直接显示连接错误导致打不开
    $link = @new mysqli($mysql["host"], $mysql["username"], $mysql["password"], $mysql["dbname"]);
    if($link->connect_error) {
        // 如果数据库连接失败，返回 null 而不是直接 die，以便在页面上显示友好提示
        return null;
    }
    mysqli_set_charset($link, 'utf8');
    return $link;
}

// 时间转换函数
function formatSeconds($seconds) {
    if (!is_numeric($seconds)) return $seconds;
    $seconds = (int)$seconds;
    if ($seconds < 1) return "0秒";
    
    $tokens = [
        31536000 => '年',
        2592000 => '月',
        86400 => '天',
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

    $action = isset($_GET['action']) ? $_GET['action'] : $_POST['action'];

    // 登录操作不需要预先检查登录状态
    if ($action !== 'login') {
        if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
            if (!isset($_GET['pass']) || $_GET['pass'] !== $admin_password) {
                die(json_encode(['code' => 0, 'msg' => '未登录或会话已过期']));
            }
        }
    }

    $link = connectDB();
    if (!$link && $action !== 'login' && $action !== 'logout') {
        die(json_encode(['code' => 0, 'msg' => '数据库连接失败，请检查配置']));
    }

    switch ($action) {
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
            
            if ($num <= 0 || empty($time) || empty($type)) {
                die(json_encode(['code' => 0, 'msg' => '参数不完整']));
            }

            $kms = [];
            for ($i = 0; $i < $num; $i++) {
                $km = strtoupper(substr(md5(uniqid() . mt_rand()), 0, 16));
                $kms[] = $km;
                
                $sql = "INSERT INTO vip_km (`km`, `type`, `time`) VALUES ('$km', '$type', '$time')";
                $link->query($sql);
            }
            die(json_encode(['code' => 1, 'msg' => '生成成功', 'data' => $kms]));
            break;

        case 'km_list':
            $result = $link->query("SELECT * FROM vip_km ORDER BY id DESC LIMIT 100");
            $data = [];
            if ($result) {
                while($row = $result->fetch_assoc()) {
                    $row['display_time'] = $row['type'] == 'vip' ? formatSeconds($row['time']) : $row['time'];
                    $row['type_name'] = $row['type'] == 'vip' ? '会员时长' : '余额点数';
                    $data[] = $row;
                }
            }
            die(json_encode(['code' => 1, 'data' => $data]));
            break;

        case 'get_stats':
            $stats = ['total' => 0, 'vip' => 0, 'money' => 0, 'users' => 0];
            $r1 = $link->query("SELECT COUNT(*) as c FROM vip_km");
            if($r1) $stats['total'] = $r1->fetch_assoc()['c'];
            $r2 = $link->query("SELECT COUNT(*) as c FROM vip_km WHERE type='vip'");
            if($r2) $stats['vip'] = $r2->fetch_assoc()['c'];
            $r3 = $link->query("SELECT COUNT(*) as c FROM vip_km WHERE type='money'");
            if($r3) $stats['money'] = $r3->fetch_assoc()['c'];
            
            // 统计 userss 目录下的文件夹数量作为用户数
            $user_dir = 'userss/';
            if (is_dir($user_dir)) {
                $files = scandir($user_dir);
                $stats['users'] = count(array_filter($files, function($f) use ($user_dir) {
                    return $f !== '.' && $f !== '..' && is_dir($user_dir . $f);
                }));
            }
            die(json_encode(['code' => 1, 'data' => $stats]));
            break;

        case 'get_users':
            $user_dir = 'userss/';
            $users = [];
            if (is_dir($user_dir)) {
                $files = scandir($user_dir);
                foreach ($files as $file) {
                    if ($file !== '.' && $file !== '..' && is_dir($user_dir . $file)) {
                        $users[] = [
                            'username' => $file,
                            'reg_time' => date("Y-m-d H:i", filemtime($user_dir . $file))
                        ];
                    }
                }
            }
            die(json_encode(['code' => 1, 'data' => $users]));
            break;
    }
    if($link) $link->close();
    exit;
}

// 页面渲染逻辑
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    if (isset($_GET['pass']) && $_GET['pass'] === $admin_password) {
        $_SESSION['admin_logged_in'] = true;
    } else {
        showLoginPage();
        exit;
    }
}

showDashboard();

function showLoginPage() {
    ?>
    <!DOCTYPE html>
    <html lang="zh-CN">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>管理员登录 - AppDoc</title>
        <link href="https://cdn.bootcdn.net/ajax/libs/twitter-bootstrap/5.1.3/css/bootstrap.min.css" rel="stylesheet">
        <style>
            body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); height: 100vh; display: flex; align-items: center; justify-content: center; }
            .login-card { background: white; padding: 2rem; border-radius: 1rem; box-shadow: 0 10px 25px rgba(0,0,0,0.2); width: 100%; max-width: 400px; }
        </style>
    </head>
    <body>
        <div class="login-card">
            <h3 class="text-center mb-4">AppDoc 总管理后台</h3>
            <form id="loginForm">
                <div class="mb-3">
                    <label class="form-label">管理员密码</label>
                    <input type="password" name="password" class="form-control" required placeholder="请输入管理密码">
                </div>
                <button type="submit" class="btn btn-primary w-100 py-2">进入后台</button>
            </form>
        </div>
        <script src="https://cdn.bootcdn.net/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
        <script>
            $('#loginForm').submit(function(e) {
                e.preventDefault();
                $.post('?action=login', $(this).serialize(), function(res) {
                    if (res.code === 1) location.reload();
                    else alert(res.msg);
                });
            });
        </script>
    </body>
    </html>
    <?php
}

function showDashboard() {
    ?>
    <!DOCTYPE html>
    <html lang="zh-CN">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>AppDoc 综合管理后台</title>
        <link href="https://cdn.bootcdn.net/ajax/libs/twitter-bootstrap/5.1.3/css/bootstrap.min.css" rel="stylesheet">
        <link href="https://cdn.bootcdn.net/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
        <style>
            :root { --sidebar-width: 240px; }
            body { background-color: #f4f7f6; }
            .sidebar { width: var(--sidebar-width); position: fixed; top: 0; left: 0; height: 100vh; background: #2c3e50; color: white; transition: all 0.3s; z-index: 1000; }
            .sidebar .nav-link { color: #bdc3c7; padding: 12px 20px; border-left: 4px solid transparent; }
            .sidebar .nav-link:hover, .sidebar .nav-link.active { color: white; background: #34495e; border-left-color: #3498db; }
            .content { margin-left: var(--sidebar-width); padding: 20px; transition: all 0.3s; }
            .card { border: none; box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075); border-radius: 0.5rem; margin-bottom: 20px; }
            .stat-card { transition: transform 0.2s; }
            .stat-card:hover { transform: translateY(-5px); }
        </style>
    </head>
    <body>
        <div class="sidebar">
            <div class="p-4 text-center">
                <h4>AppDoc PRO</h4>
                <small class="text-muted">总管理后台</small>
            </div>
            <nav class="nav flex-column mt-2">
                <a class="nav-link active" href="javascript:void(0)" onclick="showPage('stats')"><i class="fas fa-chart-line me-2"></i> 运行统计</a>
                <a class="nav-link" href="javascript:void(0)" onclick="showPage('km')"><i class="fas fa-key me-2"></i> 卡密管理</a>
                <a class="nav-link" href="javascript:void(0)" onclick="showPage('users')"><i class="fas fa-users me-2"></i> 用户列表</a>
                <a class="nav-link" href="javascript:void(0)" onclick="showPage('admin-tools')"><i class="fas fa-tools me-2"></i> 管理工具</a>
                <hr class="mx-3 my-2">
                <a class="nav-link text-danger" href="?action=logout"><i class="fas fa-sign-out-alt me-2"></i> 退出登录</a>
            </nav>
        </div>

        <div class="content">
            <!-- 运行统计 -->
            <div id="page-stats" class="page-content">
                <h3 class="mb-4">运行统计</h3>
                <div class="row">
                    <div class="col-md-3">
                        <div class="card stat-card bg-primary text-white">
                            <div class="card-body">
                                <h6>总卡密数量</h6>
                                <h2 id="stat-total">-</h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card bg-success text-white">
                            <div class="card-body">
                                <h6>会员卡密</h6>
                                <h2 id="stat-vip">-</h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card bg-info text-white">
                            <div class="card-body">
                                <h6>余额卡密</h6>
                                <h2 id="stat-money">-</h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card bg-warning text-white">
                            <div class="card-body">
                                <h6>后台用户数</h6>
                                <h2 id="stat-users">-</h2>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 卡密管理 -->
            <div id="page-km" class="page-content" style="display:none;">
                <h3 class="mb-4">卡密管理</h3>
                <div class="card">
                    <div class="card-header bg-white">批量生成卡密</div>
                    <div class="card-body">
                        <form id="genKmForm" class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">类型</label>
                                <select name="type" class="form-select">
                                    <option value="vip">会员时长(秒)</option>
                                    <option value="money">余额点数</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">数值</label>
                                <input type="text" name="time" class="form-control" placeholder="如: 86400 或 100">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">数量</label>
                                <input type="number" name="num" class="form-control" value="10">
                            </div>
                            <div class="col-md-3 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary w-100">立即生成</button>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="card mt-4">
                    <div class="card-header bg-white">最近生成的卡密 (100条)</div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>卡密内容</th>
                                    <th>类型</th>
                                    <th>数值</th>
                                    <th>状态</th>
                                </tr>
                            </thead>
                            <tbody id="kmList"></tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- 用户列表 -->
            <div id="page-users" class="page-content" style="display:none;">
                <h3 class="mb-4">后台用户列表</h3>
                <div class="card">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>用户名</th>
                                    <th>最后活动/注册</th>
                                    <th>操作</th>
                                </tr>
                            </thead>
                            <tbody id="userList"></tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- 管理工具 (对接 admin 目录) -->
            <div id="page-admin-tools" class="page-content" style="display:none;">
                <h3 class="mb-4">快捷管理工具</h3>
                <div class="alert alert-info">以下工具直接对接 <code>admin/</code> 目录下的管理脚本。</div>
                <div class="row">
                    <?php
                    $tools = [
                        ['name' => '用户封禁', 'file' => 'adminseal.php', 'icon' => 'fa-user-slash', 'color' => 'danger'],
                        ['name' => 'VIP管理', 'file' => 'adminuservip.php', 'icon' => 'fa-crown', 'color' => 'warning'],
                        ['name' => '余额管理', 'file' => 'adminmoney.php', 'icon' => 'fa-wallet', 'color' => 'success'],
                        ['name' => '密码修改', 'file' => 'adminuserpass.php', 'icon' => 'fa-key', 'color' => 'info'],
                        ['name' => '论坛管理', 'file' => 'forum.php', 'icon' => 'fa-comments', 'color' => 'primary'],
                        ['name' => '反馈查看', 'file' => 'feedback.php', 'icon' => 'fa-envelope-open-text', 'color' => 'secondary']
                    ];
                    foreach ($tools as $t): ?>
                    <div class="col-md-4 mb-3">
                        <div class="card h-100">
                            <div class="card-body d-flex align-items-center">
                                <div class="rounded-circle bg-<?php echo $t['color']; ?> text-white p-3 me-3">
                                    <i class="fas <?php echo $t['icon']; ?> fa-fw"></i>
                                </div>
                                <div>
                                    <h5 class="mb-1"><?php echo $t['name']; ?></h5>
                                    <a href="admin/<?php echo $t['file']; ?>" target="_blank" class="btn btn-sm btn-link p-0">打开工具 <i class="fas fa-external-link-alt ms-1"></i></a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <script src="https://cdn.bootcdn.net/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
        <script>
            function showPage(id) {
                $('.page-content').hide();
                $('#page-' + id).fadeIn();
                $('.nav-link').removeClass('active');
                $(`a[onclick="showPage('${id}')"]`).addClass('active');
                if(id === 'stats') loadStats();
                if(id === 'km') loadKms();
                if(id === 'users') loadUsers();
            }

            function loadStats() {
                $.get('?action=get_stats', function(res) {
                    if(res.code === 1) {
                        $('#stat-total').text(res.data.total);
                        $('#stat-vip').text(res.data.vip);
                        $('#stat-money').text(res.data.money);
                        $('#stat-users').text(res.data.users);
                    }
                });
            }

            function loadKms() {
                $.get('?action=km_list', function(res) {
                    if(res.code === 1) {
                        let html = '';
                        res.data.forEach(item => {
                            html += `<tr>
                                <td><code>${item.km}</code></td>
                                <td>${item.type_name}</td>
                                <td>${item.display_time}</td>
                                <td><span class="badge bg-success">有效</span></td>
                            </tr>`;
                        });
                        $('#kmList').html(html || '<tr><td colspan="4" class="text-center">暂无数据</td></tr>');
                    }
                });
            }

            function loadUsers() {
                $.get('?action=get_users', function(res) {
                    if(res.code === 1) {
                        let html = '';
                        res.data.forEach(u => {
                            html += `<tr>
                                <td><strong>${u.username}</strong></td>
                                <td>${u.reg_time}</td>
                                <td>
                                    <a href="activeuserlist.php?admin=${u.username}" target="_blank" class="btn btn-sm btn-outline-primary">管理用户</a>
                                </td>
                            </tr>`;
                        });
                        $('#userList').html(html || '<tr><td colspan="3" class="text-center">暂无用户数据</td></tr>');
                    }
                });
            }

            $('#genKmForm').submit(function(e) {
                e.preventDefault();
                $.post('?action=generate_km', $(this).serialize(), function(res) {
                    alert(res.msg);
                    if(res.code === 1) loadKms();
                });
            });

            $(document).ready(function() {
                loadStats();
            });
        </script>
    </body>
    </html>
    <?php
}
?>
