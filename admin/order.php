<?php
require_once '../config/database.php';
require_once '../config/session.php';

requireRole('admin');

$database = new Database();
$db = $database->getConnection();

// Handle order status updates
if ($_POST && isset($_POST['action'])) {
    if ($_POST['action'] === 'update_status') {
        $order_id = intval($_POST['order_id']);
        $new_status = $_POST['status'];
        
        $query = "UPDATE orders SET status = ? WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$new_status, $order_id]);
        
        header('Location: orders.php');
        exit();
    }
}