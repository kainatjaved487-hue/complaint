<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'db.php';

// Get the current script name (e.g., 'login.php')
$current_page = basename($_SERVER['PHP_SELF']);

// List of pages that DO NOT require login
$public_pages = ['login.php', 'register.php'];

// Auth Check Logic
if (!isset($_SESSION['user_id'])) {
    // If user is NOT logged in AND trying to access a protected page
    if (!in_array($current_page, $public_pages)) {
        header("Location: " . BASE_URL . "login.php");
        exit;
    }
} 
// Conflict Fix: If user IS logged in but tries to go to Login/Register, send them to Dashboard
else {
    if (in_array($current_page, $public_pages)) {
        header("Location: " . BASE_URL . "index.php");
        exit;
    }
}
?>