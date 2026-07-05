<?php
require_once '../config/database.php';
require_once '../config/session.php';

header('Content-Type: application/json');

if (!isLoggedIn() || $_SESSION['role'] !== 'staff_goods') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$database = new Database();
$db = $database->getConnection();

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

try {
    switch ($action) {
        case 'delete':
            $meal_id = $input['meal_id'];
            
            $query = "DELETE FROM meals WHERE id = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$meal_id]);
            
            echo json_encode(['success' => true, 'message' => 'Meal deleted successfully']);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>
