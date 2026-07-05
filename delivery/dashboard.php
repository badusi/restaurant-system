<?php
require_once '../config/database.php';
require_once '../config/session.php';

requireRole('delivery');

$database = new Database();
$db = $database->getConnection();

// Get available orders for pickup (orders that are paid and processed)
$query = "SELECT o.*, u.full_name as customer_name, u.phone as customer_phone 
          FROM orders o 
          JOIN users u ON o.user_id = u.id 
          WHERE o.status IN ('processing', 'ready_for_pickup') 
          AND o.payment_status = 'completed' 
          AND o.delivery_person_id IS NULL
          ORDER BY o.created_at ASC";
$stmt = $db->prepare($query);
$stmt->execute();
$available_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get my assigned orders
$query = "SELECT o.*, u.full_name as customer_name, u.phone as customer_phone 
          FROM orders o 
          JOIN users u ON o.user_id = u.id 
          WHERE o.delivery_person_id = ? AND o.status IN ('picked_up', 'out_for_delivery')
          ORDER BY o.pickup_time DESC";
$stmt = $db->prepare($query);
$stmt->execute([$_SESSION['user_id']]);
$my_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get completed deliveries
$query = "SELECT o.*, u.full_name as customer_name 
          FROM orders o 
          JOIN users u ON o.user_id = u.id 
          WHERE o.delivery_person_id = ? AND o.status = 'delivered'
          ORDER BY o.delivered_time DESC LIMIT 10";
$stmt = $db->prepare($query);
$stmt->execute([$_SESSION['user_id']]);
$completed_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle order pickup
if ($_POST && isset($_POST['action']) && $_POST['action'] === 'pickup') {
    $order_id = intval($_POST['order_id']);
    $estimated_minutes = intval($_POST['estimated_minutes']);
    
    $estimated_delivery_time = date('Y-m-d H:i:s', strtotime("+$estimated_minutes minutes"));
    
    $query = "UPDATE orders SET 
              delivery_person_id = ?, 
              status = 'picked_up', 
              pickup_time = CURRENT_TIMESTAMP,
              estimated_delivery_time = ?
              WHERE id = ? AND status IN ('processing', 'ready_for_pickup')";
    $stmt = $db->prepare($query);
    $stmt->execute([$_SESSION['user_id'], $estimated_delivery_time, $order_id]);
    
    header('Location: dashboard.php');
    exit();
}

