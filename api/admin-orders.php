<?php
require_once '../config/database.php';
require_once '../config/session.php';

header('Content-Type: application/json');

requireRole('admin');

$database = new Database();
$db = $database->getConnection();

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

if ($action === 'mark_ready_for_pickup') {
    // Update all paid orders that are still in processing to ready_for_pickup
    $query = "UPDATE orders SET status = 'ready_for_pickup' 
              WHERE payment_status = 'completed' 
              AND status = 'processing'";
    $stmt = $db->prepare($query);
    
    if ($stmt->execute()) {
        $updated_count = $stmt->rowCount();
        echo json_encode(['success' => true, 'updated_count' => $updated_count]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update orders']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
?>