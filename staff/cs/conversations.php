<?php
require_once '../../config/database.php';
require_once '../../config/session.php';

requireRole('staff_cs');

$database = new Database();
$db = $database->getConnection();

// Get all conversations
$query = "SELECT cc.*, u.full_name as customer_name, s.full_name as staff_name,
          (SELECT message FROM chat_messages WHERE conversation_id = cc.id ORDER BY created_at DESC LIMIT 1) as last_message,
          (SELECT COUNT(*) FROM chat_messages WHERE conversation_id = cc.id) as message_count
          FROM chat_conversations cc 
          JOIN users u ON cc.user_id = u.id 
          LEFT JOIN users s ON cc.staff_id = s.id
          ORDER BY cc.updated_at DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$conversations = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🧑‍🍳 All Conversations - Customer Service</title>
    <link rel="stylesheet" href="../../assets/css/style.css">c
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
                <li><a href="conversations.php" class="active">All Conversations</a></li>
                <li><a href="my-chats.php">My Chats</a></li>
                <li><a href="reports.php">Reports</a></li>
            </ul>
        </aside>

        <main class="content">
            <h2>All Customer Conversations</h2>
            
            <div style="background: white; padding: 20px; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.1);">
                <div style="display: flex; justify-content: between; align-items: center; margin-bottom: 20px;">
                    <h3>Conversations (<?php echo count($conversations); ?>)</h3>
                </div>
                
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
                                    <td><?php echo htmlspecialchars($conv['customer_name']); ?></td>
                                    <td><?php echo htmlspecialchars($conv['subject']); ?></td>
                                    <td><?php echo $conv['staff_name'] ? htmlspecialchars($conv['staff_name']) : 'Unassigned'; ?></td>
                                    <td><?php echo $conv['message_count']; ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo $conv['status']; ?>">
                                            <?php echo ucfirst($conv['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M j, g:i A', strtotime($conv['updated_at'])); ?></td>
                                    <td>
                                        <a href="chat.php?id=<?php echo $conv['id']; ?>" class="btn btn-primary">View Chat</a>
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
