<?php
session_start();
// 数据库配置
$mysql = ["host" => "localhost", "username" => "appdoc", "password" => "123456", "dbname" => "appdoc"];
// 管理密码配置
$admin_password = "zxc123.0";

// 连接数据库
function connectDB() {
    global $mysql;
    $link = @new mysqli($mysql["host"], $mysql["username"], $mysql["password"], $mysql["dbname"]);
    if($link->connect_error) {
        return null;
    }
    mysqli_set_charset($link, 'utf8');
    return $link;
}

// 时间转换函数 - 优化版
function formatSeconds($seconds) {
    if (!is_numeric($seconds)) return $seconds;
    $seconds = (int)$seconds;
    if ($seconds < 1) return "0秒";
    
    // 智能时间转换逻辑
    $tokens = [
        31536000 => ['unit' => '年', 'next' => 2592000],   // 1年
        2592000 => ['unit' => '月', 'next' => 604800],    // 30天
        604800 => ['unit' => '周', 'next' => 86400],       // 7天
        86400 => ['unit' => '天', 'next' => 3600],        // 24小时
        3600 => ['unit' => '小时', 'next' => 60],         // 60分钟
        60 => ['unit' => '分钟', 'next' => 1],           // 60秒
        1 => ['unit' => '秒', 'next' => 0]               // 1秒
    ];
    
    $result = [];
    $remaining = $seconds;
    
    foreach ($tokens as $unit => $info) {
        if ($remaining < $unit) continue;
        
        $numberOfUnits = floor($remaining / $unit);
        
        // 只显示最大的单位，除非是秒级需要显示具体数字
        if (count($result) == 0 && $unit == 1) {
            // 如果只有秒，显示具体数值
            $result[] = $numberOfUnits . $info['unit'];
        } elseif (count($result) < 2) {
            // 最多显示两个单位，如：1年3个月
            $result[] = $numberOfUnits . $info['unit'];
            $remaining -= $numberOfUnits * $unit;
            
            // 如果显示单位数已够2个，跳出循环
            if (count($result) >= 2) break;
        } else {
            break;
        }
    }
    
    return empty($result) ? "0秒" : implode('', $result);
}

// 检测设备类型
function detectDevice($user_agent) {
    if (strpos($user_agent, 'Android') !== false) {
        return '安卓';
    } elseif (strpos($user_agent, 'iPhone') !== false || strpos($user_agent, 'iPad') !== false) {
        return 'iOS';
    } elseif (strpos($user_agent, 'Windows') !== false) {
        return 'Windows';
    } elseif (strpos($user_agent, 'Mac') !== false) {
        return 'Mac';
    } else {
        return '其他';
    }
}

// 读取配置文件
function readConfig($file) {
    $config_file = 'admin/' . $file;
    if (file_exists($config_file)) {
        $content = file_get_contents($config_file);
        $file_ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        
        if ($file_ext === 'php') {
            // 从PHP文件中提取配置值
            // 匹配类似 $variable = "value"; 或 $variable = 123; 的格式
            if (preg_match('/\$\w+\s*=\s*[\'"](.*?)[\'"];/', $content, $matches)) {
                return $matches[1];
            } elseif (preg_match('/\$\w+\s*=\s*(\d+);/', $content, $matches)) {
                return $matches[1];
            } elseif (preg_match('/\?>(.*)/s', $content, $matches)) {
                // 提取PHP结束标签后的内容
                return trim($matches[1]);
            }
        }
        
        return trim($content);
    }
    return '';
}

// 写入配置文件
function writeConfig($file, $content) {
    $config_file = 'admin/' . $file;
    $dir = dirname($config_file);
    
    // 确保目录存在
    if (!is_dir($dir)) {
        if (!mkdir($dir, 0755, true)) {
            return false;
        }
    }
    
    // 根据文件扩展名处理内容
    $file_ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    
    if ($file_ext === 'php') {
        // PHP文件需要包裹在PHP标签中
        $content = "<?php\n" . $content . "\n";
    }
    
    return file_put_contents($config_file, $content) !== false;
}

// 获取用户VIP时长
function getUserVipTime($username) {
    $viptime_file = "userss/{$username}/admin/viptime";
    if (file_exists($viptime_file)) {
        $viptime = file_get_contents($viptime_file);
        return $viptime > 0 ? $viptime : 0;
    }
    return 0;
}

// 获取用户余额（点数）
function getUserMoney($username) {
    $money_file = "userss/{$username}/admin/data/filenum";
    if (file_exists($money_file)) {
        $money = file_get_contents($money_file);
        return is_numeric($money) ? floatval($money) : 0;
    }
    return 0;
}

// 设置用户VIP时长
function setUserVipTime($username, $viptime) {
    $viptime_file = "userss/{$username}/admin/viptime";
    $dir = dirname($viptime_file);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    return file_put_contents($viptime_file, $viptime) !== false;
}

// 设置用户余额
function setUserMoney($username, $money) {
    $money_file = "userss/{$username}/admin/data/filenum";
    $dir = dirname($money_file);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    return file_put_contents($money_file, $money) !== false;
}

