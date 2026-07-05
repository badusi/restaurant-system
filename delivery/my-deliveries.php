<?php
require_once '../config/database.php';
require_once '../config/session.php';

requireRole('delivery');

$database = new Database();
$db = $database->getConnection();

// Get my assigned orders
$query = "SELECT o.*, u.full_name as customer_name, u.phone as customer_phone, u.email as customer_email
          FROM orders o 
          JOIN users u ON o.user_id = u.id 
          WHERE o.delivery_person_id = ? AND o.status IN ('picked_up', 'out_for_delivery')
          ORDER BY o.pickup_time DESC";
$stmt = $db->prepare($query);
$stmt->execute([$_SESSION['user_id']]);
$my_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle delivery status update
if ($_POST && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $order_id = intval($_POST['order_id']);
    $new_status = $_POST['status'];
    
    $allowed_statuses = ['picked_up', 'out_for_delivery', 'delivered'];
    if (in_array($new_status, $allowed_statuses)) {
        $update_field = '';
        if ($new_status === 'delivered') {
            $update_field = ', delivered_time = CURRENT_TIMESTAMP';
        }
        
        $query = "UPDATE orders SET status = ? $update_field WHERE id = ? AND delivery_person_id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$new_status, $order_id, $_SESSION['user_id']]);
        
        header('Location: my-deliveries.php');
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🧑‍🍳 My Deliveries - Godswill</title>
    <link rel="stylesheet" href="../assets/css/style.css">c
    <style>
        /* Footer Styles */
        footer {
            background: linear-gradient(135deg, #023f05 0%, #31c205 100%);
            color: white;
            padding: 1rem 0;
            text-align: center;
            position: relative;
            bottom: 0;
            width: 100%;
        }

        footer p {
            margin: 0;
            font-size: 0.9rem;
        }

        /* Ensure the body has proper spacing */
        body {
            padding-bottom: 0; /* Remove if you had padding before */
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="container">
            <div class="logo">
                <h1>🧑‍🍳 Godswill - Delivery Portal</h1>
            </div>
            <nav class="nav">
                <span>Welcome, <?php echo $_SESSION['full_name']; ?> (Delivery Personnel)</span>
                <a href="../auth/logout.php">Logout</a>
            </nav>
        </div>
    </header>

    <div class="dashboard">
        <aside class="sidebar">
            <ul>
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="my-deliveries.php" class="active">My Deliveries</a></li>
                <li><a href="delivery-history.php">Delivery History</a></li>
            </ul>
        </aside>

        <main class="content">
            <h2>🚛 My Active Deliveries</h2>
            
            <div style="background: white; padding: 20px; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.1);">
                <?php if (empty($my_orders)): ?>
                    <div style="text-align: center; padding: 40px;">
                        <h3 style="color: #666;">📦 No Active Deliveries</h3>
                        <p style="color: #999;">You don't have any active deliveries at the moment.</p>
                        <a href="dashboard.php" class="btn btn-primary" style="margin-top: 20px;">
                            🚚 Check Available Orders
                        </a>
                    </div>
                <?php else: ?>
                    <div class="data-table">
                        <table>
                            <thead>
                                <tr>
                                    <th>Order Details</th>
                                    <th>Customer Info</th>
                                    <th>Delivery Address</th>
                                    <th>Status & Timing</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($my_orders as $order): ?>
                                    <tr>
                                        <td>
                                            <strong>Order #<?php echo $order['id']; ?></strong><br>
                                            <small>Amount: ₦<?php echo number_format($order['total_amount'], 2); ?></small><br>
                                            <small>Order Date: <?php echo date('M j, Y', strtotime($order['created_at'])); ?></small>
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($order['customer_name']); ?></strong><br>
                                            <small>📞 <?php echo htmlspecialchars($order['customer_phone']); ?></small><br>
                                            <small>✉️ <?php echo htmlspecialchars($order['customer_email']); ?></small>
                                        </td>
                                        <td>
                                            <div style="max-width: 200px;">
                                                <?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="status-badge status-<?php echo $order['status']; ?>">
                                                <?php echo ucfirst(str_replace('_', ' ', $order['status'])); ?>
                                            </span><br>
                                            <small><strong>Picked up:</strong><br><?php echo date('M j, g:i A', strtotime($order['pickup_time'])); ?></small><br>
                                            <small><strong>Est. Delivery:</strong><br><?php echo date('M j, g:i A', strtotime($order['estimated_delivery_time'])); ?></small>
                                        </td>
                                        <td>
                                            <div style="display: flex; flex-direction: column; gap: 5px;">
                                                <?php if ($order['status'] === 'picked_up'): ?>
                                                    <form method="POST" style="margin: 0;">
                                                        <input type="hidden" name="action" value="update_status">
                                                        <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                                        <input type="hidden" name="status" value="out_for_delivery">
                                                        <button type="submit" class="btn btn-secondary" style="width: 100%; font-size: 12px;">
                                                            🚛 Start Delivery
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                                
                                                <?php if ($order['status'] === 'out_for_delivery'): ?>
                                                    <form method="POST" style="margin: 0;">
                                                        <input type="hidden" name="action" value="update_status">
                                                        <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                                        <input type="hidden" name="status" value="delivered">
                                                        <button type="submit" class="btn" style="background: #27ae60; color: white; width: 100%; font-size: 12px;">
                                                            ✅ Mark Delivered
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                                
                                                <button onclick="callCustomer('<?php echo $order['customer_phone']; ?>')" class="btn" style="background: #3498db; color: white; width: 100%; font-size: 12px;">
                                                    📞 Call Customer
                                                </button>
                                                
                                                <button onclick="showOrderDetails(<?php echo $order['id']; ?>)" class="btn" style="background: #9b59b6; color: white; width: 100%; font-size: 12px;">
                                                    📋 View Details
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
              <!-- Footer -->
    <footer style="background: linear-gradient(135deg, #023f05 0%, #31c205 100%); color: white; padding: 1rem 0; text-align: center;">
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> Godswill Meal System. Developed by Badusi</p>
        </div>
    </footer>
    <script>
        function callCustomer(phone) {
            if (confirm('Call customer at ' + phone + '?')) {
                window.location.href = 'tel:' + phone;
            }
        }

        function showOrderDetails(orderId) {
            // You can implement a modal or redirect to order details page
            alert('Order details functionality can be implemented here for Order #' + orderId);
        }

        // Auto-refresh every 2 minutes to check for status updates
        setTimeout(function() {
            location.reload();
        }, 120000);
    </script>
</body>
</html>
