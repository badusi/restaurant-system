<?php
require_once '../config/database.php';
require_once '../config/session.php';

requireRole('admin');

$database = new Database();
$db = $database->getConnection();

// Get all chat conversations with details
$query = "SELECT cc.*, u.full_name as customer_name, u.email as customer_email, 
          s.full_name as staff_name,
          (SELECT COUNT(*) FROM chat_messages WHERE conversation_id = cc.id) as message_count,
          (SELECT message FROM chat_messages WHERE conversation_id = cc.id ORDER BY created_at DESC LIMIT 1) as last_message
          FROM chat_conversations cc 
          JOIN users u ON cc.user_id = u.id 
          LEFT JOIN users s ON cc.staff_id = s.id
          ORDER BY cc.updated_at DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$conversations = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get statistics
$stats = [];
$stats['total_conversations'] = count($conversations);
$stats['open_conversations'] = count(array_filter($conversations, function($c) { return $c['status'] === 'open'; }));
$stats['closed_conversations'] = $stats['total_conversations'] - $stats['open_conversations'];
$stats['unassigned_conversations'] = count(array_filter($conversations, function($c) { return !$c['staff_id']; }));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🧑‍🍳 Customer Chats - Admin</title>
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
                <li><a href="transactions.php">Transactions</a></li>
                <li><a href="chats.php" class="active">Customer Chats</a></li>
                <li><a href="reports.php">Reports</a></li>
            </ul>
        </aside>

        <main class="content">
            <h2>Customer Chat Management</h2>
            
            <!-- Statistics Cards -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px;">
                <div style="background: white; padding: 20px; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); text-align: center;">
                    <h3 style="color: #3498db; font-size: 2rem; margin-bottom: 10px;"><?php echo $stats['total_conversations']; ?></h3>
                    <p style="color: #666;">Total Conversations</p>
                </div>
                <div style="background: white; padding: 20px; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); text-align: center;">
                    <h3 style="color: #f39c12; font-size: 2rem; margin-bottom: 10px;"><?php echo $stats['open_conversations']; ?></h3>
                    <p style="color: #666;">Open</p>
                </div>
                <div style="background: white; padding: 20px; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); text-align: center;">
                    <h3 style="color: #27ae60; font-size: 2rem; margin-bottom: 10px;"><?php echo $stats['closed_conversations']; ?></h3>
                    <p style="color: #666;">Closed</p>
                </div>
                <div style="background: white; padding: 20px; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); text-align: center;">
                    <h3 style="color: #e74c3c; font-size: 2rem; margin-bottom: 10px;"><?php echo $stats['unassigned_conversations']; ?></h3>
                    <p style="color: #666;">Unassigned</p>
                </div>
            </div>
            
            <div style="background: white; padding: 20px; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.1);">
                <h3>All Customer Conversations</h3>
                
                <div class="data-table">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Customer</th>
                                <th>Subject</th>
                                <th>Assigned Staff</th>
                                <th>Messages</th>
                                <th>Status</th>
                                <th>Last Activity</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($conversations as $conv): ?>
                                <tr>
                                    <td>#<?php echo $conv['id']; ?></td>
                                    <td>
                                        <?php echo htmlspecialchars($conv['customer_name']); ?>
                                        <br><small style="color: #666;"><?php echo htmlspecialchars($conv['customer_email']); ?></small>
                                    </td>
                                    <td><?php echo htmlspecialchars($conv['subject']); ?></td>
                                    <td><?php echo $conv['staff_name'] ? htmlspecialchars($conv['staff_name']) : '<span style="color: #e74c3c;">Unassigned</span>'; ?></td>
                                    <td><?php echo $conv['message_count']; ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo $conv['status']; ?>">
                                            <?php echo ucfirst($conv['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M j, Y g:i A', strtotime($conv['updated_at'])); ?></td>
                                    <td>
                                        <a href="chat-details.php?id=<?php echo $conv['id']; ?>" class="btn btn-secondary">View Chat</a>
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
