<?php
require_once '../config/database.php';
require_once '../config/session.php';

requireRole('admin');

$database = new Database();
$db = $database->getConnection();

$conversation_id = $_GET['id'] ?? 0;

// Get conversation details
$query = "SELECT cc.*, u.full_name as customer_name, u.email as customer_email, 
          s.full_name as staff_name
          FROM chat_conversations cc 
          JOIN users u ON cc.user_id = u.id 
          LEFT JOIN users s ON cc.staff_id = s.id 
          WHERE cc.id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$conversation_id]);
$conversation = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$conversation) {
    header('Location: chats.php');
    exit();
}

// Get messages
$query = "SELECT cm.*, u.full_name, u.role 
          FROM chat_messages cm 
          JOIN users u ON cm.sender_id = u.id 
          WHERE cm.conversation_id = ? 
          ORDER BY cm.created_at ASC";
$stmt = $db->prepare($query);
$stmt->execute([$conversation_id]);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🧑‍🍳 Chat Details - Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">c
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
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <div>
                    <h2>Chat Conversation #<?php echo $conversation['id']; ?></h2>
                    <p style="color: #666;">Subject: <?php echo htmlspecialchars($conversation['subject']); ?></p>
                </div>
                <a href="chats.php" class="btn btn-secondary">Back to Chats</a>
            </div>

            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 30px;">
                <!-- Chat Messages -->
                <div>
                    <div class="chat-container">
                        <div class="chat-header">
                            <h3>Conversation Messages</h3>
                            <p>Between <?php echo htmlspecialchars($conversation['customer_name']); ?> and Support Team</p>
                        </div>
                        
                        <div class="chat-messages" id="chatMessages">
                            <?php foreach ($messages as $message): ?>
                                <div class="message <?php echo $message['role'] === 'user' ? 'user' : 'staff'; ?>">
                                    <p><strong><?php echo htmlspecialchars($message['full_name']); ?> (<?php echo ucfirst($message['role']); ?>):</strong> <?php echo htmlspecialchars($message['message']); ?></p>
                                    <small style="color: #666;"><?php echo date('M j, Y g:i A', strtotime($message['created_at'])); ?></small>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Conversation Details -->
                <div>
                    <div style="background: white; padding: 20px; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); margin-bottom: 20px;">
                        <h3>Conversation Details</h3>
                        <div style="margin-top: 15px;">
                            <p><strong>Customer:</strong> <?php echo htmlspecialchars($conversation['customer_name']); ?></p>
                            <p><strong>Email:</strong> <?php echo htmlspecialchars($conversation['customer_email']); ?></p>
                            <p><strong>Assigned Staff:</strong> <?php echo $conversation['staff_name'] ? htmlspecialchars($conversation['staff_name']) : 'Unassigned'; ?></p>
                            <p><strong>Status:</strong> 
                                <span class="status-badge status-<?php echo $conversation['status']; ?>">
                                    <?php echo ucfirst($conversation['status']); ?>
                                </span>
                            </p>
                            <p><strong>Created:</strong> <?php echo date('M j, Y g:i A', strtotime($conversation['created_at'])); ?></p>
                            <p><strong>Last Updated:</strong> <?php echo date('M j, Y g:i A', strtotime($conversation['updated_at'])); ?></p>
                            <p><strong>Total Messages:</strong> <?php echo count($messages); ?></p>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div style="background: white; padding: 20px; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.1);">
                        <h3>Quick Actions</h3>
                        <div style="margin-top: 15px;">
                            <a href="user-details.php?id=<?php echo $conversation['user_id']; ?>" class="btn btn-primary" style="width: 100%; margin-bottom: 10px; text-align: center; display: block; text-decoration: none;">View Customer Profile</a>
                            <?php if ($conversation['staff_id']): ?>
                                <a href="staff.php" class="btn btn-secondary" style="width: 100%; text-align: center; display: block; text-decoration: none;">View Staff Details</a>
                            <?php endif; ?>
                        </div>
                    </div>
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
    <script>
        // Auto-scroll to bottom of chat
        const chatMessages = document.getElementById('chatMessages');
        chatMessages.scrollTop = chatMessages.scrollHeight;
    </script>
</body>
</html>
