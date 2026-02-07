<?php
/**
 * 卡密管理面板 (增强版)
 * 功能：查看、复制、单删、批量删除卡密，支持分类展示和排序
 */

// 设置响应头，确保在处理 AJAX 请求时返回 JSON
if (isset($_GET['action']) || isset($_POST['action'])) {
    header('Content-Type: application/json; charset=utf-8');
}

// 默认管理密码为 'zxcv25'，请务必修改
if($_GET['pass'] != 'zxc123.0' && $_POST['pass'] != 'zxc123.0') {
    if (isset($_GET['action']) || isset($_POST['action'])) {
        die(json_encode(['code' => 0, 'msg' => '管理密码错误']));
    } else {
        die('管理密码错误');
    }
}

$mysql = [
    "host" => "localhost",
    "username" => "appdoc",
    "password" => "123456",
    "dbname" => "appdoc",
];

// 连接数据库
$link = new mysqli($mysql["host"], $mysql["username"], $mysql["password"], $mysql["dbname"]);
if($link->connect_error) {
    if (isset($_GET['action']) || isset($_POST['action'])) {
        die(json_encode(['code' => 0, 'msg' => "数据库连接失败: " . $link->connect_error]));
    } else {
        die("数据库连接失败: " . $link->connect_error);
    }
}
mysqli_set_charset($link, 'utf8');

// 处理删除请求 (POST)
if (isset($_POST['action']) && $_POST['action'] == 'delete') {
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
}

// 处理查询请求 (GET)
if (isset($_GET['action']) && $_GET['action'] == 'list') {
    // 时间转换函数 (为了在 AJAX 响应中也能使用)
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
    
    $type = isset($_GET['type']) ? $_GET['type'] : 'all';
    
    if ($type == 'all') {
        // 默认按类型和数值排序
        $sql = "SELECT * FROM `vip_km` ORDER BY `type` ASC, `time` ASC";
    } else {
        $type = $link->real_escape_string($type);
        $sql = "SELECT * FROM `vip_km` WHERE `type` = '$type' ORDER BY `time` ASC";
    }
    
    $result = $link->query($sql);
    $data = [];
    
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            // 在返回数据中添加格式化后的时间
            $row['display_time'] = ($row['type'] == 'vip') ? formatSeconds($row['time']) : $row['time'] . ' (个/元)';
            $data[] = $row;
        }
    }
    
    die(json_encode(['code' => 1, 'data' => $data]));
}

