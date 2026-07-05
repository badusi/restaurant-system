<?php
require_once '../config/database.php';
require_once '../config/session.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

$database = new Database();
$db = $database->getConnection();

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

try {
    switch ($action) {
        case 'add':
            $meal_id = $input['meal_id'];
            $quantity = $input['quantity'] ?? 1;
            
            // Check if item already in cart
            $query = "SELECT id, quantity FROM cart WHERE user_id = ? AND meal_id = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$_SESSION['user_id'], $meal_id]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($existing) {
                // Update quantity
                $new_quantity = $existing['quantity'] + $quantity;
                $query = "UPDATE cart SET quantity = ? WHERE id = ?";
                $stmt = $db->prepare($query);
                $stmt->execute([$new_quantity, $existing['id']]);
            } else {
                // Add new item
                $query = "INSERT INTO cart (user_id, meal_id, quantity) VALUES (?, ?, ?)";
                $stmt = $db->prepare($query);
                $stmt->execute([$_SESSION['user_id'], $meal_id, $quantity]);
            }
            
            echo json_encode(['success' => true, 'message' => 'Item added to cart']);
            break;
            
        case 'update':
            $cart_id = $input['cart_id'];
            $change = $input['change'];
            
            // Get current quantity
            $query = "SELECT quantity FROM cart WHERE id = ? AND user_id = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$cart_id, $_SESSION['user_id']]);
            $current = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($current) {
                $new_quantity = max(1, $current['quantity'] + $change);
                $query = "UPDATE cart SET quantity = ? WHERE id = ?";
                $stmt = $db->prepare($query);
                $stmt->execute([$new_quantity, $cart_id]);
                
                echo json_encode(['success' => true, 'message' => 'Cart updated']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Item not found']);
            }
            break;
            
        case 'remove':
            $cart_id = $input['cart_id'];
            
            $query = "DELETE FROM cart WHERE id = ? AND user_id = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$cart_id, $_SESSION['user_id']]);
            
            echo json_encode(['success' => true, 'message' => 'Item removed']);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>
