<?php
require_once '../config/database.php';
require_once '../config/session.php';

requireRole('admin');

$database = new Database();
$db = $database->getConnection();

$user_id = $_GET['id'] ?? 0;

// Get user details
$query = "SELECT * FROM users WHERE id = ? AND role = 'user'";
$stmt = $db->prepare($query);
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    header('Location: users.php');
    exit();
}

// Get user orders
$query = "SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC";
$stmt = $db->prepare($query);
$stmt->execute([$user_id]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get user chat conversations
$query = "SELECT cc.*, s.full_name as staff_name 
          FROM chat_conversations cc 
          LEFT JOIN users s ON cc.staff_id = s.id 
          WHERE cc.user_id = ? 
          ORDER BY cc.created_at DESC";
$stmt = $db->prepare($query);
$stmt->execute([$user_id]);
$conversations = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate statistics
$total_spent = array_sum(array_column(array_filter($orders, function($o) { return $o['payment_status'] === 'completed'; }), 'total_amount'));
$total_orders = count($orders);
$completed_orders = count(array_filter($orders, function($o) { return $o['payment_status'] === 'completed'; }));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🧑‍🍳 User Details - Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .content{
            overflow-y: scroll;
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
                <h1>🧑‍🍳 Godswill - Admin Panel</h1>
            </div>
            <nav class="nav">
                <span>Welcome, <?php echo $_SESSION['full_name']; ?> (Administrator)</span>
                <a href="../auth/logout.php">Logout</a>
            </nav>
        </div>
    </header>

    <div class="dashboard">
        <aside class="sidebar">
            <ul>
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="users.php" class="active">Users</a></li>
                <li><a href="staff.php">Staff</a></li>
                <li><a href="orders.php">Orders</a></li>
                <li><a href="transactions.php">Transactions</a></li>
                <li><a href="chats.php">Customer Chats</a></li>
                <li><a href="reports.php">Reports</a></li>
            </ul>
        </aside>

        <main class="content">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
                <h2>User Details: <?php echo htmlspecialchars($user['full_name']); ?></h2>
                <a href="users.php" class="btn btn-secondary">Back to Users</a>
            </div>
            
            <!-- User Information -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-bottom: 30px;">
                <div style="background: white; padding: 20px; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.1);">
                    <h3>Personal Information</h3>
                    <div style="margin-top: 20px;">
                        <p><strong>Full Name:</strong> <?php echo htmlspecialchars($user['full_name']); ?></p>
                        <p><strong>Username:</strong> <?php echo htmlspecialchars($user['username']); ?></p>
                        <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
                        <p><strong>Phone:</strong> <?php echo htmlspecialchars($user['phone'] ?: 'Not provided'); ?></p>
                        <p><strong>Address:</strong> <?php echo $user['address'] ? nl2br(htmlspecialchars($user['address'])) : 'Not provided'; ?></p>
                        <p><strong>Member Since:</strong> <?php echo date('F j, Y', strtotime($user['created_at'])); ?></p>
                        <p><strong>Last Updated:</strong> <?php echo date('F j, Y g:i A', strtotime($user['updated_at'])); ?></p>
                    </div>
                </div>

                <div style="background: white; padding: 20px; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.1);">
                    <h3>Account Statistics</h3>
                    <div style="margin-top: 20px;">
                        <div style="display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #eee;">
                            <span>Total Orders:</span>
                            <span style="font-weight: bold; color: #3498db;"><?php echo $total_orders; ?></span>
                        </div>
                        <div style="display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #eee;">
                            <span>Completed Orders:</span>
                            <span style="font-weight: bold; color: #27ae60;"><?php echo $completed_orders; ?></span>
                        </div>
                        <div style="display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #eee;">
                            <span>Total Spent:</span>
                            <span style="font-weight: bold; color: #e74c3c;">₦<?php echo number_format($total_spent, 2); ?></span>
                        </div>
                        <div style="display: flex; justify-content: space-between; padding: 10px 0;">
                            <span>Support Conversations:</span>
                            <span style="font-weight: bold; color: #f39c12;"><?php echo count($conversations); ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Order History -->
            <div style="background: white; padding: 20px; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); margin-bottom: 30px;">
                <h3>Order History</h3>
                <?php if (empty($orders)): ?>
                    <p style="text-align: center; color: #666; padding: 20px;">No orders found.</p>
                <?php else: ?>
                    <div class="data-table">
                        <table>
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Payment</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orders as $order): ?>
                                    <tr>
                                        <td>#<?php echo $order['id']; ?></td>
                                        <td>₦<?php echo number_format($order['total_amount'], 2); ?></td>
                                        <td>
                                            <span class="status-badge status-<?php echo $order['status']; ?>">
                                                <?php echo ucfirst($order['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="status-badge status-<?php echo $order['payment_status']; ?>">
                                                <?php echo ucfirst($order['payment_status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('M j, Y', strtotime($order['created_at'])); ?></td>
                                        <td>
                                            <a href="order-details.php?id=<?php echo $order['id']; ?>" class="btn btn-secondary">View</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Support Conversations -->
            <div style="background: white; padding: 20px; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.1);">
                <h3>Support Conversations</h3>
                <?php if (empty($conversations)): ?>
                    <p style="text-align: center; color: #666; padding: 20px;">No support conversations found.</p>
                <?php else: ?>
                    <div class="data-table">
                        <table>
                            <thead>
                                <tr>
                                    <th>Conversation ID</th>
                                    <th>Subject</th>
                                    <th>Assigned Staff</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($conversations as $conv): ?>
                                    <tr>
                                        <td>#<?php echo $conv['id']; ?></td>
                                        <td><?php echo htmlspecialchars($conv['subject']); ?></td>
                                        <td><?php echo $conv['staff_name'] ? htmlspecialchars($conv['staff_name']) : 'Unassigned'; ?></td>
                                        <td>
                                            <span class="status-badge status-<?php echo $conv['status']; ?>">
                                                <?php echo ucfirst($conv['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('M j, Y', strtotime($conv['created_at'])); ?></td>
                                        <td>
                                            <a href="chat-details.php?id=<?php echo $conv['id']; ?>" class="btn btn-secondary">View Chat</a>
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
</body>
</html>
