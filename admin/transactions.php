<?php
require_once '../config/database.php';
require_once '../config/session.php';

requireRole('admin');

$database = new Database();
$db = $database->getConnection();

// Get all transactions with order and user details
$query = "SELECT t.*, o.total_amount as order_amount, u.full_name as customer_name, u.email as customer_email
          FROM transactions t 
          JOIN orders o ON t.order_id = o.id 
          JOIN users u ON o.user_id = u.id 
          ORDER BY t.created_at DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get transaction statistics
$stats = [];

// Total transactions
$stats['total_transactions'] = count($transactions);

// Successful transactions
$successful = array_filter($transactions, function($t) { return $t['status'] === 'success'; });
$stats['successful_transactions'] = count($successful);

// Total revenue
$stats['total_revenue'] = array_sum(array_column($successful, 'amount'));

// Failed transactions
$stats['failed_transactions'] = $stats['total_transactions'] - $stats['successful_transactions'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🧑‍🍳 Transactions - Admin</title>
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
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="users.php">Users</a></li>
                <li><a href="staff.php">Staff</a></li>
                <li><a href="orders.php">Orders</a></li>
                <li><a href="transactions.php" class="active">Transactions</a></li>
                <li><a href="chats.php">Customer Chats</a></li>
                <li><a href="reports.php">Reports</a></li>
            </ul>
        </aside>

        <main class="content">
            <h2>Transaction Management</h2>
            
            <!-- Statistics Cards -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px;">
                <div style="background: white; padding: 20px; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); text-align: center;">
                    <h3 style="color: #3498db; font-size: 2rem; margin-bottom: 10px;"><?php echo $stats['total_transactions']; ?></h3>
                    <p style="color: #666;">Total Transactions</p>
                </div>
                <div style="background: white; padding: 20px; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); text-align: center;">
                    <h3 style="color: #27ae60; font-size: 2rem; margin-bottom: 10px;"><?php echo $stats['successful_transactions']; ?></h3>
                    <p style="color: #666;">Successful</p>
                </div>
                <div style="background: white; padding: 20px; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); text-align: center;">
                    <h3 style="color: #e74c3c; font-size: 2rem; margin-bottom: 10px;"><?php echo $stats['failed_transactions']; ?></h3>
                    <p style="color: #666;">Failed</p>
                </div>
                <div style="background: white; padding: 20px; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); text-align: center;">
                    <h3 style="color: #f39c12; font-size: 2rem; margin-bottom: 10px;">₦<?php echo number_format($stats['total_revenue'], 2); ?></h3>
                    <p style="color: #666;">Total Revenue</p>
                </div>
            </div>
            
            <div style="background: white; padding: 20px; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.1);">
                <h3>All Transactions</h3>
                
                <div class="data-table">
                    <table>
                        <thead>
                            <tr>
                                <th>Transaction ID</th>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>Amount</th>
                                <th>Gateway</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($transactions as $transaction): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($transaction['transaction_id']); ?></td>
                                    <td>#<?php echo $transaction['order_id']; ?></td>
                                    <td>
                                        <?php echo htmlspecialchars($transaction['customer_name']); ?>
                                        <br><small style="color: #666;"><?php echo htmlspecialchars($transaction['customer_email']); ?></small>
                                    </td>
                                    <td>₦<?php echo number_format($transaction['amount'], 2); ?></td>
                                    <td><?php echo ucwords(str_replace('_', ' ', $transaction['payment_gateway'])); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo $transaction['status']; ?>">
                                            <?php echo ucfirst($transaction['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M j, Y g:i A', strtotime($transaction['created_at'])); ?></td>
                                    <td>
                                        <a href="order-details.php?id=<?php echo $transaction['order_id']; ?>" class="btn btn-secondary">View Order</a>
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
            <p>&copy; <?php echo date('Y'); ?> Godswill Meal System. Developed by Kayode Ilesanmi, Lawal Fawas and Areo David</p>
        </div>
    </footer>
</body>
</html>
