<?php
$admin = isset($_GET["admin"])?$_GET["admin"]:"";
?>

<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>易对接后台缴费中心</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0,user-scalable=no">
    <link type="text/css" media="all"  rel="stylesheet" href="base.css" />
    <script src="https://code.jquery.com/jquery-3.6.1.min.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            background: #f5f7fa;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            font-family: "Microsoft YaHei", "PingFang SC", sans-serif;
            padding: 20px;
        }
        
        .main {
            width: 100%;
            max-width: 400px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.1);
        }
        
        h1 {
            background: #2196F3;
            text-align: center;
            font-size: 18px;
            color: #fff;
            height: 56px;
            line-height: 56px;
            font-weight: normal;
            border-radius: 8px 8px 0 0;
        }
        
        .form-box {
            padding: 32px 28px;
        }
        
        .user, .km {
            width: 100%;
            height: 44px;
            border: 1px solid #dcdfe6;
            border-radius: 4px;
            outline: none;
            font-size: 14px;
            padding: 0 15px;
            color: #606266;
            margin-bottom: 20px;
            transition: border-color 0.2s;
        }
        
        .user:focus, .km:focus {
            border-color: #2196F3;
        }
        
        .user::placeholder, .km::placeholder {
            color: #c0c4cc;
        }
        
        .shiyong {
            display: block;
            width: 100%;
            height: 44px;
            margin-top: 8px;
            background: #2196F3;
            color: #fff;
            border: none;
            border-radius: 4px;
            font-size: 15px;
            cursor: pointer;
            transition: background 0.2s;
        }
        
        .shiyong:hover {
            background: #1e88e5;
        }
        
        .goumai {
            display: block;
            width: 100%;
            height: 44px;
            margin-top: 12px;
            background: #fff;
            color: #606266;
            border: 1px solid #dcdfe6;
            border-radius: 4px;
            font-size: 15px;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .goumai:hover {
            color: #2196F3;
            border-color: #2196F3;
        }
        
        .g {
            margin-top: 24px;
            text-align: center;
            color: #909399;
            font-size: 12px;
        }
        
        @media only screen and (max-width: 480px){
            body {
                padding: 16px;
            }
            .form-box {
                padding: 24px 20px;
            }
        }
    </style>
</head>
<body>
    <div class="main">
        <h1>卡密缴费</h1>
        <div class="form-box">
            <input class="user" value="<?php echo $admin; ?>" placeholder="请输入账号" />
            <input class="km" placeholder="请输入卡密" />
            <button onclick="use_click()" class="shiyong">使用卡密</button>
            <button onclick="window.open('http://fk.mx1.ltd/','_blank')" class="goumai">购买卡密</button>
        </div>
    </div>
    <div class="g">易对接后台缴费中心</div>
</body>

<script>
    function use_click(){
        let user = document.querySelector('.user').value;
        let km = document.querySelector('.km').value;
        if(user == '' || km == '')alert('请输入完整');
        else
        use(user,km);
    }
    
    function use(user,km){
        $.ajax({
        url: "./appdoc_ajax.php",
        type: "POST",
        data: {"user":user,"km":km},
        dataType: "json",
        error: function(error){
            alert("error");
        },
        success: function(data){
            alert(data.msg);
        }
	    });
    }
</script>

</html>
