<?php
require_once '../../config/database.php';
require_once '../../config/session.php';

requireRole('staff_cs');

$database = new Database();
$db = $database->getConnection();

// Get statistics
$stats = [];

// Total conversations
$query = "SELECT COUNT(*) as count FROM chat_conversations";
$stmt = $db->prepare($query);
$stmt->execute();
$stats['total_conversations'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

// Open conversations
$query = "SELECT COUNT(*) as count FROM chat_conversations WHERE status = 'open'";
$stmt = $db->prepare($query);
$stmt->execute();
$stats['open_conversations'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

// My conversations
$query = "SELECT COUNT(*) as count FROM chat_conversations WHERE staff_id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$_SESSION['user_id']]);
$stats['my_conversations'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

// Average response time (simplified)
$stats['avg_response_time'] = '2.5 hours';

// Recent activity
$query = "SELECT cc.*, u.full_name as customer_name, s.full_name as staff_name
          FROM chat_conversations cc 
          JOIN users u ON cc.user_id = u.id 
          LEFT JOIN users s ON cc.staff_id = s.id
          ORDER BY cc.updated_at DESC 
          LIMIT 10";
$stmt = $db->prepare($query);
$stmt->execute();
$recent_activity = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🧑‍🍳 Reports - Customer Service</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
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
                <h1>🧑‍🍳 Godswill - Customer Service</h1>
            </div>
            <nav class="nav">
                <span>Welcome, <?php echo $_SESSION['full_name']; ?> (Customer Service)</span>
                <a href="../../auth/logout.php">Logout</a>
            </nav>
        </div>
    </header>

    <div class="dashboard">
        <aside class="sidebar">
            <ul>
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="conversations.php">All Conversations</a></li>
                <li><a href="my-chats.php">My Chats</a></li>
                <li><a href="reports.php" class="active">Reports</a></li>
            </ul>
        </aside>

        <main class="content">
            <h2>Customer Service Reports</h2>
            
            <!-- Statistics Cards -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px;">
                <div style="background: white; padding: 20px; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); text-align: center;">
                    <h3 style="color: #3498db; font-size: 2rem; margin-bottom: 10px;"><?php echo $stats['total_conversations']; ?></h3>
                    <p style="color: #666;">Total Conversations</p>
                </div>
                <div style="background: white; padding: 20px; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); text-align: center;">
                    <h3 style="color: #f39c12; font-size: 2rem; margin-bottom: 10px;"><?php echo $stats['open_conversations']; ?></h3>
                    <p style="color: #666;">Open Conversations</p>
                </div>
                <div style="background: white; padding: 20px; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); text-align: center;">
                    <h3 style="color: #27ae60; font-size: 2rem; margin-bottom: 10px;"><?php echo $stats['my_conversations']; ?></h3>
                    <p style="color: #666;">My Conversations</p>
                </div>
                <div style="background: white; padding: 20px; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); text-align: center;">
                    <h3 style="color: #e74c3c; font-size: 2rem; margin-bottom: 10px;"><?php echo $stats['avg_response_time']; ?></h3>
                    <p style="color: #666;">Avg Response Time</p>
                </div>
            </div>

            <!-- Recent Activity -->
            <div style="background: white; padding: 20px; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.1);">
                <h3>Recent Activity</h3>
                <div class="data-table">
                    <table>
                        <thead>
                            <tr>
                                <th>Conversation</th>
                                <th>Customer</th>
                                <th>Assigned Staff</th>
                                <th>Status</th>
                                <th>Last Updated</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_activity as $activity): ?>
                                <tr>
                                    <td>#<?php echo $activity['id']; ?> - <?php echo htmlspecialchars($activity['subject']); ?></td>
                                    <td><?php echo htmlspecialchars($activity['customer_name']); ?></td>
                                    <td><?php echo $activity['staff_name'] ? htmlspecialchars($activity['staff_name']) : 'Unassigned'; ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo $activity['status']; ?>">
                                            <?php echo ucfirst($activity['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M j, Y g:i A', strtotime($activity['updated_at'])); ?></td>
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
