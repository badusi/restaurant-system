<?php
require_once '../config/database.php';
require_once '../config/session.php';

requireRole('admin');

$database = new Database();
$db = $database->getConnection();

// Get statistics
$stats = [];

// Total users
$query = "SELECT COUNT(*) as count FROM users WHERE role = 'user'";
$stmt = $db->prepare($query);
$stmt->execute();
$stats['users'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

// Total staff
$query = "SELECT COUNT(*) as count FROM users WHERE role IN ('staff_goods', 'staff_cs', 'delivery')";
$stmt = $db->prepare($query);
$stmt->execute();
$stats['staff'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

// Total orders
$query = "SELECT COUNT(*) as count FROM orders";
$stmt = $db->prepare($query);
$stmt->execute();
$stats['orders'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

// Total revenue
$query = "SELECT SUM(total_amount) as total FROM orders WHERE payment_status = 'completed'";
$stmt = $db->prepare($query);
$stmt->execute();
$stats['revenue'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

// Recent orders
$query = "SELECT o.*, u.full_name as customer_name 
          FROM orders o 
          JOIN users u ON o.user_id = u.id 
          ORDER BY o.created_at DESC 
          LIMIT 10";
$stmt = $db->prepare($query);
$stmt->execute();
$recent_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🧑‍🍳 Admin Dashboard - Godswill</title>
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
                <li><a href="dashboard.php" class="active">Dashboard</a></li>
                <li><a href="users.php">Users</a></li>
                <li><a href="staff.php">Staff</a></li>
                <li><a href="staff-approvals.php">Applicants</a></li>
                <li><a href="orders.php">Orders</a></li>
                <li><a href="transactions.php">Transactions</a></li>
                <li><a href="chats.php">Customer Chats</a></li>
                <li><a href="reports.php">Reports</a></li>
            </ul>
        </aside>

        <main class="content">
            <h2>Admin Dashboard</h2>
            
            <!-- Statistics Cards -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 40px;">
                <div style="background: white; padding: 30px; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); text-align: center;">
                    <h3 style="color: #3498db; font-size: 2rem; margin-bottom: 10px;"><?php echo $stats['users']; ?></h3>
                    <p style="color: #666;">Total Users</p>
                </div>
                <div style="background: white; padding: 30px; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); text-align: center;">
                    <h3 style="color: #27ae60; font-size: 2rem; margin-bottom: 10px;"><?php echo $stats['staff']; ?></h3>
                    <p style="color: #666;">Total Staff</p>
                </div>
                <div style="background: white; padding: 30px; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); text-align: center;">
                    <h3 style="color: #f39c12; font-size: 2rem; margin-bottom: 10px;"><?php echo $stats['orders']; ?></h3>
                    <p style="color: #666;">Total Orders</p>
                </div>
                <div style="background: white; padding: 30px; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); text-align: center;">
                    <h3 style="color: #e74c3c; font-size: 2rem; margin-bottom: 10px;">₦<?php echo number_format($stats['revenue'], 2); ?></h3>
                    <p style="color: #666;">Total Revenue</p>
                </div>
            </div>

            <!-- Recent Orders -->
            <div style="background: white; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); overflow: hidden;">
                <div style="padding: 20px; border-bottom: 1px solid #eee;">
                    <h3>Recent Orders</h3>
                </div>
                <div class="data-table">
                    <table>
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Payment</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_orders as $order): ?>
                                <tr>
                                    <td>#<?php echo $order['id']; ?></td>
                                    <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
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