// 处理 AJAX 请求
if (isset($_GET['action']) || isset($_POST['action'])) {
    header('Content-Type: application/json; charset=utf-8');
    $action = isset($_GET['action']) ? $_GET['action'] : $_POST['action'];

    if ($action !== 'login') {
        if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
            if (!isset($_GET['pass']) || $_GET['pass'] !== $admin_password) {
                die(json_encode(['code' => 0, 'msg' => '未登录或会话已过期']));
            }
        }
    }
    $link = connectDB();
    if (!$link && $action !== 'login' && $action !== 'logout' && $action !== 'get_config' && $action !== 'update_config') {
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
            $time_value = isset($_POST['time_value']) ? floatval($_POST['time_value']) : 0;
            $time_unit = isset($_POST['time_unit']) ? $_POST['time_unit'] : 'seconds';
            $type = isset($_POST['type']) ? $_POST['type'] : '';
            
            if ($num <= 0 || $time_value <= 0 || empty($type)) {
                die(json_encode(['code' => 0, 'msg' => '参数不完整']));
            }
            
            // 根据类型和单位转换时间
            if ($type == 'vip') {
                // 会员时长：转换为秒
                $unit_multipliers = [
                    'seconds' => 1,
                    'minutes' => 60,
                    'hours' => 3600,
                    'days' => 86400,
                    'weeks' => 604800,
                    'months' => 2592000,
                    'years' => 31536000
                ];
                $time = intval($time_value * $unit_multipliers[$time_unit]);
            } else {
                // 余额：直接使用数值，不转换单位
                $time = $time_value;
            }
            
            $kms = [];
            $stmt = $link->prepare("INSERT INTO vip_km (`km`, `type`, `time`) VALUES (?, ?, ?)");
            for ($i = 0; $i < $num; $i++) {
                $km = md5(uniqid() . mt_rand() . microtime(true));
                $kms[] = $km;
                $stmt->bind_param("sss", $km, $type, $time);
                $stmt->execute();
            }
            $stmt->close();
            die(json_encode(['code' => 1, 'msg' => '生成成功', 'data' => $kms]));
            break;
        case 'km_list':
            // 先检查数据库连接
            if (!$link) {
                die(json_encode(['code' => 0, 'msg' => '数据库连接失败']));
            }
            
            // 检查表是否存在
            $table_check = $link->query("SHOW TABLES LIKE 'vip_km'");
            if (!$table_check || $table_check->num_rows == 0) {
                die(json_encode(['code' => 0, 'msg' => 'vip_km表不存在']));
            }
            
            // 简化方案：使用 type 和 km 排序，避免主键检测
            $query = "SELECT * FROM vip_km ORDER BY `type` ASC, `km` DESC LIMIT 100";
            $result = $link->query($query);
            
            if (!$result) {
                die(json_encode(['code' => 0, 'msg' => '查询失败: ' . $link->error]));
            }
            
            $data = [];
            if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    // 简化时间转换逻辑，与示例保持一致
                    if ($row['type'] == 'vip') {
                        $type_name = '会员时长';
                        $display_time = formatSeconds($row['time']);
                    } else {
                        $type_name = '余额/文档';
                        $display_time = $row['time'] . ' (个/元)';
                    }
                    
                    $row['display_time'] = $display_time;
                    $row['type_name'] = $type_name;
                    $data[] = $row;
                }
            }
            
            die(json_encode(['code' => 1, 'data' => $data, 'count' => count($data)]));
            break;
        case 'delete_km':
            $km_id = isset($_POST['id']) ? trim($_POST['id']) : '';
            if (empty($km_id)) {
                die(json_encode(['code' => 0, 'msg' => '参数错误']));
            }
            
            // 使用 km 字段删除，因为 km 应该是主键或唯一标识
            $stmt = $link->prepare("DELETE FROM vip_km WHERE km = ?");
            if (!$stmt) {
                die(json_encode(['code' => 0, 'msg' => '预处理失败: ' . $link->error]));
            }
            $stmt->bind_param("s", $km_id);
            $stmt->execute();
            
            if ($stmt->affected_rows > 0) {
                die(json_encode(['code' => 1, 'msg' => '删除成功']));
            } else {
                die(json_encode(['code' => 0, 'msg' => '删除失败，可能卡密不存在']));
            }
            $stmt->close();
            break;
        case 'search_km':
            $keyword = isset($_POST['keyword']) ? trim($_POST['keyword']) : '';
            if (empty($keyword)) {
                die(json_encode(['code' => 0, 'msg' => '请输入搜索关键词']));
            }
            $stmt = $link->prepare("SELECT * FROM vip_km WHERE km LIKE ? ORDER BY km DESC LIMIT 100");
            $search_keyword = '%' . $keyword . '%';
            $stmt->bind_param("s", $search_keyword);
            $stmt->execute();
            $result = $stmt->get_result();
            $data = [];
            while($row = $result->fetch_assoc()) {
                $row['display_time'] = $row['type'] == 'vip' ? formatSeconds($row['time']) : $row['time'] . ' (个/元)';
                $row['type_name'] = $row['type'] == 'vip' ? '会员时长' : '余额/文档';
                $data[] = $row;
            }
            $stmt->close();
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
            $search = isset($_POST['search']) ? trim($_POST['search']) : '';
            if (is_dir($user_dir)) {
                $files = scandir($user_dir);
                foreach ($files as $file) {
                    if ($file !== '.' && $file !== '..' && is_dir($user_dir . $file)) {
                        if (!empty($search) && strpos($file, $search) === false) {
                            continue;
                        }
                        $viptime = getUserVipTime($file);
                        $money = getUserMoney($file);
                        $device = '未知';
                        $last_active = '未知';
                        $is_banned = false;

                        // 获取最后登录时间
                        $logintime_file = "userss/{$file}/admin/logintime";
                        if (file_exists($logintime_file)) {
                            $last_active = file_get_contents($logintime_file);
                        } else {
                            $last_active = date("Y-m-d H:i", filemtime($user_dir . $file));
                        }

                        // 检查是否封禁
                        $seal_file = "userss/{$file}/admin/seal";
                        if (file_exists($seal_file)) {
                            $seal_time = file_get_contents($seal_file);
                            if ($seal_time > time()) {
                                $is_banned = true;
                            }
                        }

                        // 获取设备信息
                        $device_file = "userss/{$file}/admin/device";
                        if (file_exists($device_file)) {
                            $device_str = file_get_contents($device_file);
                            $device = detectDevice($device_str);
                        }

                        // 计算VIP剩余时间
                        if ($viptime > time()) {
                            $vip_display = date("Y-m-d H:i", $viptime);
                        } else {
                            $vip_display = '已过期';
                        }

                        $users[] = [
                            'username' => $file,
                            'reg_time' => $last_active,
                            'vip_time' => $vip_display,
                            'viptime_raw' => $viptime,
                            'money' => number_format($money, 2) . '点',
                            'money_raw' => $money,
                            'device' => $device,
                            'is_banned' => $is_banned
                        ];
                    }
                }
            }
            die(json_encode(['code' => 1, 'data' => $users]));
            break;
        case 'update_user':
            $username = isset($_POST['username']) ? trim($_POST['username']) : '';
            $field = isset($_POST['field']) ? trim($_POST['field']) : '';
            $value = isset($_POST['value']) ? trim($_POST['value']) : '';

            if (empty($username) || empty($field)) {
                die(json_encode(['code' => 0, 'msg' => '参数不完整']));
            }

            $user_dir = 'userss/' . $username . '/admin/';
            if (!is_dir($user_dir)) {
                die(json_encode(['code' => 0, 'msg' => '用户不存在']));
            }

            if ($field === 'vip_time') {
                $success = setUserVipTime($username, intval($value));
            } elseif ($field === 'money') {
                $success = setUserMoney($username, floatval($value));
            } else {
                die(json_encode(['code' => 0, 'msg' => '不支持的字段']));
            }

            if ($success) {
                die(json_encode(['code' => 1, 'msg' => '更新成功']));
            } else {
                die(json_encode(['code' => 0, 'msg' => '更新失败']));
            }
            break;
        case 'ban_user':
            $username = isset($_POST['username']) ? trim($_POST['username']) : '';
            if (empty($username)) {
                die(json_encode(['code' => 0, 'msg' => '用户名不能为空']));
            }

            $user_dir = 'userss/' . $username . '/admin/';
            if (!is_dir($user_dir)) {
                die(json_encode(['code' => 0, 'msg' => '用户不存在']));
            }

            $seal_file = $user_dir . 'seal';
            if (file_exists($seal_file)) {
                $seal_time = file_get_contents($seal_file);
                if ($seal_time > time()) {
                    unlink($seal_file);
                    die(json_encode(['code' => 1, 'msg' => '已解封用户']));
                } else {
                    unlink($seal_file);
                }
            }
            // 封禁10年
            $ban_time = time() + 315360000;
            file_put_contents($seal_file, $ban_time);
            die(json_encode(['code' => 1, 'msg' => '已封禁用户']));
            break;
        case 'identify_db_tables':
            // 识别数据库中的所有表
            $all_tables = [];
            $table_info = [];
            
            // 获取所有表
            $result = $link->query("SHOW TABLES");
            while ($row = $result->fetch_array()) {
                $table_name = $row[0];
                $all_tables[] = $table_name;
                
                // 获取表结构
                $structure_result = $link->query("DESCRIBE $table_name");
                $fields = [];
                while ($field_row = $structure_result->fetch_assoc()) {
                    $fields[] = [
                        'name' => $field_row['Field'],
                        'type' => $field_row['Type'],
                        'null' => $field_row['Null'],
                        'key' => $field_row['Key'],
                        'default' => $field_row['Default']
                    ];
                }
                
                // 获取数据量
                $count_result = $link->query("SELECT COUNT(*) as total FROM $table_name");
                $total = $count_result->fetch_assoc()['total'];
                
                // 获取前10条数据
                $data_result = $link->query("SELECT * FROM $table_name LIMIT 10");
                $sample_data = [];
                if ($data_result && $data_result->num_rows > 0) {
                    while ($data_row = $data_result->fetch_assoc()) {
                        $sample_data[] = $data_row;
                    }
                }
                
                $table_info[$table_name] = [
                    'fields' => $fields,
                    'total' => $total,
                    'sample_data' => $sample_data
                ];
            }
            
            die(json_encode(['code' => 1, 'data' => [
                'tables' => $all_tables,
                'table_info' => $table_info
            ]]));
            break;

        case 'get_config':
            $configs = [
                'reg_max_num' => readConfig('reg_max_num.php'),
                'user_max_size' => readConfig('user_max_size.php'),
                'pagetime' => readConfig('pagetime'),
                'notice' => readConfig('notice'),
                'sign' => readConfig('sign'),
                'docking' => readConfig('docking'),
                'IP' => readConfig('IP'),
                'api' => readConfig('api'),
                'blessing' => readConfig('blessing'),
                'codeIP' => readConfig('codeIP'),
                'cooperation' => readConfig('cooperation'),
                'exchange' => readConfig('exchange'),
                'iyu' => readConfig('iyu'),
                'joke' => readConfig('joke'),
                'new' => readConfig('new'),
                'random' => readConfig('random'),
                'userIP' => readConfig('userIP'),
                '轮番广告' => readConfig('轮番广告')
            ];
            die(json_encode(['code' => 1, 'data' => $configs]));
            break;
        case 'update_config':
            $file = isset($_POST['file']) ? trim($_POST['file']) : '';
            $content = isset($_POST['content']) ? trim($_POST['content']) : '';

            if (empty($file)) {
                die(json_encode(['code' => 0, 'msg' => '文件名不能为空']));
            }

            $success = writeConfig($file, $content);
            if ($success) {
                die(json_encode(['code' => 1, 'msg' => '更新成功']));
            } else {
                die(json_encode(['code' => 0, 'msg' => '更新失败，请检查文件权限']));
            }
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            background: white;
            padding: 2rem;
            border-radius: 1rem;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
            width: 100%;
            max-width: 400px;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <h2 class="text-center mb-4">AppDoc 总管理后台</h2>
        <form id="loginForm">
            <div class="mb-3">
                <label class="form-label">管理员密码</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">进入后台</button>
        </form>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --sidebar-width: 260px;
        }
        body {
            background-color: #f4f7f6;
        }
        .sidebar {
            width: var(--sidebar-width);
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            background: #2c3e50;
            color: white;
            transition: all 0.3s;
            z-index: 1000;
            overflow-y: auto;
        }
        .sidebar .nav-link {
            color: #bdc3c7;
            padding: 10px 20px;
            border-left: 4px solid transparent;
            font-size: 14px;
        }
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            color: white;
            background: #34495e;
            border-left-color: #3498db;
        }
        .sidebar .category-title {
            color: #7f8c8d;
            font-size: 12px;
            padding: 10px 20px;
            margin: 10px 0 5px 0;
            border-bottom: 1px solid #3a536b;
            text-transform: uppercase;
            font-weight: 600;
        }
        .content {
            margin-left: var(--sidebar-width);
            padding: 20px;
            transition: all 0.3s;
        }
        .card {
            border: none;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            border-radius: 0.5rem;
            margin-bottom: 20px;
        }
        .stat-card {
            transition: transform 0.2s;
            cursor: pointer;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .device-badge {
            font-size: 0.8rem;
            padding: 2px 8px;
        }
        .copy-btn {
            cursor: pointer;
            color: #3498db;
            transition: color 0.2s;
        }
        .copy-btn:hover {
            color: #2980b9;
        }
        .switch-card {
            border-left: 4px solid #3498db;
            transition: transform 0.2s;
        }
        .switch-card:hover {
            transform: translateX(5px);
        }
        .config-section {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
        }
        .config-title {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 10px;
            border-bottom: 2px solid #3498db;
            padding-bottom: 5px;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="p-4 border-bottom border-secondary">
            <h4 class="m-0"><i class="fas fa-cube me-2"></i>AppDoc PRO</h4>
            <small class="text-muted">综合管理后台</small>
        </div>
        <nav class="mt-2">
            <div class="category-title"><i class="fas fa-tachometer-alt me-2"></i>数据统计</div>
            <a href="#" class="nav-link active" onclick="showPage('stats')"><i class="fas fa-chart-line me-2"></i>运行统计</a>

            <div class="category-title"><i class="fas fa-key me-2"></i>卡密管理</div>
            <a href="#" class="nav-link" onclick="showPage('km')"><i class="fas fa-key me-2"></i>卡密生成与管理</a>

            <div class="category-title"><i class="fas fa-users me-2"></i>用户管理</div>
            <a href="#" class="nav-link" onclick="showPage('users')"><i class="fas fa-user-edit me-2"></i>用户列表</a>

            <div class="category-title"><i class="fas fa-sliders-h me-2"></i>系统配置</div>
            <a href="#" class="nav-link" onclick="showPage('config')"><i class="fas fa-cogs me-2"></i>功能开关</a>

            <div class="category-title mt-3"></div>
            <a href="?action=logout" class="nav-link text-danger"><i class="fas fa-sign-out-alt me-2"></i>退出登录</a>
        </nav>
    </div>

    <div class="content">
        <!-- 运行统计页面 -->
        <div id="page-stats" class="page-content">
            <h3 class="mb-4"><i class="fas fa-chart-line me-2"></i>运行统计</h3>
            <div class="row">
                <div class="col-md-3">
                    <div class="card stat-card bg-primary text-white p-3" onclick="showPage('km')">
                        <div class="card-body">
                            <h6 class="card-title">总卡密数量</h6>
                            <h2 class="mb-0" id="stat-total">-</h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card bg-warning text-dark p-3" onclick="showPage('km')">
                        <div class="card-body">
                            <h6 class="card-title">会员卡密</h6>
                            <h2 class="mb-0" id="stat-vip">-</h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card bg-success text-white p-3" onclick="showPage('km')">
                        <div class="card-body">
                            <h6 class="card-title">余额卡密</h6>
                            <h2 class="mb-0" id="stat-money">-</h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card bg-info text-white p-3" onclick="showPage('users')">
                        <div class="card-body">
                            <h6 class="card-title">后台用户数</h6>
                            <h2 class="mb-0" id="stat-users">-</h2>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 卡密管理页面 -->
        <div id="page-km" class="page-content" style="display:none;">
            <h3 class="mb-4"><i class="fas fa-key me-2"></i>卡密管理</h3>
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title mb-3"><i class="fas fa-plus-circle me-2"></i>批量生成卡密</h5>
                    <form id="genKmForm">
                        <div class="row">
                            <div class="col-md-3">
                                <label class="form-label">卡密类型</label>
                                <select name="type" class="form-select" id="kmType" onchange="updateTimeUnitHint()">
                                    <option value="vip">会员时长</option>
                                    <option value="money">余额点数</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">数值单位</label>
                                <select name="time_unit" class="form-select" id="timeUnit" onchange="updateTimeUnitHint()">
                                    <option value="seconds">秒(自定义)</option>
                                    <option value="minutes">分钟</option>
                                    <option value="hours">小时</option>
                                    <option value="days">天</option>
                                    <option value="weeks">周</option>
                                    <option value="months">月</option>
                                    <option value="years">年</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">数值</label>
                                <input type="number" name="time_value" class="form-control" value="1" min="0" required id="timeValue">
                                <small class="text-muted" id="timeUnitHint">输入秒数</small>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">生成数量</label>
                                <input type="number" name="num" class="form-control" value="10" min="1" max="100" required>
                            </div>
                            <div class="col-md-12 mt-3">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-magic me-2"></i>立即生成
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="card mt-3">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="card-title mb-0"><i class="fas fa-list me-2"></i>卡密列表</h5>
                        <div class="input-group" style="width: 300px;">
                            <input type="text" id="kmSearch" class="form-control" placeholder="搜索卡密...">
                            <button class="btn btn-outline-primary" onclick="searchKm()">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>序号</th>
                                    <th>卡密内容</th>
                                    <th>类型</th>
                                    <th>数值</th>
                                    <th>状态</th>
                                    <th>操作</th>
                                </tr>
                            </thead>
                            <tbody id="kmList"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- 用户管理页面 -->
        <div id="page-users" class="page-content" style="display:none;">
            <h3 class="mb-4"><i class="fas fa-user-edit me-2"></i>用户管理</h3>
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="card-title mb-0"><i class="fas fa-users me-2"></i>用户列表</h5>
                        <div class="input-group" style="width: 300px;">
                            <input type="text" id="userSearch" class="form-control" placeholder="搜索用户名...">
                            <button class="btn btn-outline-primary" onclick="searchUsers()">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>用户名</th>
                                    <th>设备类型</th>
                                    <th>VIP到期时间</th>
                                    <th>余额</th>
                                    <th>最后活动</th>
                                    <th>操作</th>
                                </tr>
                            </thead>
                            <tbody id="userList"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- 用户详情弹窗 -->
        <div class="modal fade" id="userDetailModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="fas fa-user me-2"></i>用户详情 - <span id="detailUsername"></span></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <form id="updateUserForm">
                            <div class="mb-3">
                                <label class="form-label"><i class="fas fa-crown me-2"></i>VIP时长(时间戳)</label>
                                <input type="number" id="detailVipTime" class="form-control" name="vip_time">
                                <small class="text-muted">输入时间戳，当前时间:<?php echo time(); ?></small>
                                <br>
                                <small class="text-muted">1天=<?php echo time()+86400; ?></small>
                                <br>
                                <small class="text-muted">1个月=<?php echo time()+2592000; ?></small>
                            </div>
                            <div class="mb-3">
                                <label class="form-label"><i class="fas fa-coins me-2"></i>余额(点数)</label>
                                <input type="number" id="detailMoney" class="form-control" name="money" step="0.01">
                            </div>
                            <div class="mb-3">
                                <label class="form-label"><i class="fas fa-mobile-alt me-2"></i>设备信息</label>
                                <input type="text" id="detailDevice" class="form-control" name="device" readonly>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-danger" onclick="banUser()">
                            <i class="fas fa-ban me-2"></i>封禁/解封
                        </button>
                        <button type="button" class="btn btn-primary" onclick="updateUser()">
                            <i class="fas fa-save me-2"></i>保存修改
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- 卡密详情弹窗 -->
        <div class="modal fade" id="kmDetailModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="fas fa-info-circle me-2"></i>卡密详情</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body" id="kmDetailContent">
                        <!-- 动态内容 -->
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-2"></i>关闭
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- 功能配置页面 -->
        <div id="page-config" class="page-content" style="display:none;">
            <h3 class="mb-4"><i class="fas fa-cogs me-2"></i>APP功能配置</h3>
            
            <!-- 基础配置 -->
            <div class="config-section">
                <div class="config-title"><i class="fas fa-shield-alt me-2"></i>基础配置</div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="card switch-card">
                            <div class="card-body">
                                <h5 class="card-title"><i class="fas fa-user-plus me-2"></i>注册限制</h5>
                                <div class="mb-3">
                                    <label class="form-label">最大注册数量</label>
                                    <input type="number" id="config_reg_max_num" class="form-control" placeholder="输入数值">
                                    <small class="text-muted">限制最多注册的用户数量</small>
                                </div>
                                <button class="btn btn-primary" onclick="updateConfig('reg_max_num.php', 'config_reg_max_num')">
                                    <i class="fas fa-save me-2"></i>保存
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card switch-card">
                            <div class="card-body">
                                <h5 class="card-title"><i class="fas fa-hdd me-2"></i>用户文件大小</h5>
                                <div class="mb-3">
                                    <label class="form-label">最大文件大小(MB)</label>
                                    <input type="number" id="config_user_max_size" class="form-control" placeholder="输入数值">
                                    <small class="text-muted">限制用户上传文件的最大大小</small>
                                </div>
                                <button class="btn btn-primary" onclick="updateConfig('user_max_size.php', 'config_user_max_size')">
                                    <i class="fas fa-save me-2"></i>保存
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 功能开关 -->
            <div class="config-section">
                <div class="config-title"><i class="fas fa-toggle-on me-2"></i>功能开关</div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="card switch-card">
                            <div class="card-body">
                                <h5 class="card-title"><i class="fas fa-clock me-2"></i>页面时间</h5>
                                <div class="mb-3">
                                    <label class="form-label">时间戳</label>
                                    <input type="text" id="config_pagetime" class="form-control" placeholder="输入时间戳">
                                    <small class="text-muted">页面显示的时间戳</small>
                                </div>
                                <button class="btn btn-primary" onclick="updateConfig('pagetime', 'config_pagetime')">
                                    <i class="fas fa-save me-2"></i>保存
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card switch-card">
                            <div class="card-body">
                                <h5 class="card-title"><i class="fas fa-bullhorn me-2"></i>公告通知</h5>
                                <div class="mb-3">
                                    <label class="form-label">公告内容</label>
                                    <textarea id="config_notice" class="form-control" rows="3" placeholder="输入公告内容"></textarea>
                                    <small class="text-muted">APP内显示的公告内容</small>
                                </div>
                                <button class="btn btn-primary" onclick="updateConfig('notice', 'config_notice')">
                                    <i class="fas fa-save me-2"></i>保存
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 增值功能 -->
            <div class="config-section">
                <div class="config-title"><i class="fas fa-gem me-2"></i>增值功能</div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="card switch-card">
                            <div class="card-body">
                                <h5 class="card-title"><i class="fas fa-calendar-check me-2"></i>签到功能</h5>
                                <div class="mb-3">
                                    <label class="form-label">签到配置</label>
                                    <textarea id="config_sign" class="form-control" rows="3" placeholder="输入签到配置"></textarea>
                                    <small class="text-muted">控制签到功能的开关和奖励</small>
                                </div>
                                <button class="btn btn-primary" onclick="updateConfig('sign', 'config_sign')">
                                    <i class="fas fa-save me-2"></i>保存
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card switch-card">
                            <div class="card-body">
                                <h5 class="card-title"><i class="fas fa-gift me-2"></i>福利功能</h5>
                                <div class="mb-3">
                                    <label class="form-label">福利配置</label>
                                    <textarea id="config_blessing" class="form-control" rows="3" placeholder="输入福利配置"></textarea>
                                    <small class="text-muted">控制福利功能的开关和奖励</small>
                                </div>
                                <button class="btn btn-primary" onclick="updateConfig('blessing', 'config_blessing')">
                                    <i class="fas fa-save me-2"></i>保存
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 对接配置 -->
            <div class="config-section">
                <div class="config-title"><i class="fas fa-plug me-2"></i>对接配置</div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="card switch-card">
                            <div class="card-body">
                                <h5 class="card-title"><i class="fas fa-plug me-2"></i>对接参数</h5>
                                <div class="mb-3">
                                    <label class="form-label">对接配置</label>
                                    <textarea id="config_docking" class="form-control" rows="3" placeholder="输入对接参数"></textarea>
                                    <small class="text-muted">APP对接相关配置</small>
                                </div>
                                <button class="btn btn-primary" onclick="updateConfig('docking', 'config_docking')">
                                    <i class="fas fa-save me-2"></i>保存
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card switch-card">
                            <div class="card-body">
                                <h5 class="card-title"><i class="fas fa-globe me-2"></i>API配置</h5>
                                <div class="mb-3">
                                    <label class="form-label">API参数</label>
                                    <textarea id="config_api" class="form-control" rows="3" placeholder="输入API配置"></textarea>
                                    <small class="text-muted">API对接相关参数</small>
                                </div>
                                <button class="btn btn-primary" onclick="updateConfig('api', 'config_api')">
                                    <i class="fas fa-save me-2"></i>保存
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 娱乐功能 -->
            <div class="config-section">
                <div class="config-title"><i class="fas fa-gamepad me-2"></i>娱乐功能</div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="card switch-card">
                            <div class="card-body">
                                <h5 class="card-title"><i class="fas fa-laugh me-2"></i>笑话功能</h5>
                                <div class="mb-3">
                                    <label class="form-label">笑话配置</label>
                                    <textarea id="config_joke" class="form-control" rows="3" placeholder="输入笑话配置"></textarea>
                                    <small class="text-muted">笑话内容的配置</small>
                                </div>
                                <button class="btn btn-primary" onclick="updateConfig('joke', 'config_joke')">
                                    <i class="fas fa-save me-2"></i>保存
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card switch-card">
                            <div class="card-body">
                                <h5 class="card-title"><i class="fas fa-random me-2"></i>随机功能</h5>
                                <div class="mb-3">
                                    <label class="form-label">随机配置</label>
                                    <textarea id="config_random" class="form-control" rows="3" placeholder="输入随机配置"></textarea>
                                    <small class="text-muted">随机内容的配置</small>
                                </div>
                                <button class="btn btn-primary" onclick="updateConfig('random', 'config_random')">
                                    <i class="fas fa-save me-2"></i>保存
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 广告功能 -->
            <div class="config-section">
                <div class="config-title"><i class="fas fa-ad me-2"></i>广告配置</div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="card switch-card">
                            <div class="card-body">
                                <h5 class="card-title"><i class="fas fa-bullhorn me-2"></i>轮番广告</h5>
                                <div class="mb-3">
                                    <label class="form-label">广告配置</label>
                                    <textarea id="config_轮番广告" class="form-control" rows="3" placeholder="输入轮番广告配置"></textarea>
                                    <small class="text-muted">轮番广告的内容配置</small>
                                </div>
                                <button class="btn btn-primary" onclick="updateConfig('轮番广告', 'config_轮番广告')">
                                    <i class="fas fa-save me-2"></i>保存
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card switch-card">
                            <div class="card-body">
                                <h5 class="card-title"><i class="fas fa-star me-2"></i>新功能</h5>
                                <div class="mb-3">
                                    <label class="form-label">新功能配置</label>
                                    <textarea id="config_new" class="form-control" rows="3" placeholder="输入新功能配置"></textarea>
                                    <small class="text-muted">新功能的开关配置</small>
                                </div>
                                <button class="btn btn-primary" onclick="updateConfig('new', 'config_new')">
                                    <i class="fas fa-save me-2"></i>保存
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 其他配置 -->
            <div class="config-section">
                <div class="config-title"><i class="fas fa-cogs me-2"></i>其他配置</div>
                <div class="row">
                    <div class="col-md-4">
                        <div class="card switch-card">
                            <div class="card-body">
                                <h5 class="card-title"><i class="fas fa-map-marker-alt me-2"></i>IP配置</h5>
                                <div class="mb-3">
                                    <label class="form-label">IP地址</label>
                                    <input type="text" id="config_IP" class="form-control" placeholder="输入IP地址">
                                </div>
                                <button class="btn btn-primary btn-sm" onclick="updateConfig('IP', 'config_IP')">保存</button>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card switch-card">
                            <div class="card-body">
                                <h5 class="card-title"><i class="fas fa-key me-2"></i>编码IP</h5>
                                <div class="mb-3">
                                    <label class="form-label">编码配置</label>
                                    <input type="text" id="config_codeIP" class="form-control" placeholder="输入编码配置">
                                </div>
                                <button class="btn btn-primary btn-sm" onclick="updateConfig('codeIP', 'config_codeIP')">保存</button>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card switch-card">
                            <div class="card-body">
                                <h5 class="card-title"><i class="fas fa-exchange-alt me-2"></i>兑换功能</h5>
                                <div class="mb-3">
                                    <label class="form-label">兑换配置</label>
                                    <input type="text" id="config_exchange" class="form-control" placeholder="输入兑换配置">
                                </div>
                                <button class="btn btn-primary btn-sm" onclick="updateConfig('exchange', 'config_exchange')">保存</button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4">
                        <div class="card switch-card">
                            <div class="card-body">
                                <h5 class="card-title"><i class="fas fa-user me-2"></i>用户IP</h5>
                                <div class="mb-3">
                                    <label class="form-label">用户IP配置</label>
                                    <input type="text" id="config_userIP" class="form-control" placeholder="输入用户IP配置">
                                </div>
                                <button class="btn btn-primary btn-sm" onclick="updateConfig('userIP', 'config_userIP')">保存</button>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card switch-card">
                            <div class="card-body">
                                <h5 class="card-title"><i class="fas fa-handshake me-2"></i>合作功能</h5>
                                <div class="mb-3">
                                    <label class="form-label">合作配置</label>
                                    <input type="text" id="config_cooperation" class="form-control" placeholder="输入合作配置">
                                </div>
                                <button class="btn btn-primary btn-sm" onclick="updateConfig('cooperation', 'config_cooperation')">保存</button>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card switch-card">
                            <div class="card-body">
                                <h5 class="card-title"><i class="fas fa-comments me-2"></i>爱语功能</h5>
                                <div class="mb-3">
                                    <label class="form-label">爱语配置</label>
                                    <input type="text" id="config_iyu" class="form-control" placeholder="输入爱语配置">
                                </div>
                                <button class="btn btn-primary btn-sm" onclick="updateConfig('iyu', 'config_iyu')">保存</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    let currentUser = '';

    function showPage(id) {
        $('.page-content').hide();
        $('#page-' + id).fadeIn();
        $('.nav-link').removeClass('active');
        $(`a[onclick="showPage('${id}')"]`).addClass('active');
        if(id === 'stats') loadStats();
        if(id === 'km') loadKms();
        if(id === 'users') loadUsers();
        if(id === 'config') loadConfig();
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
        console.log('开始加载卡密列表...');
        $.get('?action=km_list', function(res) {
            console.log('卡密列表响应:', res);
            if(res.code === 1) {
                let html = '';
                if(res.data.length > 0) {
                    res.data.forEach((item, index) => {
                        html += `<tr>
                            <td>${index + 1}</td>
                            <td><code class="copy-btn" onclick="copyToClipboard('${item.km}')">${item.km}</code></td>
                            <td>${item.type_name}</td>
                            <td>${item.display_time}</td>
                            <td><span class="badge bg-success">有效</span></td>
                            <td>
                                <button class="btn btn-sm btn-info" onclick="showKmDetail('${item.km}')">
                                    <i class="fas fa-info-circle"></i> 详情
                                </button>
                                <button class="btn btn-sm btn-warning" onclick="identifyKm('${item.km}')">
                                    <i class="fas fa-search"></i> 识别
                                </button>
                                <button class="btn btn-sm btn-danger" onclick="deleteKm('${item.km}')">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>`;
                    });
                } else {
                    html = '<tr><td colspan="6" class="text-center">暂无卡密数据</td></tr>';
                }
                $('#kmList').html(html);
            } else {
                console.error('卡密列表加载失败:', res.msg);
                $('#kmList').html('<tr><td colspan="6" class="text-center text-danger">加载失败: ' + res.msg + '</td></tr>');
            }
        }).fail(function(xhr, status, error) {
            console.error('AJAX请求失败:', status, error);
            $('#kmList').html('<tr><td colspan="6" class="text-center text-danger">网络错误，请检查数据库连接</td></tr>');
        });
    }

    function searchKm() {
        const keyword = $('#kmSearch').val().trim();
        if (!keyword) {
            loadKms();
            return;
        }
        console.log('搜索卡密:', keyword);
        $.post('?action=search_km', {keyword: keyword}, function(res) {
            console.log('搜索结果:', res);
            if(res.code === 1) {
                let html = '';
                if(res.data.length > 0) {
                    res.data.forEach((item, index) => {
                        html += `<tr>
                            <td>${index + 1}</td>
                            <td><code class="copy-btn" onclick="copyToClipboard('${item.km}')">${item.km}</code></td>
                            <td>${item.type_name}</td>
                            <td>${item.display_time}</td>
                            <td><span class="badge bg-success">有效</span></td>
                            <td>
                                <button class="btn btn-sm btn-info" onclick="showKmDetail('${item.km}')">
                                    <i class="fas fa-info-circle"></i> 详情
                                </button>
                                <button class="btn btn-sm btn-warning" onclick="identifyKm('${item.km}')">
                                    <i class="fas fa-search"></i> 识别
                                </button>
                                <button class="btn btn-sm btn-danger" onclick="deleteKm('${item.km}')">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>`;
                    });
                } else {
                    html = '<tr><td colspan="6" class="text-center">未找到匹配的卡密</td></tr>';
                }
                $('#kmList').html(html);
            } else {
                alert(res.msg);
            }
        });
    }

    function deleteKm(km) {
        if (!confirm('确定要删除这个卡密吗？')) return;
        console.log('删除卡密:', km);
        $.post('?action=delete_km', {id: km}, function(res) {
            console.log('删除响应:', res);
            alert(res.msg);
            if (res.code === 1) loadKms();
        }).fail(function(xhr, status, error) {
            console.error('删除失败:', status, error);
            alert('网络错误，请检查数据库连接');
        });
    }

    function copyToClipboard(text) {
        navigator.clipboard.writeText(text).then(() => {
            alert('已复制到剪贴板: ' + text);
        }).catch(err => {
            console.error('复制失败:', err);
            const textarea = document.createElement('textarea');
            textarea.value = text;
            document.body.appendChild(textarea);
            textarea.select();
            document.execCommand('copy');
            document.body.removeChild(textarea);
            alert('已复制到剪贴板: ' + text);
        });
    }

    function showKmDetail(km) {
        $.get('?action=get_config', function(configRes) {
            const dockingConfig = configRes.data.docking || '';
            const apiConfig = configRes.data.api || '';
            const IPConfig = configRes.data.IP || '';
            const codeIPConfig = configRes.data.codeIP || '';
            const userIPConfig = configRes.data.userIP || '';
            
            let detailHtml = `
                <div class="km-detail-info">
                    <h5 class="mb-3"><i class="fas fa-key me-2"></i>卡密详细信息</h5>
                    <div class="mb-3">
                        <strong>卡密：</strong><code>${km}</code>
                    </div>
                    <div class="mb-3">
                        <strong>对接配置：</strong><pre class="bg-light p-2">${dockingConfig || '未配置'}</pre>
                    </div>
                    <div class="mb-3">
                        <strong>API配置：</strong><pre class="bg-light p-2">${apiConfig || '未配置'}</pre>
                    </div>
                    <div class="mb-3">
                        <strong>IP配置：</strong><pre class="bg-light p-2">${IPConfig || '未配置'}</pre>
                    </div>
                    <div class="mb-3">
                        <strong>代码IP：</strong><pre class="bg-light p-2">${codeIPConfig || '未配置'}</pre>
                    </div>
                    <div class="mb-3">
                        <strong>用户IP：</strong><pre class="bg-light p-2">${userIPConfig || '未配置'}</pre>
                    </div>
                </div>
            `;
            
            $('#kmDetailContent').html(detailHtml);
            $('#kmDetailModal').modal('show');
        });
    }

    function identifyKm(km) {
        console.log('开始识别卡密:', km);
        
        // HTML特殊字符转义函数
        function htmlspecialchars(str) {
            if (!str) return '';
            str = String(str);
            return str.replace(/&/g, '&amp;')
                     .replace(/</g, '&lt;')
                     .replace(/>/g, '&gt;')
                     .replace(/"/g, '&quot;')
                     .replace(/'/g, '&#039;');
        }
        
        // 显示加载状态
        $('#kmDetailContent').html(`
            <div class="text-center py-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">加载中...</span>
                </div>
                <p class="mt-3">正在识别数据库中的所有表...</p>
            </div>
        `);
        $('#kmDetailModal').modal('show');
        
        // 获取数据库表信息
        $.get('?action=identify_db_tables', function(res) {
            console.log('数据库表信息:', res);
            
            if (res.code === 1) {
                const tables = res.data.tables;
                const tableInfo = res.data.table_info;
                
                let identifyResult = `
                    <div class="identify-result">
                        <h5 class="mb-3"><i class="fas fa-search me-2"></i>数据库表识别结果</h5>
                        <div class="alert alert-info">
                            <strong>识别的卡密：</strong><code>${km}</code>
                        </div>
                        
                        <div class="alert alert-success">
                            <strong><i class="fas fa-database me-2"></i>发现 ${tables.length} 个数据库表</strong>
                        </div>
                        
                        <div class="mb-3">
                            <h6><i class="fas fa-table me-2"></i>表列表：</h6>
                            <div class="row">
                `;
                
                // 显示所有表的概览
                tables.forEach(tableName => {
                    const info = tableInfo[tableName];
                    const badgeClass = info.total > 0 ? 'bg-success' : 'bg-secondary';
                    
                    identifyResult += `
                        <div class="col-md-4 mb-2">
                            <div class="card border-info">
                                <div class="card-body p-2">
                                    <h6 class="card-title mb-1">
                                        <i class="fas fa-table me-1"></i>${tableName}
                                        <span class="badge ${badgeClass} float-end">${info.total}条</span>
                                    </h6>
                                    <small class="text-muted">${info.fields.length}个字段</small>
                                </div>
                            </div>
                        </div>
                    `;
                });
                
                identifyResult += `
                            </div>
                        </div>
                        
                        <!-- 详细表信息 -->
                        <h6 class="mt-4 mb-3"><i class="fas fa-info-circle me-2"></i>详细表结构：</h6>
                        <div class="accordion" id="tablesAccordion">
                `;
                
                // 为每个表创建折叠面板
                tables.forEach((tableName, index) => {
                    const info = tableInfo[tableName];
                    
                    identifyResult += `
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="heading${index}">
                                <button class="accordion-button collapsed" type="button" 
                                        data-bs-toggle="collapse" data-bs-target="#collapse${index}">
                                    <i class="fas fa-table me-2"></i><strong>${tableName}</strong>
                                    <span class="badge bg-primary ms-2">${info.total}条数据</span>
                                    <span class="badge bg-info ms-1">${info.fields.length}个字段</span>
                                </button>
                            </h2>
                            <div id="collapse${index}" class="accordion-collapse collapse" 
                                 data-bs-parent="#tablesAccordion">
                                <div class="accordion-body">
                                    <h6 class="mb-2">表结构：</h6>
                                    <table class="table table-sm table-bordered mb-3">
                                        <thead class="table-light">
                                            <tr>
                                                <th>字段名</th>
                                                <th>类型</th>
                                                <th>键</th>
                                                <th>允许NULL</th>
                                                <th>默认值</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                    `;
                    
                    info.fields.forEach(field => {
                        identifyResult += `
                            <tr>
                                <td><strong>${htmlspecialchars(field.name)}</strong></td>
                                <td><code>${htmlspecialchars(field.type)}</code></td>
                                <td>${field.key ? `<span class="badge bg-primary">${htmlspecialchars(field.key)}</span>` : '-'}</td>
                                <td>${htmlspecialchars(field.null)}</td>
                                <td>${field.default ? htmlspecialchars(String(field.default)) : '-'}</td>
                            </tr>
                        `;
                    });
                    
                    identifyResult += `
                                        </tbody>
                                    </table>
                    `;
                    
                    // 显示数据示例
                    if (info.sample_data.length > 0) {
                        identifyResult += `
                            <h6 class="mb-2">数据示例（前${info.sample_data.length}条）：</h6>
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered">
                                    <thead class="table-light">
                                        <tr>
                        `;
                        
                        const firstRow = info.sample_data[0];
                        Object.keys(firstRow).forEach(key => {
                            identifyResult += `<th>${htmlspecialchars(key)}</th>`;
                        });
                        
                        identifyResult += `
                                        </tr>
                                    </thead>
                                    <tbody>
                        `;
                        
                        info.sample_data.forEach(row => {
                            identifyResult += `<tr>`;
                            Object.values(row).forEach(value => {
                                let displayValue = value;
                                if (value === null) {
                                    displayValue = '<em class="text-muted">NULL</em>';
                                } else if (typeof value === 'string' && value.length > 50) {
                                    displayValue = htmlspecialchars(value.substring(0, 50)) + '...';
                                } else {
                                    displayValue = htmlspecialchars(String(value));
                                }
                                identifyResult += `<td>${displayValue}</td>`;
                            });
                            identifyResult += `</tr>`;
                        });
                        
                        identifyResult += `
                                    </tbody>
                                </table>
                            </div>
                        `;
                    } else {
                        identifyResult += `<p class="text-muted">暂无数据</p>`;
                    }
                    
                    identifyResult += `
                                </div>
                            </div>
                        </div>
                    `;
                });
                
                identifyResult += `
                        </div>
                        
                        <div class="mt-4 text-muted">
                            <small><i class="fas fa-info-circle me-1"></i>识别功能已对接数据库中的所有表，包括交流、代码、源码、教程专区等</small>
                        </div>
                    </div>
                `;
                
                $('#kmDetailContent').html(identifyResult);
            } else {
                $('#kmDetailContent').html(`
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        识别失败：${res.msg || '未知错误'}
                    </div>
                `);
            }
        }).fail(function() {
            $('#kmDetailContent').html(`
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    网络错误，请检查数据库连接
                </div>
            `);
        });
    }

    function loadUsers() {
        const search = $('#userSearch').val().trim();
        $.post('?action=get_users', {search: search}, function(res) {
            if(res.code === 1) {
                let html = '';
                res.data.forEach(u => {
                    let deviceColor = u.device === '安卓' ? 'success' : (u.device === 'iOS' ? 'warning' : 'secondary');
                    let banBadge = u.is_banned ? '<span class="badge bg-danger ms-2">已封禁</span>' : '';
                    html += `<tr>
                        <td><strong>${u.username}</strong>${banBadge}</td>
                        <td><span class="badge bg-${deviceColor} device-badge">${u.device}</span></td>
                        <td>${u.vip_time}</td>
                        <td>${u.money}</td>
                        <td>${u.reg_time}</td>
                        <td>
                            <button class="btn btn-sm btn-primary" onclick="showUserDetail('${u.username}')">
                                <i class="fas fa-edit"></i> 管理
                            </button>
                        </td>
                    </tr>`;
                });
                $('#userList').html(html || '<tr><td colspan="6" class="text-center">暂无用户数据</td></tr>');
            }
        });
    }

    function searchUsers() {
        loadUsers();
    }

    function showUserDetail(username) {
        currentUser = username;
        $('#detailUsername').text(username);
        $.post('?action=get_users', {search: username}, function(res) {
            if(res.code === 1 && res.data.length > 0) {
                const user = res.data[0];
                $('#detailVipTime').val(user.viptime_raw);
                $('#detailMoney').val(user.money_raw);
                $('#detailDevice').val(user.device);
                $('#userDetailModal').modal('show');
            }
        });
    }

    function updateUser() {
        const vipTime = $('#detailVipTime').val();
        const money = $('#detailMoney').val();

        $.post('?action=update_user', {
            username: currentUser,
            field: 'vip_time',
            value: vipTime
        }, function(res1) {
            if (res1.code === 1) {
                $.post('?action=update_user', {
                    username: currentUser,
                    field: 'money',
                    value: money
                }, function(res2) {
                    alert(res2.msg);
                    $('#userDetailModal').modal('hide');
                    loadUsers();
                });
            } else {
                alert(res1.msg);
            }
        });
    }

    function banUser() {
        if (!confirm('确定要封禁/解封此用户吗？')) return;
        $.post('?action=ban_user', {username: currentUser}, function(res) {
            alert(res.msg);
            $('#userDetailModal').modal('hide');
            loadUsers();
        });
    }

    function loadConfig() {
        console.log('开始加载配置...');
        $.get('?action=get_config', function(res) {
            console.log('配置响应:', res);
            if(res.code === 1) {
                $('#config_reg_max_num').val(res.data.reg_max_num);
                $('#config_user_max_size').val(res.data.user_max_size);
                $('#config_pagetime').val(res.data.pagetime);
                $('#config_notice').val(res.data.notice);
                $('#config_sign').val(res.data.sign);
                $('#config_docking').val(res.data.docking);
                $('#config_IP').val(res.data.IP);
                $('#config_api').val(res.data.api);
                $('#config_blessing').val(res.data.blessing);
                $('#config_codeIP').val(res.data.codeIP);
                $('#config_cooperation').val(res.data.cooperation);
                $('#config_exchange').val(res.data.exchange);
                $('#config_iyu').val(res.data.iyu);
                $('#config_joke').val(res.data.joke);
                $('#config_new').val(res.data.new);
                $('#config_random').val(res.data.random);
                $('#config_userIP').val(res.data.userIP);
                $('#config_轮番广告').val(res.data.轮番广告);
            } else {
                console.error('配置加载失败:', res.msg);
            }
        }).fail(function(xhr, status, error) {
            console.error('配置加载AJAX失败:', status, error);
        });
    }

    function updateConfig(file, inputId) {
        const content = $('#' + inputId).val();
        console.log('更新配置:', file, content);
        $.post('?action=update_config', {
            file: file,
            content: content
        }, function(res) {
            console.log('更新配置响应:', res);
            alert(res.msg);
        }).fail(function(xhr, status, error) {
            console.error('更新配置AJAX失败:', status, error);
            alert('网络错误，请检查文件权限');
        });
    }

    // 更新时间单位提示
    function updateTimeUnitHint() {
        const kmType = $('#kmType').val();
        const timeUnit = $('#timeUnit').val();
        
        const unitNames = {
            'seconds': '秒',
            'minutes': '分钟',
            'hours': '小时',
            'days': '天',
            'weeks': '周',
            'months': '月',
            'years': '年'
        };
        
        if (kmType === 'vip') {
            $('#timeUnitHint').text(`会员时长，输入${unitNames[timeUnit]}数，自动转换为秒`);
            $('#timeUnit').prop('disabled', false);
        } else {
            $('#timeUnitHint').text('余额点数，直接输入数值，不转换单位');
            $('#timeUnit').prop('disabled', true);
            $('#timeUnit').val('seconds');
        }
    }

    $('#genKmForm').submit(function(e) {
        e.preventDefault();
        $.post('?action=generate_km', $(this).serialize(), function(res) {
            alert(res.msg);
            if(res.code === 1) loadKms();
        });
    });

    $('#userSearch').on('keypress', function(e) {
        if (e.which === 13) {
            searchUsers();
        }
    });

    $('#kmSearch').on('keypress', function(e) {
        if (e.which === 13) {
            searchKm();
        }
    });

    $(document).ready(function() {
        console.log('页面加载完成');
        loadStats();
        updateTimeUnitHint();
    });
    </script>
</body>
</html>
<?php
}
?>