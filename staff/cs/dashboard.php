<?php
require_once '../../config/database.php';
require_once '../../config/session.php';

requireRole('staff_cs');

$database = new Database();
$db = $database->getConnection();

// Get open conversations
$query = "SELECT cc.*, u.full_name as customer_name, 
          (SELECT COUNT(*) FROM chat_messages WHERE conversation_id = cc.id AND sender_id != ?) as unread_count,
          (SELECT message FROM chat_messages WHERE conversation_id = cc.id ORDER BY created_at DESC LIMIT 1) as last_message
          FROM chat_conversations cc 
          JOIN users u ON cc.user_id = u.id 
          WHERE cc.status = 'open' 
          ORDER BY cc.updated_at DESC";
$stmt = $db->prepare($query);
$stmt->execute([$_SESSION['user_id']]);
$conversations = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get assigned conversations
$query = "SELECT cc.*, u.full_name as customer_name,
          (SELECT message FROM chat_messages WHERE conversation_id = cc.id ORDER BY created_at DESC LIMIT 1) as last_message
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
    <title>🧑‍🍳 Customer Service Dashboard</title>
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
                <li><a href="dashboard.php" class="active">Dashboard</a></li>
                <li><a href="conversations.php">All Conversations</a></li>
                <li><a href="my-chats.php">My Chats</a></li>
                <li><a href="reports.php">Reports</a></li>
            </ul>
        </aside>

        <main class="content">
            <h2>Customer Service Dashboard</h2>
            
            <!-- Statistics -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px;">
                <div style="background: white; padding: 20px; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); text-align: center;">
                    <h3 style="color: #f39c12; font-size: 2rem; margin-bottom: 10px;"><?php echo count($conversations); ?></h3>
                    <p style="color: #666;">Open Conversations</p>
                </div>
                <div style="background: white; padding: 20px; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); text-align: center;">
                    <h3 style="color: #3498db; font-size: 2rem; margin-bottom: 10px;"><?php echo count($my_conversations); ?></h3>
                    <p style="color: #666;">My Conversations</p>
                </div>
            </div>

            <!-- Open Conversations -->
            <div style="background: white; padding: 20px; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); margin-bottom: 30px;">
                <h3>Open Conversations</h3>
                <?php if (empty($conversations)): ?>
                    <p style="text-align: center; color: #666; padding: 20px;">No open conversations at the moment.</p>
                <?php else: ?>
                    <div class="data-table">
                        <table>
                            <thead>
                                <tr>
                                    <th>Customer</th>
                                    <th>Subject</th>
                                    <th>Last Message</th>
                                    <th>Updated</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($conversations as $conv): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($conv['customer_name']); ?></td>
                                        <td><?php echo htmlspecialchars($conv['subject']); ?></td>
                                        <td style="max-width: 200px; overflow: hidden; text-overflow: ellipsis;">
                                            <?php echo htmlspecialchars(substr($conv['last_message'] ?? 'No messages yet', 0, 50)); ?>...
                                        </td>
                                        <td><?php echo date('M j, g:i A', strtotime($conv['updated_at'])); ?></td>
                                        <td>
                                            <a href="chat.php?id=<?php echo $conv['id']; ?>" class="btn btn-primary">Respond</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <!-- My Conversations -->
            <div style="background: white; padding: 20px; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.1);">
                <h3>My Active Conversations</h3>
                <?php if (empty($my_conversations)): ?>
                    <p style="text-align: center; color: #666; padding: 20px;">You haven't been assigned any conversations yet.</p>
                <?php else: ?>
                    <div class="data-table">
                        <table>
                            <thead>
                                <tr>
                                    <th>Customer</th>
                                    <th>Subject</th>
                                    <th>Last Message</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($my_conversations as $conv): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($conv['customer_name']); ?></td>
                                        <td><?php echo htmlspecialchars($conv['subject']); ?></td>
                                        <td style="max-width: 200px; overflow: hidden; text-overflow: ellipsis;">
                                            <?php echo htmlspecialchars(substr($conv['last_message'] ?? 'No messages yet', 0, 50)); ?>...
                                        </td>
                                        <td>
                                            <span class="status-badge status-<?php echo $conv['status']; ?>">
                                                <?php echo ucfirst($conv['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="chat.php?id=<?php echo $conv['id']; ?>" class="btn btn-primary">Continue</a>
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
