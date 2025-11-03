<?php
// admin_logout.php - 退出登录

session_start();
session_destroy();
header("Location: admin_login.php");
exit;
?>
