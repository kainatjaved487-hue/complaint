<?php
session_start();
$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'super_admin';
$_SESSION['name'] = 'Admin';
$_SERVER['SCRIPT_NAME'] = '/complaint/notifications.php';
$_SERVER['PHP_SELF'] = '/complaint/notifications.php';
require_once 'notifications.php';
