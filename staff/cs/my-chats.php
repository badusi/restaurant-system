<?php
require_once '../../config/database.php';
require_once '../../config/session.php';

requireRole('staff_cs');

$database = new Database();
$db = $database->getConnection();

// Get conversations assigned to current staff
$query = "SELECT cc.*, u.full_name as customer_name,
          (SELECT message FROM chat_messages WHERE conversation_id = cc.id ORDER BY created_at DESC LIMIT 1) as last_message,
          (SELECT COUNT(*) FROM chat_messages WHERE conversation_id = cc.id) as message_count
          FROM chat_conversations cc 
          JOIN users u ON cc.user_id = u.id 
          WHERE cc.staff_id = ?
          ORDER BY cc.updated_at DESC";
$stmt = $db->prepare($query);
$stmt->execute([$_SESSION['user_id']]);
$my_conversations = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🧑‍🍳 My Chats - Customer Service</title>
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
                <li><a href="my-chats.php" class="active">My Chats</a></li>
                <li><a href="reports.php">Reports</a></li>
            </ul>
        </aside>

        <main class="content">
            <h2>My Assigned Chats</h2>
            
            <div style="background: white; padding: 20px; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.1);">
                <div style="display: flex; justify-content: between; align-items: center; margin-bottom: 20px;">
                    <h3>My Conversations (<?php echo count($my_conversations); ?>)</h3>
                </div>
                
                <?php if (empty($my_conversations)): ?>
                    <div style="text-align: center; padding: 50px; color: #666;">
                        <h4>No conversations assigned yet</h4>
                        <p>Check the <a href="conversations.php" style="color: #3498db;">All Conversations</a> page to pick up new chats.</p>
                    </div>
                <?php else: ?>
                    <div class="data-table">
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Customer</th>
                                    <th>Subject</th>
                                    <th>Messages</th>
                                    <th>Status</th>
                                    <th>Last Message</th>
                                    <th>Last Activity</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($my_conversations as $conv): ?>
                                    <tr>
                                        <td>#<?php echo $conv['id']; ?></td>
                                        <td><?php echo htmlspecialchars($conv['customer_name']); ?></td>
                                        <td><?php echo htmlspecialchars($conv['subject']); ?></td>
                                        <td><?php echo $conv['message_count']; ?></td>
                                        <td>
                                            <span class="status-badge status-<?php echo $conv['status']; ?>">
                                                <?php echo ucfirst($conv['status']); ?>
                                            </span>
                                        </td>
                                        <td style="max-width: 200px; overflow: hidden; text-overflow: ellipsis;">
                                            <?php echo htmlspecialchars(substr($conv['last_message'] ?? 'No messages', 0, 50)); ?>...
                                        </td>
                                        <td><?php echo date('M j, g:i A', strtotime($conv['updated_at'])); ?></td>
                                        <td>
                                            <a href="chat.php?id=<?php echo $conv['id']; ?>" class="btn btn-primary">Continue Chat</a>
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
