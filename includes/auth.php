<?php
// includes/auth.php
session_start();

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function getOrgId() {
    return $_SESSION['organization_id'] ?? null;
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

// Global update if session user is active
if (isset($_SESSION['user_id']) && isset($conn)) {
    $uid = (int)$_SESSION['user_id'];
    mysqli_query($conn, "UPDATE users SET last_active_at = NOW(), current_status = IF(current_status = 'offline', 'online', current_status) WHERE id = $uid");
}

function redirect($url) {
    header("Location: $url");
    exit();
}

function checkAuth() {
    if (!isLoggedIn()) {
        redirect(BASE_URL . 'login.php');
    }
}

function checkAdmin() {
    checkAuth();
    if (!isAdmin()) {
        redirect(BASE_URL . 'index.php');
    }
}
?>