// Handle delivery completion
if ($_POST && isset($_POST['action']) && $_POST['action'] === 'deliver') {
    $order_id = intval($_POST['order_id']);
    
    $query = "UPDATE orders SET 
              status = 'delivered', 
              delivered_time = CURRENT_TIMESTAMP
              WHERE id = ? AND delivery_person_id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$order_id, $_SESSION['user_id']]);
    
    header('Location: dashboard.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🧑‍🍳 Delivery Dashboard - Godswill</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .dashboard{
            overflow-y: scroll;
            background-color: #fff;
        }
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
                <li><a href="dashboard.php" class="active">Dashboard</a></li>
                <li><a href="my-deliveries.php">My Deliveries</a></li>
                <li><a href="delivery-history.php">Delivery History</a></li>
            </ul>
        </aside>

        <main class="content">
            <h2>🚚 Delivery Dashboard</h2>
            
            <!-- Debug Info -->
            <div style="background: #f8f9fa; padding: 15px; border-radius: 5px; margin-bottom: 20px; border-left: 4px solid #007bff;">
                <h4>📊 System Status</h4>
                <p><strong>Available Orders:</strong> <?php echo count($available_orders); ?></p>
                <p><strong>My Active Deliveries:</strong> <?php echo count($my_orders); ?></p>
                <p><strong>Recent Completions:</strong> <?php echo count($completed_orders); ?></p>
                
                <?php
                // Show all orders for debugging
                $debug_query = "SELECT id, status, payment_status, delivery_person_id, total_amount FROM orders ORDER BY created_at DESC LIMIT 5";
                $debug_stmt = $db->prepare($debug_query);
                $debug_stmt->execute();
                $debug_orders = $debug_stmt->fetchAll(PDO::FETCH_ASSOC);
                ?>
                <details style="margin-top: 10px;">
                    <summary style="cursor: pointer; font-weight: bold;">🔍 Recent Orders Debug Info</summary>
                    <div style="margin-top: 10px; font-family: monospace; font-size: 12px;">
                        <?php foreach ($debug_orders as $order): ?>
                            <div style="padding: 5px; border-bottom: 1px solid #ddd;">
                                Order #<?php echo $order['id']; ?> - 
                                Status: <strong><?php echo $order['status']; ?></strong> - 
                                Payment: <strong><?php echo $order['payment_status']; ?></strong> - 
                                Delivery Person: <?php echo $order['delivery_person_id'] ?: 'None'; ?> - 
                                Amount: ₦<?php echo $order['total_amount']; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </details>
            </div>
            
            <!-- Available Orders for Pickup -->
            <div style="background: white; padding: 20px; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); margin-bottom: 30px;">
                <h3>📦 Available Orders for Pickup (<?php echo count($available_orders); ?>)</h3>
                <?php if (empty($available_orders)): ?>
                    <div style="text-align: center; color: #666; padding: 20px; background: #f8f9fa; border-radius: 5px;">
                        <p><strong>No orders available for pickup at the moment.</strong></p>
                        <p style="font-size: 14px; margin-top: 10px;">Orders will appear here when they are:</p>
                        <ul style="list-style: none; padding: 0; margin: 10px 0;">
                            <li>✅ Payment completed</li>
                            <li>✅ Status: Processing or Ready for Pickup</li>
                            <li>✅ No delivery person assigned</li>
                        </ul>
                    </div>
                <?php else: ?>
                    <div class="data-table">
                        <table>
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Customer</th>
                                    <th>Phone</th>
                                    <th>Address</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Order Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($available_orders as $order): ?>
                                    <tr>
                                        <td>#<?php echo $order['id']; ?></td>
                                        <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                        <td><?php echo htmlspecialchars($order['customer_phone']); ?></td>
                                        <td><?php echo htmlspecialchars($order['shipping_address']); ?></td>
                                        <td>₦<?php echo number_format($order['total_amount'], 2); ?></td>
                                        <td>
                                            <span class="status-badge status-<?php echo $order['status']; ?>">
                                                <?php echo ucfirst(str_replace('_', ' ', $order['status'])); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('M j, Y g:i A', strtotime($order['created_at'])); ?></td>
                                        <td>
                                            <button onclick="pickupOrder(<?php echo $order['id']; ?>)" class="btn btn-primary">
                                                🚚 Pickup Order
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <!-- My Current Deliveries -->
            <div style="background: white; padding: 20px; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); margin-bottom: 30px;">
                <h3>🚛 My Current Deliveries (<?php echo count($my_orders); ?>)</h3>
                <?php if (empty($my_orders)): ?>
                    <p style="text-align: center; color: #666; padding: 20px;">No active deliveries assigned.</p>
                <?php else: ?>
                    <div class="data-table">
                        <table>
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Customer</th>
                                    <th>Phone</th>
                                    <th>Address</th>
                                    <th>Status</th>
                                    <th>Pickup Time</th>
                                    <th>Est. Delivery</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($my_orders as $order): ?>
                                    <tr>
                                        <td>#<?php echo $order['id']; ?></td>
                                        <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                        <td><?php echo htmlspecialchars($order['customer_phone']); ?></td>
                                        <td><?php echo htmlspecialchars($order['shipping_address']); ?></td>
                                        <td>
                                            <span class="status-badge status-<?php echo $order['status']; ?>">
                                                <?php echo ucfirst(str_replace('_', ' ', $order['status'])); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('M j, g:i A', strtotime($order['pickup_time'])); ?></td>
                                        <td><?php echo date('M j, g:i A', strtotime($order['estimated_delivery_time'])); ?></td>
                                        <td>
                                            <?php if ($order['status'] === 'picked_up'): ?>
                                                <button onclick="startDelivery(<?php echo $order['id']; ?>)" class="btn btn-secondary" style="margin-right: 5px;">
                                                    🚛 Start Delivery
                                                </button>
                                            <?php endif; ?>
                                            <?php if ($order['status'] === 'out_for_delivery'): ?>
                                                <button onclick="completeDelivery(<?php echo $order['id']; ?>)" class="btn" style="background: #27ae60; color: white;">
                                                    ✅ Mark Delivered
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Recent Completed Deliveries -->
            <div style="background: white; padding: 20px; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.1);">
                <h3>✅ Recent Completed Deliveries</h3>
                <?php if (empty($completed_orders)): ?>
                    <p style="text-align: center; color: #666; padding: 20px;">No completed deliveries yet.</p>
                <?php else: ?>
                    <div class="data-table">
                        <table>
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Customer</th>
                                    <th>Amount</th>
                                    <th>Delivered Time</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($completed_orders as $order): ?>
                                    <tr>
                                        <td>#<?php echo $order['id']; ?></td>
                                        <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                        <td>₦<?php echo number_format($order['total_amount'], 2); ?></td>
                                        <td><?php echo date('M j, Y g:i A', strtotime($order['delivered_time'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Pickup Modal -->
    <div id="pickupModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000;">
        <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 30px; border-radius: 10px; max-width: 400px; width: 90%;">
            <h3>🚚 Pickup Order</h3>
            <form method="POST" id="pickupForm">
                <input type="hidden" name="action" value="pickup">
                <input type="hidden" name="order_id" id="pickupOrderId">
                
                <div class="form-group">
                    <label for="estimated_minutes">Estimated delivery time (minutes):</label>
                    <select name="estimated_minutes" id="estimated_minutes" required>
                        <option value="30">30 minutes</option>
                        <option value="45">45 minutes</option>
                        <option value="60">1 hour</option>
                        <option value="90">1.5 hours</option>
                        <option value="120">2 hours</option>
                        <option value="180">3 hours</option>
                    </select>
                </div>
                
                <div style="display: flex; gap: 15px; margin-top: 20px;">
                    <button type="submit" class="btn btn-primary">Confirm Pickup</button>
                    <button type="button" onclick="closePickupModal()" class="btn btn-secondary">Cancel</button>
                </div>
            </form>
        </div>
    </div>
              <!-- Footer -->
    <footer style="background: linear-gradient(135deg, #023f05 0%, #31c205 100%); color: white; padding: 1rem 0; text-align: center;">
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> Godswill Meal System. Developed by Kayode Ilesanmi, Lawal Fawas and Areo David</p>
        </div>
    </footer>
    <script>
        function pickupOrder(orderId) {
            document.getElementById('pickupOrderId').value = orderId;
            document.getElementById('pickupModal').style.display = 'block';
        }

        function closePickupModal() {
            document.getElementById('pickupModal').style.display = 'none';
        }

        function startDelivery(orderId) {
            if (confirm('Are you sure you want to start delivery for this order?')) {
                fetch('../api/delivery.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'start_delivery',
                        order_id: orderId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                });
            }
        }

        function completeDelivery(orderId) {
            if (confirm('Are you sure you want to mark this order as delivered?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="deliver">
                    <input type="hidden" name="order_id" value="${orderId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Close modal when clicking outside
        document.getElementById('pickupModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closePickupModal();
            }
        });

        // Auto refresh every 30 seconds
        setTimeout(function() {
            location.reload();
        }, 30000);
    </script>
</body>
</html>