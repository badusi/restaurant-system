<?php
require_once '../config/database.php';
require_once '../config/session.php';

requireRole('delivery');

$database = new Database();
$db = $database->getConnection();

// Pagination
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Get total count
$count_query = "SELECT COUNT(*) as total FROM orders WHERE delivery_person_id = ? AND status = 'delivered'";
$count_stmt = $db->prepare($count_query);
$count_stmt->execute([$_SESSION['user_id']]);
$total_orders = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total_orders / $limit);

// Get completed deliveries with pagination
$query = "SELECT o.*, u.full_name as customer_name, u.phone as customer_phone
          FROM orders o 
          JOIN users u ON o.user_id = u.id 
          WHERE o.delivery_person_id = ? AND o.status = 'delivered'
          ORDER BY o.delivered_time DESC 
          LIMIT ? OFFSET ?";

$stmt = $db->prepare($query);

// Bind values properly (first parameter: 1-based position)
$stmt->bindValue(1, $_SESSION['user_id'], PDO::PARAM_INT);
$stmt->bindValue(2, $limit, PDO::PARAM_INT);
$stmt->bindValue(3, $offset, PDO::PARAM_INT);

$stmt->execute();
$completed_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);


// Get delivery statistics
$stats_query = "SELECT 
                COUNT(*) as total_deliveries,
                SUM(total_amount) as total_earnings,
                AVG(TIMESTAMPDIFF(MINUTE, pickup_time, delivered_time)) as avg_delivery_time
                FROM orders 
                WHERE delivery_person_id = ? AND status = 'delivered'";
$stats_stmt = $db->prepare($stats_query);
$stats_stmt->execute([$_SESSION['user_id']]);
$stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);

// Get monthly statistics
$monthly_query = "SELECT 
                  DATE_FORMAT(delivered_time, '%Y-%m') as month,
                  COUNT(*) as deliveries,
                  SUM(total_amount) as earnings
                  FROM orders 
                  WHERE delivery_person_id = ? AND status = 'delivered'
                  GROUP BY DATE_FORMAT(delivered_time, '%Y-%m')
                  ORDER BY month DESC
                  LIMIT 12";
$monthly_stmt = $db->prepare($monthly_query);
$monthly_stmt->execute([$_SESSION['user_id']]);
$monthly_stats = $monthly_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🧑‍🍳 Delivery History - Godswill</title>
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
                <li><a href="my-deliveries.php">My Deliveries</a></li>
                <li><a href="delivery-history.php" class="active">Delivery History</a></li>
            </ul>
        </aside>

        <main class="content">
            <h2>📊 Delivery History & Statistics</h2>
            
            <!-- Statistics Cards -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px;">
                <div style="background: linear-gradient(135deg, #3498db, #2980b9); color: white; padding: 20px; border-radius: 10px; text-align: center;">
                    <h3 style="margin: 0; font-size: 2.5em;"><?php echo $stats['total_deliveries'] ?: 0; ?></h3>
                    <p style="margin: 5px 0 0 0;">Total Deliveries</p>
                </div>
                <div style="background: linear-gradient(135deg, #27ae60, #229954); color: white; padding: 20px; border-radius: 10px; text-align: center;">
                    <h3 style="margin: 0; font-size: 2.5em;">₦<?php echo number_format($stats['total_earnings'] ?: 0, 2); ?></h3>
                    <p style="margin: 5px 0 0 0;">Total Order Value</p>
                </div>
                <div style="background: linear-gradient(135deg, #f39c12, #e67e22); color: white; padding: 20px; border-radius: 10px; text-align: center;">
                    <h3 style="margin: 0; font-size: 2.5em;"><?php echo round($stats['avg_delivery_time'] ?: 0); ?> min</h3>
                    <p style="margin: 5px 0 0 0;">Avg Delivery Time</p>
                </div>
            </div>

            <!-- Monthly Statistics -->
            <?php if (!empty($monthly_stats)): ?>
            <div style="background: white; padding: 20px; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); margin-bottom: 30px;">
                <h3>📈 Monthly Performance</h3>
                <div class="data-table">
                    <table>
                        <thead>
                            <tr>
                                <th>Month</th>
                                <th>Deliveries</th>
                                <th>Order Value</th>
                                <th>Avg per Delivery</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($monthly_stats as $month): ?>
                                <tr>
                                    <td><?php echo date('F Y', strtotime($month['month'] . '-01')); ?></td>
                                    <td><?php echo $month['deliveries']; ?></td>
                                    <td>₦<?php echo number_format($month['earnings'], 2); ?></td>
                                    <td>₦<?php echo number_format($month['earnings'] / $month['deliveries'], 2); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

            <!-- Delivery History -->
            <div style="background: white; padding: 20px; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.1);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h3>✅ Completed Deliveries (<?php echo $total_orders; ?> total)</h3>
                    
                    <!-- Pagination Info -->
                    <?php if ($total_pages > 1): ?>
                        <div>
                            Page <?php echo $page; ?> of <?php echo $total_pages; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if (empty($completed_orders)): ?>
                    <div style="text-align: center; padding: 40px;">
                        <h3 style="color: #666;">📦 No Completed Deliveries</h3>
                        <p style="color: #999;">You haven't completed any deliveries yet.</p>
                        <a href="dashboard.php" class="btn btn-primary" style="margin-top: 20px;">
                            🚚 Start Delivering
                        </a>
                    </div>
                <?php else: ?>
                    <div class="data-table">
                        <table>
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Customer</th>
                                    <th>Phone</th>
                                    <th>Amount</th>
                                    <th>Pickup Time</th>
                                    <th>Delivered Time</th>
                                    <th>Delivery Duration</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($completed_orders as $order): ?>
                                    <tr>
                                        <td>#<?php echo $order['id']; ?></td>
                                        <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                        <td><?php echo htmlspecialchars($order['customer_phone']); ?></td>
                                        <td>₦<?php echo number_format($order['total_amount'], 2); ?></td>
                                        <td><?php echo date('M j, g:i A', strtotime($order['pickup_time'])); ?></td>
                                        <td><?php echo date('M j, g:i A', strtotime($order['delivered_time'])); ?></td>
                                        <td>
                                            <?php 
                                            $pickup = new DateTime($order['pickup_time']);
                                            $delivered = new DateTime($order['delivered_time']);
                                            $duration = $pickup->diff($delivered);
                                            
                                            if ($duration->h > 0) {
                                                echo $duration->h . 'h ' . $duration->i . 'm';
                                            } else {
                                                echo $duration->i . ' minutes';
                                            }
                                            ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <div style="display: flex; justify-content: center; align-items: center; gap: 10px; margin-top: 20px;">
                            <?php if ($page > 1): ?>
                                <a href="?page=<?php echo $page - 1; ?>" class="btn btn-secondary">← Previous</a>
                            <?php endif; ?>
                            
                            <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                <a href="?page=<?php echo $i; ?>" class="btn <?php echo $i === $page ? 'btn-primary' : 'btn-secondary'; ?>">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>
                            
                            <?php if ($page < $total_pages): ?>
                                <a href="?page=<?php echo $page + 1; ?>" class="btn btn-secondary">Next →</a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
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