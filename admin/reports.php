<?php
require_once '../config/database.php';
require_once '../config/session.php';

requireRole('admin');

$database = new Database();
$db = $database->getConnection();

// Get comprehensive statistics
$stats = [];

// User statistics
$query = "SELECT COUNT(*) as count FROM users WHERE role = 'user'";
$stmt = $db->prepare($query);
$stmt->execute();
$stats['total_users'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

// Staff statistics
$query = "SELECT COUNT(*) as count FROM users WHERE role IN ('staff_goods', 'staff_cs')";
$stmt = $db->prepare($query);
$stmt->execute();
$stats['total_staff'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

// meal statistics
$query = "SELECT COUNT(*) as count FROM meals WHERE status = 'active'";
$stmt = $db->prepare($query);
$stmt->execute();
$stats['active_meals'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

// Order statistics
$query = "SELECT COUNT(*) as count, SUM(total_amount) as revenue FROM orders WHERE payment_status = 'completed'";
$stmt = $db->prepare($query);
$stmt->execute();
$order_stats = $stmt->fetch(PDO::FETCH_ASSOC);
$stats['completed_orders'] = $order_stats['count'];
$stats['total_revenue'] = $order_stats['revenue'] ?? 0;

// Handle period selection
$period = $_GET['period'] ?? 'month';
$selected_date = $_GET['selected_date'] ?? date('Y-m-d');

// Initialize variables
$period_data = [];
$top_meals_period = [];

if ($period === 'day') {
    // Get revenue for selected day
    $query = "SELECT SUM(total_amount) as revenue FROM orders WHERE DATE(created_at) = ? AND payment_status = 'completed'";
    $stmt = $db->prepare($query);
    $stmt->execute([$selected_date]);
    $revenue = $stmt->fetch(PDO::FETCH_ASSOC)['revenue'] ?? 0;
    $period_data[] = [
        'period' => date('M j, Y', strtotime($selected_date)),
        'revenue' => $revenue
    ];

    // Get top meals for selected day
    $query = "SELECT p.name, SUM(oi.quantity) as total_sold, SUM(oi.quantity * oi.price) as revenue
              FROM order_items oi 
              JOIN meals p ON oi.meal_id = p.id 
              JOIN orders o ON oi.order_id = o.id 
              WHERE DATE(o.created_at) = ? AND o.payment_status = 'completed'
              GROUP BY p.id, p.name 
              ORDER BY total_sold DESC 
              LIMIT 10";
    $stmt = $db->prepare($query);
    $stmt->execute([$selected_date]);
    $top_meals_period = $stmt->fetchAll(PDO::FETCH_ASSOC);

} elseif ($period === 'month') {
    // Monthly revenue (last 6 months)
    for ($i = 5; $i >= 0; $i--) {
        $month = date('Y-m', strtotime("-$i months"));
        $query = "SELECT SUM(total_amount) as revenue FROM orders WHERE DATE_FORMAT(created_at, '%Y-%m') = ? AND payment_status = 'completed'";
        $stmt = $db->prepare($query);
        $stmt->execute([$month]);
        $revenue = $stmt->fetch(PDO::FETCH_ASSOC)['revenue'] ?? 0;
        $period_data[] = [
            'period' => date('M Y', strtotime($month . '-01')),
            'revenue' => $revenue
        ];
    }

    // Get top meals for current month
    $current_month = date('Y-m');
    $query = "SELECT p.name, SUM(oi.quantity) as total_sold, SUM(oi.quantity * oi.price) as revenue
              FROM order_items oi 
              JOIN meals p ON oi.meal_id = p.id 
              JOIN orders o ON oi.order_id = o.id 
              WHERE DATE_FORMAT(o.created_at, '%Y-%m') = ? AND o.payment_status = 'completed'
              GROUP BY p.id, p.name 
              ORDER BY total_sold DESC 
              LIMIT 10";
    $stmt = $db->prepare($query);
    $stmt->execute([$current_month]);
    $top_meals_period = $stmt->fetchAll(PDO::FETCH_ASSOC);

} elseif ($period === 'year') {
    // Yearly revenue (last 5 years)
    for ($i = 4; $i >= 0; $i--) {
        $year = date('Y', strtotime("-$i years"));
        $query = "SELECT SUM(total_amount) as revenue FROM orders WHERE YEAR(created_at) = ? AND payment_status = 'completed'";
        $stmt = $db->prepare($query);
        $stmt->execute([$year]);
        $revenue = $stmt->fetch(PDO::FETCH_ASSOC)['revenue'] ?? 0;
        $period_data[] = [
            'period' => $year,
            'revenue' => $revenue
        ];
    }

    // Get top meals for current year
    $current_year = date('Y');
    $query = "SELECT p.name, SUM(oi.quantity) as total_sold, SUM(oi.quantity * oi.price) as revenue
              FROM order_items oi 
              JOIN meals p ON oi.meal_id = p.id 
              JOIN orders o ON oi.order_id = o.id 
              WHERE YEAR(o.created_at) = ? AND o.payment_status = 'completed'
              GROUP BY p.id, p.name 
              ORDER BY total_sold DESC 
              LIMIT 10";
    $stmt = $db->prepare($query);
    $stmt->execute([$current_year]);
    $top_meals_period = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Top selling meals (overall)
$query = "SELECT p.name, SUM(oi.quantity) as total_sold, SUM(oi.quantity * oi.price) as revenue
          FROM order_items oi 
          JOIN meals p ON oi.meal_id = p.id 
          JOIN orders o ON oi.order_id = o.id 
          WHERE o.payment_status = 'completed'
          GROUP BY p.id, p.name 
          ORDER BY total_sold DESC 
          LIMIT 10";
$stmt = $db->prepare($query);
$stmt->execute();
$top_meals = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Recent registrations
$query = "SELECT full_name, email, created_at FROM users WHERE role = 'user' ORDER BY created_at DESC LIMIT 10";
$stmt = $db->prepare($query);
$stmt->execute();
$recent_users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🧑‍🍳 Reports - Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .content{
            overflow-y: scroll;
        }
        .period-selector {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .period-selector form {
            display: flex;
            gap: 15px;
            align-items: end;
            flex-wrap: wrap;
        }
        .form-group {
            display: flex;
            flex-direction: column;
        }
        .form-group label {
            margin-bottom: 5px;
            font-weight: 600;
            color: #2c3e50;
        }
        select, input {
            padding: 10px 12px;
            border: 2px solid #e1e8ed;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        select:focus, input:focus {
            outline: none;
            border-color: #31c205;
        }
        .btn {
            padding: 10px 20px;
            background: linear-gradient(135deg, #023f05 0%, #31c205);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s;
        }
        .btn:hover {
            transform: translateY(-2px);
        }
        .analysis-box {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            height: 100%;
        }
        .revenue-item {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #eee;
            align-items: center;
        }
        .revenue-item:last-child {
            border-bottom: none;
        }
        .meal-rank {
            background: #3498db;
            color: white;
            padding: 4px 10px;
            border-radius: 50%;
            font-size: 0.8rem;
            margin-right: 12px;
            min-width: 30px;
            text-align: center;
        }
        .meal-info {
            display: flex;
            align-items: center;
            flex: 1;
        }
        .meal-stats {
            text-align: right;
        }
        .total-sold {
            font-weight: bold;
            color: #2c3e50;
        }
        .revenue-amount {
            color: #27ae60;
            font-size: 0.9rem;
            font-weight: 600;
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
                <li><a href="users.php">Users</a></li>
                <li><a href="staff.php">Staff</a></li>
                <li><a href="orders.php">Orders</a></li>
                <li><a href="transactions.php">Transactions</a></li>
                <li><a href="chats.php">Customer Chats</a></li>
                <li><a href="reports.php" class="active">Reports</a></li>
            </ul>
        </aside>

        <main class="content">
            <h2>System Reports & Analytics</h2>
            
            <!-- Overview Statistics -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px;">
                <div style="background: white; padding: 20px; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); text-align: center;">
                    <h3 style="color: #3498db; font-size: 2rem; margin-bottom: 10px;"><?php echo $stats['total_users']; ?></h3>
                    <p style="color: #666;">Total Users</p>
                </div>
                <div style="background: white; padding: 20px; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); text-align: center;">
                    <h3 style="color: #27ae60; font-size: 2rem; margin-bottom: 10px;"><?php echo $stats['total_staff']; ?></h3>
                    <p style="color: #666;">Staff Members</p>
                </div>
                <div style="background: white; padding: 20px; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); text-align: center;">
                    <h3 style="color: #f39c12; font-size: 2rem; margin-bottom: 10px;"><?php echo $stats['active_meals']; ?></h3>
                    <p style="color: #666;">Active meals</p>
                </div>
                <div style="background: white; padding: 20px; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); text-align: center;">
                    <h3 style="color: #e74c3c; font-size: 2rem; margin-bottom: 10px;"><?php echo $stats['completed_orders']; ?></h3>
                    <p style="color: #666;">Completed Orders</p>
                </div>
                <div style="background: white; padding: 20px; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); text-align: center; grid-column: span 2;">
                    <h3 style="color: #9b59b6; font-size: 2rem; margin-bottom: 10px;">₦<?php echo number_format($stats['total_revenue'], 2); ?></h3>
                    <p style="color: #666;">Total Revenue</p>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-bottom: 30px;">
                <!-- Revenue Analysis Box -->
                <div class="analysis-box">
                    <div class="period-selector" style="margin: -20px -20px 20px -20px; padding: 20px; border-radius: 10px 10px 0 0; background: #f8f9fa;">
                        <form method="GET" action="">
                            <div class="form-group">
                                <label for="period">Select Period:</label>
                                <select name="period" id="period" onchange="this.form.submit()">
                                    <option value="day" <?php echo $period === 'day' ? 'selected' : ''; ?>>Day</option>
                                    <option value="month" <?php echo $period === 'month' ? 'selected' : ''; ?>>Month</option>
                                    <option value="year" <?php echo $period === 'year' ? 'selected' : ''; ?>>Year</option>
                                </select>
                            </div>
                            
                            <?php if ($period === 'day'): ?>
                            <div class="form-group">
                                <label for="selected_date">Select Date:</label>
                                <input type="date" name="selected_date" id="selected_date" value="<?php echo $selected_date; ?>" max="<?php echo date('Y-m-d'); ?>">
                            </div>
                            <?php endif; ?>
                            
                            <button type="submit" class="btn">Apply</button>
                        </form>
                    </div>

                    <h3 style="margin-bottom: 20px; color: #2c3e50;">
                        <?php 
                        if ($period === 'day') {
                            echo 'Daily Revenue Analysis';
                        } elseif ($period === 'month') {
                            echo 'Monthly Revenue Analysis (Last 6 Months)';
                        } else {
                            echo 'Yearly Revenue Analysis (Last 5 Years)';
                        }
                        ?>
                    </h3>
                    
                    <div style="margin-top: 20px;">
                        <?php foreach ($period_data as $data): ?>
                            <div class="revenue-item">
                                <span style="font-weight: 500;"><?php echo $data['period']; ?></span>
                                <span style="font-weight: bold; color: #27ae60;">₦<?php echo number_format($data['revenue'], 2); ?></span>
                            </div>
                        <?php endforeach; ?>
                        
                        <?php if ($period === 'day' && !empty($period_data)): ?>
                            <div style="margin-top: 20px; padding-top: 20px; border-top: 2px solid #eee;">
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <span style="font-weight: bold; color: #2c3e50;">Daily Total:</span>
                                    <span style="font-weight: bold; color: #27ae60; font-size: 1.1rem;">
                                        ₦<?php echo number_format($period_data[0]['revenue'], 2); ?>
                                    </span>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Top meals Analysis Box -->
                <div class="analysis-box">
                    <h3 style="margin-bottom: 20px; color: #2c3e50;">
                        <?php 
                        if ($period === 'day') {
                            echo 'Top Selling Meals for ' . date('M j, Y', strtotime($selected_date));
                        } elseif ($period === 'month') {
                            echo 'Top Selling Meals for ' . date('F Y');
                        } else {
                            echo 'Top Selling Meals for ' . date('Y');
                        }
                        ?>
                    </h3>
                    
                    <div style="margin-top: 20px;">
                        <?php if (!empty($top_meals_period)): ?>
                            <?php foreach ($top_meals_period as $index => $meal): ?>
                                <div class="revenue-item">
                                    <div class="meal-info">
                                        <span class="meal-rank"><?php echo $index + 1; ?></span>
                                        <span style="font-weight: 500;"><?php echo htmlspecialchars($meal['name']); ?></span>
                                    </div>
                                    <div class="meal-stats">
                                        <div class="total-sold"><?php echo $meal['total_sold']; ?> sold</div>
                                        <div class="revenue-amount">₦<?php echo number_format($meal['revenue'], 2); ?></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div style="text-align: center; color: #666; padding: 40px 20px;">
                                <p style="font-size: 1.1rem; margin-bottom: 10px;">📊</p>
                                <p>No sales data available for the selected period.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Recent User Registrations -->
            <div style="background: white; padding: 20px; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.1);">
                <h3>Recent User Registrations</h3>
                <div class="data-table">
                    <table>
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Registration Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_users as $user): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td><?php echo date('M j, Y g:i A', strtotime($user['created_at'])); ?></td>
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
