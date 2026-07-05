<?php
require_once '../config/database.php';
require_once '../config/session.php';

header('Content-Type: application/json');

requireRole('delivery');

$database = new Database();
$db = $database->getConnection();

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

if ($action === 'start_delivery') {
    $order_id = intval($input['order_id']);
    
    $query = "UPDATE orders SET status = 'out_for_delivery' WHERE id = ? AND delivery_person_id = ? AND status = 'picked_up'";
    $stmt = $db->prepare($query);
    
    if ($stmt->execute([$order_id, $_SESSION['user_id']])) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update order status']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
?>
