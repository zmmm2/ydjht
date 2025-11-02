<?php
// dashboard_logout.php - 退出登录

session_start();
session_destroy();
header("Location: dashboard_login.php");
exit;
?>
