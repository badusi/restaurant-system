<?php
require_once '../config/database.php';
require_once '../config/session.php';

requireRole('user');

$database = new Database();
$db = $database->getConnection();

// Get or create conversation
$conversation_id = $_GET['conversation_id'] ?? null;

if (!$conversation_id) {
    // Create new conversation
    $query = "INSERT INTO chat_conversations (user_id, subject) VALUES (?, 'General Support')";
    $stmt = $db->prepare($query);
    $stmt->execute([$_SESSION['user_id']]);
    $conversation_id = $db->lastInsertId();
    
    header("Location: chat.php?conversation_id=$conversation_id");
    exit();
}

// Get conversation details
$query = "SELECT * FROM chat_conversations WHERE id = ? AND user_id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$conversation_id, $_SESSION['user_id']]);
$conversation = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$conversation) {
    header('Location: chat.php');
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
        
        header("Location: chat.php?conversation_id=$conversation_id");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🧑‍🍳 Customer Support - Godswill</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .content{
            overflow-y: scroll;
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="container">
            <div class="logo">
                <h1>🧑‍🍳 Godswill</h1>
            </div>
            <nav class="nav">
                <span>Welcome, <?php echo $_SESSION['full_name']; ?></span>
                <a href="dashboard.php">🍴 Dashboard</a>
                <a href="cart.php">Meal Cart</a>
                <a href="orders.php">Orders</a>
                <a href="../auth/logout.php">Logout</a>
            </nav>
        </div>
    </header>

    <div class="dashboard">
        <aside class="sidebar">
            <ul>
                <li><a href="dashboard.php">🍴 Dashboard</a></li>
                <li><a href="cart.php">🧺 Meal Cart</a></li>
                <li><a href="orders.php">My Orders</a></li>
                <li><a href="chat.php" class="active">Customer Support</a></li>
                <li><a href="profile.php">Profile</a></li>
            </ul>
        </aside>

        <main class="content">
            <h2>Customer Support Chat</h2>
            
            <div class="chat-container">
                <div class="chat-header">
                    <h3>Support Conversation</h3>
                    <p>Status: <?php echo ucfirst($conversation['status']); ?></p>
                </div>
                
                <div class="chat-messages" id="chatMessages">
                    <?php if (empty($messages)): ?>
                        <div class="message staff">
                            <p><strong>Support Team:</strong> Hello! How can we help you today?</p>
                            <small style="color: #666;">Just now</small>
                        </div>
                    <?php else: ?>
                        <?php foreach ($messages as $message): ?>
                            <div class="message <?php echo $message['role'] === 'user' ? 'user' : 'staff'; ?>">
                                <p><strong><?php echo htmlspecialchars($message['full_name']); ?>:</strong> <?php echo htmlspecialchars($message['message']); ?></p>
                                <small style="color: #666;"><?php echo date('M j, Y g:i A', strtotime($message['created_at'])); ?></small>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <form method="POST" class="chat-input">
                    <input type="text" name="message" placeholder="Type your message..." required>
                    <button type="submit">Send</button>
                </form>
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
        
        // Reduced auto-refresh to every 30 seconds instead of 5 seconds
        setInterval(function() {
            // Only refresh if user is not typing
            const messageInput = document.querySelector('input[name="message"]');
            if (document.activeElement !== messageInput) {
                location.reload();
            }
        }, 30000); // Changed from 5000 to 30000 (30 seconds)
    </script>
</body>
</html>
