<?php
require_once '../../config/database.php';
require_once '../../config/session.php';

requireRole('staff_cs');

$database = new Database();
$db = $database->getConnection();

$conversation_id = $_GET['id'] ?? 0;

// Get conversation details
$query = "SELECT cc.*, u.full_name as customer_name, u.email as customer_email 
          FROM chat_conversations cc 
          JOIN users u ON cc.user_id = u.id 
          WHERE cc.id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$conversation_id]);
$conversation = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$conversation) {
    header('Location: dashboard.php');
    exit();
}

// Assign conversation to current staff if not assigned
if (!$conversation['staff_id']) {
    $query = "UPDATE chat_conversations SET staff_id = ? WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$_SESSION['user_id'], $conversation_id]);
    $conversation['staff_id'] = $_SESSION['user_id'];
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

// Handle new message
if ($_POST && isset($_POST['message'])) {
    $message = trim($_POST['message']);
    if (!empty($message)) {
        $query = "INSERT INTO chat_messages (conversation_id, sender_id, message) VALUES (?, ?, ?)";
        $stmt = $db->prepare($query);
        $stmt->execute([$conversation_id, $_SESSION['user_id'], $message]);
        
        // Update conversation timestamp
        $query = "UPDATE chat_conversations SET updated_at = CURRENT_TIMESTAMP WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$conversation_id]);
        
        header("Location: chat.php?id=$conversation_id");
        exit();
    }
}

// Handle close conversation
if ($_POST && isset($_POST['close_conversation'])) {
    $query = "UPDATE chat_conversations SET status = 'closed' WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$conversation_id]);
    
    header('Location: dashboard.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🧑‍🍳 Customer Support Chat</title>
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
                <a href="dashboard.php">Dashboard</a>
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
                <li><a href="reports.php">Reports</a></li>
            </ul>
        </aside>

        <main class="content">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <div>
                    <h2>Chat with <?php echo htmlspecialchars($conversation['customer_name']); ?></h2>
                    <p style="color: #666;">Subject: <?php echo htmlspecialchars($conversation['subject']); ?></p>
                </div>
                <div>
                    <?php if ($conversation['status'] === 'open'): ?>
                        <form method="POST" style="display: inline;">
                            <button type="submit" name="close_conversation" class="btn" style="background: #e74c3c; color: white;" onclick="return confirm('Are you sure you want to close this conversation?')">Close Conversation</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="chat-container">
                <div class="chat-header">
                    <h3>Support Conversation</h3>
                    <p>Customer: <?php echo htmlspecialchars($conversation['customer_name']); ?> (<?php echo htmlspecialchars($conversation['customer_email']); ?>)</p>
                </div>
                
                <div class="chat-messages" id="chatMessages">
                    <?php foreach ($messages as $message): ?>
                        <div class="message <?php echo $message['role'] === 'user' ? 'user' : 'staff'; ?>">
                            <p><strong><?php echo htmlspecialchars($message['full_name']); ?>:</strong> <?php echo htmlspecialchars($message['message']); ?></p>
                            <small style="color: #666;"><?php echo date('M j, Y g:i A', strtotime($message['created_at'])); ?></small>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <?php if ($conversation['status'] === 'open'): ?>
                    <form method="POST" class="chat-input">
                        <input type="text" name="message" placeholder="Type your response..." required>
                        <button type="submit">Send</button>
                    </form>
                <?php else: ?>
                    <div style="padding: 20px; text-align: center; background: #f8f9fa; border-top: 1px solid #ddd;">
                        <p style="color: #666;">This conversation has been closed.</p>
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
    <script>
        // Auto-scroll to bottom of chat
        const chatMessages = document.getElementById('chatMessages');
        chatMessages.scrollTop = chatMessages.scrollHeight;
    </script>
</body>
</html>
