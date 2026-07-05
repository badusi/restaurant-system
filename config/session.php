<?php
session_start();

function requireLogin() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: ../auth/login.php');
        exit();
    }
}

function requireRole($role) {
    requireLogin();
    
    if (is_array($role)) {
        if (!in_array($_SESSION['role'], $role)) {
            header('Location: /restaurant-system/auth/unauthorized.php');
            exit();
        }
    } else {
        if ($_SESSION['role'] !== $role) {
            header('Location: /restaurant-system/auth/unauthorized.php');
            exit();
        }
    }
    
    // Check if user is approved (except for regular users)
    if ($_SESSION['role'] !== 'user' && $_SESSION['role'] !== 'admin' && $_SESSION['status'] !== 'approved') {
        header('Location: /restaurant-system/auth/pending-approval.php');
        exit();
    }
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function getUserRole() {
    return $_SESSION['role'] ?? null;
}

function getUserId() {
    return $_SESSION['user_id'] ?? null;
}
?>
