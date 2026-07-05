<?php
require_once '../config/database.php';
require_once '../config/session.php';

requireRole('user');

$database = new Database();
$db = $database->getConnection();

$order_id = $_GET['id'] ?? 0;

// Get order details
$query = "SELECT * FROM orders WHERE id = ? AND user_id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$order_id, $_SESSION['user_id']]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    header('Location: orders.php');
    exit();
}

// Get order items
$query = "SELECT oi.*, m.name, m.description, m.image_url 
          FROM order_items oi 
          JOIN meals m ON oi.meal_id = m.id 
          WHERE oi.order_id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$order_id]);
$order_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get transaction details
$query = "SELECT * FROM transactions WHERE order_id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$order_id]);
$transaction = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🧑‍🍳 Order Details - Godswill</title>
    <link rel="stylesheet" href="../assets/css/style.css">
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
                <h1>🧑‍🍳 Godswill</h1>
            </div>
            <nav class="nav">
                <span>Welcome, <?php echo $_SESSION['full_name']; ?></span>
                <a href="dashboard.php">🍴 Dashboard</a>
                <a href="cart.php">🧺 Meal Cart</a>
                <a href="orders.php">Orders</a>
                <a href="../auth/logout.php">Logout</a>
            </nav>
        </div>
    </header>

    <div class="dashboard">
        <aside class="sidebar">
            <ul>
                <li><a href="dashboard.php">🍴 Dashboard</a></li>
                <li><a href="cart.php">Shopping Cart</a></li>
                <li><a href="orders.php" class="active">My Orders</a></li>
                <li><a href="chat.php">Customer Support</a></li>
                <li><a href="profile.php">Profile</a></li>
            </ul>
        </aside>

        <main class="content">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
                <h2>Order #<?php echo $order['id']; ?></h2>
                <a href="orders.php" class="btn btn-secondary">Back to Orders</a>
            </div>
            
            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 30px;">
                <!-- Order Items -->
                <div>
                    <div style="background: white; padding: 20px; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); margin-bottom: 20px;">
                        <h3>Order Items</h3>
                        <?php foreach ($order_items as $item): ?>
                            <div style="display: flex; align-items: center; padding: 15px 0; border-bottom: 1px solid #eee;">
                                <div style="width: 60px; height: 60px; background: #f0f0f0; border-radius: 5px; margin-right: 15px; display: flex; align-items: center; justify-content: center;">
                                    <?php if ($item['image_url']): ?>
                                        <img src="<?php echo htmlspecialchars($item['image_url']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" style="width: 100%; height: 100%; object-fit: cover; border-radius: 5px;">
                                    <?php else: ?>
                                        <span style="color: #999; font-size: 0.8rem;">No Image</span>
                                    <?php endif; ?>
                                </div>
                                <div style="flex: 1;">
                                    <h4 style="margin-bottom: 5px;"><?php echo htmlspecialchars($item['name']); ?></h4>
                                    <p style="color: #666; margin-bottom: 5px;"><?php echo htmlspecialchars($item['description']); ?></p>
                                    <p style="font-weight: bold;">Quantity: <?php echo $item['quantity']; ?> × ₦<?php echo number_format($item['price'], 2); ?></p>
                                </div>
                                <div style="text-align: right;">
                                    <p style="font-weight: bold; color: #e74c3c;">₦<?php echo number_format($item['price'] * $item['quantity'], 2); ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Shipping Address -->
                    <div style="background: white; padding: 20px; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.1);">
                        <h3>Shipping Address</h3>
                        <p><?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?></p>
                    </div>
                </div>

                <!-- Order Summary -->
                <div>
                    <div style="background: white; padding: 20px; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); margin-bottom: 20px;">
                        <h3>Order Summary</h3>
                        <div style="margin-bottom: 15px;">
                            <p><strong>Order Date:</strong> <?php echo date('M j, Y g:i A', strtotime($order['created_at'])); ?></p>
                            <p><strong>Status:</strong> 
                                <span class="status-badge status-<?php echo $order['status']; ?>">
                                    <?php echo ucfirst($order['status']); ?>
                                </span>
                            </p>
                            <p><strong>Payment Status:</strong> 
                                <span class="status-badge status-<?php echo $order['payment_status']; ?>">
                                    <?php echo ucfirst($order['payment_status']); ?>
                                </span>
                            </p>
                            <p><strong>Payment Method:</strong> <?php echo ucwords(str_replace('_', ' ', $order['payment_method'])); ?></p>
                            <?php if ($order['status'] === 'shipped'): ?>
                                <p><strong>Estimated Delivery:</strong> <?php echo $order['estimated_delivery_days']; ?> days</p>
                            <?php endif; ?>
                        </div>
                        <div style="border-top: 2px solid #3498db; padding-top: 15px;">
                            <p style="display: flex; justify-content: space-between; font-size: 1.2rem; font-weight: bold;">
                                <span>Total:</span>
                                <span style="color: #e74c3c;">₦<?php echo number_format($order['total_amount'], 2); ?></span>
                            </p>
                        </div>
                    </div>

                    <!-- Transaction Details -->
                    <?php if ($transaction): ?>
                        <div style="background: white; padding: 20px; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.1);">
                            <h3>Transaction Details</h3>
                            <p><strong>Transaction ID:</strong> <?php echo htmlspecialchars($transaction['transaction_id']); ?></p>
                            <p><strong>Amount:</strong> ₦<?php echo number_format($transaction['amount'], 2); ?></p>
                            <p><strong>Status:</strong> 
                                <span class="status-badge status-<?php echo $transaction['status']; ?>">
                                    <?php echo ucfirst($transaction['status']); ?>
                                </span>
                            </p>
                            <p><strong>Date:</strong> <?php echo date('M j, Y g:i A', strtotime($transaction['created_at'])); ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
          <!-- Footer -->
    <footer style="background: linear-gradient(135deg, #023f05 0%, #31c205 100%); color: white; padding: 1rem 0; text-align: center;">
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> Godswill Meal System. Developed by Badusi</p>
        </div>
    </footer>
</body>
</html>
