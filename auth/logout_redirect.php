<?php
require_once '../config/session.php';

// Destroy all session data
session_unset();
session_destroy();

// Get the correct base URL for redirection
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];
$base_url = $protocol . '://' . $host;

// Redirect to home page
header('Location: ' . $base_url . '/restaurant-system/index.php');
exit();
?>