// 默认返回 HTML 界面
$link->close();
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>卡密管理系统</title>
    <style>
        /* 样式代码 (为节省篇幅，此处省略，请参考上一步骤中的完整代码) */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Helvetica Neue', Arial, sans-serif;
            background: #f5f7fa;
            color: #333;
        }
        
        .wrapper {
            display: flex;
            min-height: 100vh;
        }
        
        /* 侧边栏 */
        .sidebar {
            width: 250px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px 20px;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        }
        
        .sidebar-title {
            font-size: 1.5em;
            font-weight: bold;
            margin-bottom: 30px;
            text-align: center;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.2);
        }
        
        .sidebar-menu {
            list-style: none;
        }
        
        .sidebar-menu li {
            margin-bottom: 15px;
        }
        
        .sidebar-menu a {
            display: block;
            padding: 12px 15px;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s ease;
            cursor: pointer;
            font-size: 0.95em;
        }
        
        .sidebar-menu a:hover,
        .sidebar-menu a.active {
            background: rgba(255,255,255,0.2);
            transform: translateX(5px);
        }
        
        /* 主内容区 */
        .main-content {
            flex: 1;
            padding: 30px;
            overflow-y: auto;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        
        .header h1 {
            font-size: 1.8em;
            color: #333;
        }
        
        .header-actions {
            display: flex;
            gap: 10px;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.95em;
            transition: all 0.3s ease;
            font-weight: 500;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .btn-danger {
            background: #f5576c;
            color: white;
        }
        
        .btn-danger:hover {
            background: #e63946;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(245, 87, 108, 0.4);
        }
        
        .btn-danger:disabled {
            background: #ccc;
            cursor: not-allowed;
            transform: none;
        }
        
        /* 统计卡片 */
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
        }
        
        .stat-card.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .stat-icon {
            font-size: 2em;
            margin-bottom: 10px;
        }
        
        .stat-number {
            font-size: 2em;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .stat-label {
            font-size: 0.9em;
            opacity: 0.8;
        }
        
        /* 列表容器 */
        .list-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            overflow: hidden;
        }
        
        .list-header {
            padding: 20px;
            background: #f8f9fa;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .list-header-left {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .checkbox-all {
            width: 20px;
            height: 20px;
            cursor: pointer;
        }
        
        .list-header h2 {
            font-size: 1.1em;
            color: #333;
        }
        
        .list-body {
            max-height: 600px;
            overflow-y: auto;
        }
        
        .km-row {
            padding: 15px 20px;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            align-items: center;
            gap: 15px;
            transition: all 0.2s ease;
        }
        
        .km-row:hover {
            background: #f8f9fa;
        }
        
        .km-row.selected {
            background: #e7f3ff;
        }
        
        .km-checkbox {
            width: 20px;
            height: 20px;
            cursor: pointer;
        }
        
        .km-code {
            flex: 1;
            font-family: 'Courier New', monospace;
            background: #f5f7fa;
            padding: 8px 12px;
            border-radius: 6px;
            word-break: break-all;
            cursor: pointer;
            transition: all 0.2s ease;
            user-select: all;
        }
        
        .km-code:hover {
            background: #e9ecef;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
        }
        
        .km-code.copied {
            background: #d4edda;
            color: #155724;
        }
        
        .km-type {
            min-width: 80px;
            text-align: center;
            font-weight: 500;
        }
        
        .km-type.vip {
            color: #667eea;
            background: #f0f4ff;
            padding: 5px 10px;
            border-radius: 6px;
        }
        
        .km-type.money {
            color: #f5576c;
            background: #ffe9ed;
            padding: 5px 10px;
            border-radius: 6px;
        }
        
        .km-time {
            min-width: 120px;
            text-align: center;
            color: #666;
        }
        
        .km-actions {
            display: flex;
            gap: 8px;
        }
        
        .btn-small {
            padding: 6px 12px;
            font-size: 0.85em;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .btn-copy {
            background: #667eea;
            color: white;
        }
        
        .btn-copy:hover {
            background: #764ba2;
        }
        
        .btn-delete {
            background: #f5576c;
            color: white;
        }
        
        .btn-delete:hover {
            background: #e63946;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }
        
        .empty-icon {
            font-size: 3em;
            margin-bottom: 15px;
        }
        
        /* 加载状态 */
        .loading {
            text-align: center;
            padding: 40px;
            color: #666;
        }
        
        .spinner {
            border: 3px solid #f3f3f3;
            border-top: 3px solid #667eea;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            animation: spin 1s linear infinite;
            margin: 0 auto 15px;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* 通知 */
        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 20px;
            border-radius: 8px;
            color: white;
            font-weight: 500;
            animation: slideIn 0.3s ease;
            z-index: 1000;
        }
        
        .notification.success {
            background: #28a745;
        }
        
        .notification.error {
            background: #dc3545;
        }
        
        @keyframes slideIn {
            from {
                transform: translateX(400px);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        /* 响应式设计 */
        @media (max-width: 768px) {
            .wrapper {
                flex-direction: column;
            }
            
            .sidebar {
                width: 100%;
                padding: 20px;
            }
            
            .sidebar-menu {
                display: flex;
                flex-wrap: wrap;
                gap: 10px;
            }
            
            .sidebar-menu li {
                margin-bottom: 0;
                flex: 1;
                min-width: 100px;
            }
            
            .sidebar-menu a {
                padding: 8px 10px;
                text-align: center;
                font-size: 0.85em;
            }
            
            .main-content {
                padding: 15px;
            }
            
            .header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
            
            .header h1 {
                font-size: 1.3em;
            }
            
            .stats-container {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .km-row {
                flex-wrap: wrap;
                gap: 10px;
            }
            
            .km-code {
                flex-basis: 100%;
            }
            
            .km-type,
            .km-time {
                min-width: auto;
                flex: 1;
            }
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <!-- 侧边栏 -->
        <div class="sidebar">
            <div class="sidebar-title">🎫 卡密管理</div>
            <ul class="sidebar-menu">
                <li><a class="menu-item active" data-type="all">📊 全部卡密</a></li>
                <li><a class="menu-item" data-type="vip">👑 会员卡密</a></li>
                <li><a class="menu-item" data-type="money">📄 文档配额</a></li>
            </ul>
        </div>
        
        <!-- 主内容区 -->
        <div class="main-content">
            <!-- 顶部操作栏 -->
            <div class="header">
                <h1 id="page-title">全部卡密</h1>
                <div class="header-actions">
                    <button class="btn btn-danger" id="batch-delete-btn" onclick="batchDelete()" disabled>
                        🗑️ 批量删除
                    </button>
                </div>
            </div>
            
            <!-- 统计卡片 -->
            <div class="stats-container" id="stats-container"></div>
            
            <!-- 列表容器 -->
            <div class="list-container">
                <div class="list-header">
                    <div class="list-header-left">
                        <input type="checkbox" class="checkbox-all" id="checkbox-all" onchange="toggleAll(this)">
                        <h2 id="list-title">卡密列表</h2>
                    </div>
                </div>
                <div class="list-body" id="list-body">
                    <div class="loading">
                        <div class="spinner"></div>
                        <p>加载中...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // 确保在 URL 中传入了 pass 参数
        const urlParams = new URLSearchParams(window.location.search);
        const pass = urlParams.get('pass');
        if (!pass) {
            alert('URL 中缺少管理密码 (pass) 参数！');
        }
        
        let currentType = 'all';
        let allData = [];
        let selectedKms = [];
        
        // 初始化
        document.addEventListener('DOMContentLoaded', function() {
            loadData('all');
            
            // 菜单切换
            document.querySelectorAll('.menu-item').forEach(item => {
                item.addEventListener('click', function() {
                    document.querySelectorAll('.menu-item').forEach(i => i.classList.remove('active'));
                    this.classList.add('active');
                    currentType = this.dataset.type;
                    document.getElementById('page-title').textContent = this.textContent.trim();
                    loadData(currentType);
                });
            });
        });
        
        // 加载数据
        function loadData(type) {
            const listBody = document.getElementById('list-body');
            listBody.innerHTML = '<div class="loading"><div class="spinner"></div><p>加载中...</p></div>';
            
            fetch(`?action=list&type=${type}&pass=${pass}`)
                .then(res => res.json())
                .then(res => {
                    if (res.code === 1) {
                        allData = res.data || [];
                        renderStats();
                        renderList();
                    } else {
                        listBody.innerHTML = '<div class="empty-state"><div class="empty-icon">❌</div><p>' + res.msg + '</p></div>';
                    }
                })
                .catch(err => {
                    listBody.innerHTML = '<div class="empty-state"><div class="empty-icon">⚠️</div><p>加载失败</p></div>';
                });
        }
        
        // 渲染统计卡片
        function renderStats() {
            const statsContainer = document.getElementById('stats-container');
            
            const vipCount = allData.filter(k => k.type === 'vip').length;
            const moneyCount = allData.filter(k => k.type === 'money').length;
            const totalCount = allData.length;
            
            statsContainer.innerHTML = `
                <div class="stat-card ${currentType === 'all' ? 'active' : ''}" onclick="switchType('all')">
                    <div class="stat-icon">📊</div>
                    <div class="stat-number">${totalCount}</div>
                    <div class="stat-label">全部卡密</div>
                </div>
                <div class="stat-card ${currentType === 'vip' ? 'active' : ''}" onclick="switchType('vip')">
                    <div class="stat-icon">👑</div>
                    <div class="stat-number">${vipCount}</div>
                    <div class="stat-label">会员卡密</div>
                </div>
                <div class="stat-card ${currentType === 'money' ? 'active' : ''}" onclick="switchType('money')">
                    <div class="stat-icon">📄</div>
                    <div class="stat-number">${moneyCount}</div>
                    <div class="stat-label">文档配额</div>
                </div>
            `;
        }
        
        // 渲染列表
        function renderList() {
            const listBody = document.getElementById('list-body');
            
            if (allData.length === 0) {
                listBody.innerHTML = '<div class="empty-state"><div class="empty-icon">📭</div><p>暂无卡密</p></div>';
                return;
            }
            
            let html = '';
            allData.forEach(km => {
                const typeLabel = km.type === 'vip' ? '会员' : '文档';
                const typeClass = km.type === 'vip' ? 'vip' : 'money';
                // display_time 字段已在 PHP 中格式化
                const displayTime = km.display_time; 
                
                html += `
                    <div class="km-row" data-km="${km.km}">
                        <input type="checkbox" class="km-checkbox" onchange="updateSelection()">
                        <div class="km-code" onclick="copySingleKm(this, '${km.km}')" title="点击复制">${km.km}</div>
                        <div class="km-type ${typeClass}">${typeLabel}</div>
                        <div class="km-time">${displayTime}</div>
                        <div class="km-actions">
                            <button class="btn-small btn-delete" onclick="deleteSingleKm('${km.km}')">删除</button>
                        </div>
                    </div>
                `;
            });
            
            listBody.innerHTML = html;
            document.getElementById('checkbox-all').checked = false;
            selectedKms = [];
            updateBatchDeleteBtn();
        }
        
        // 复制单个卡密
        function copySingleKm(element, km) {
            navigator.clipboard.writeText(km).then(() => {
                showNotification('已复制到剪贴板', 'success');
                
                if (element.classList && element.classList.contains('km-code')) {
                    element.classList.add('copied');
                    setTimeout(() => element.classList.remove('copied'), 1500);
                }
            });
        }
        
        // 删除单个卡密
        function deleteSingleKm(km) {
            if (confirm('确定要删除这张卡密吗？')) {
                performDelete([km]);
            }
        }
        
        // 全选/反选
        function toggleAll(checkbox) {
            document.querySelectorAll('.km-checkbox').forEach(cb => {
                cb.checked = checkbox.checked;
            });
            updateSelection();
        }
        
        // 更新选中状态
        function updateSelection() {
            selectedKms = [];
            document.querySelectorAll('.km-checkbox').forEach(cb => {
                const row = cb.closest('.km-row');
                if (cb.checked) {
                    const km = row.dataset.km;
                    selectedKms.push(km);
                    row.classList.add('selected');
                } else {
                    row.classList.remove('selected');
                }
            });
            
            // 更新全选框状态
            const allCheckboxes = document.querySelectorAll('.km-checkbox');
            const allChecked = allCheckboxes.length > 0 && selectedKms.length === allCheckboxes.length;
            document.getElementById('checkbox-all').checked = allChecked;
            
            updateBatchDeleteBtn();
        }
        
        // 更新批量删除按钮状态
        function updateBatchDeleteBtn() {
            const btn = document.getElementById('batch-delete-btn');
            btn.disabled = selectedKms.length === 0;
            btn.textContent = `🗑️ 批量删除 (${selectedKms.length})`;
        }
        
        // 批量删除
        function batchDelete() {
            if (selectedKms.length === 0) {
                showNotification('请先选择要删除的卡密', 'error');
                return;
            }
            
            if (confirm(`确定要删除 ${selectedKms.length} 张卡密吗？此操作不可撤销！`)) {
                performDelete(selectedKms);
            }
        }
        
        // 执行删除
        function performDelete(kms) {
            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('pass', pass);
            kms.forEach(km => formData.append('kms[]', km));
            
            fetch('?', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(res => {
                if (res.code === 1) {
                    showNotification(res.msg, 'success');
                    loadData(currentType);
                } else {
                    showNotification(res.msg, 'error');
                }
            })
            .catch(err => showNotification('删除失败', 'error'));
        }
        
        // 切换类型
        function switchType(type) {
            document.querySelectorAll('.menu-item').forEach(item => {
                item.classList.remove('active');
                if (item.dataset.type === type) item.classList.add('active');
            });
            
            let title = '';
            if (type === 'all') title = '全部卡密';
            else if (type === 'vip') title = '会员卡密';
            else if (type === 'money') title = '文档配额';
            document.getElementById('page-title').textContent = title;
            
            currentType = type;
            loadData(type);
        }
        
        // 显示通知
        function showNotification(msg, type) {
            const notification = document.createElement('div');
            notification.className = `notification ${type}`;
            notification.textContent = msg;
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.style.opacity = 0;
                notification.style.transition = 'opacity 0.3s ease';
                setTimeout(() => notification.remove(), 300);
            }, 2000);
        }
    </script>
</body>
</html>
