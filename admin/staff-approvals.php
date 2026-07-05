<?php
require_once '../config/database.php';
require_once '../config/session.php';

requireRole('admin');

$database = new Database();
$db = $database->getConnection();

// Handle approval/decline actions
if ($_POST && isset($_POST['action'])) {
    $user_id = intval($_POST['user_id']);
    $action = $_POST['action'];
    
    if ($action === 'approve') {
        $query = "UPDATE users SET status = 'approved' WHERE id = ? AND status = 'pending'";
        $stmt = $db->prepare($query);
        $stmt->execute([$user_id]);
        $message = "User approved successfully!";
    } elseif ($action === 'decline') {
        $query = "UPDATE users SET status = 'declined' WHERE id = ? AND status = 'pending'";
        $stmt = $db->prepare($query);
        $stmt->execute([$user_id]);
        $message = "User application declined.";
    }
}

// Get pending staff applications
$query = "SELECT * FROM users WHERE status = 'pending' AND role != 'user' ORDER BY created_at DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$pending_users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🧑‍🍳 Staff Approvals - Admin</title>
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
                <li><a href="staff-approvals.php" class="active">Staff Approvals</a></li>
                <li><a href="orders.php">Orders</a></li>
                <li><a href="transactions.php">Transactions</a></li>
                <li><a href="chats.php">Customer Chats</a></li>
                <li><a href="reports.php">Reports</a></li>
            </ul>
        </aside>

        <main class="content">
            <h2>👥 Staff Approvals</h2>
            
            <?php if (isset($message)): ?>
                <div class="alert alert-success"><?php echo $message; ?></div>
            <?php endif; ?>
            
            <div style="background: white; padding: 20px; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.1);">
                <h3>Pending Applications (<?php echo count($pending_users); ?>)</h3>
                
                <?php if (empty($pending_users)): ?>
                    <p style="text-align: center; color: #666; padding: 20px;">No pending staff applications.</p>
                <?php else: ?>
                    <?php foreach ($pending_users as $user): ?>
                        <div style="border: 1px solid #ddd; border-radius: 10px; padding: 20px; margin-bottom: 20px;">
                            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px;">
                                <div>
                                    <h4><?php echo htmlspecialchars($user['full_name']); ?></h4>
                                    <p><strong>Position:</strong> <?php echo ucwords(str_replace('_', ' ', $user['role'])); ?></p>
                                    <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
                                    <p><strong>Phone:</strong> <?php echo htmlspecialchars($user['phone'] ?: 'Not provided'); ?></p>
                                    <p><strong>Address:</strong> <?php echo htmlspecialchars($user['address'] ?: 'Not provided'); ?></p>
                                    <p><strong>Applied:</strong> <?php echo date('M j, Y g:i A', strtotime($user['created_at'])); ?></p>
                                    
                                    <div style="margin-top: 15px;">
                                        <?php if ($user['cv_file']): ?>
                                            <a href="../uploads/<?php echo htmlspecialchars($user['cv_file']); ?>" target="_blank" class="btn btn-secondary" style="margin-right: 10px;">
                                                📄 View CV
                                            </a>
                                        <?php endif; ?>
                                        
                                        <?php if ($user['results_file']): ?>
                                            <a href="../uploads/<?php echo htmlspecialchars($user['results_file']); ?>" target="_blank" class="btn btn-secondary">
                                                📊 View Results
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div style="text-align: right;">
                                    <form method="POST" style="display: inline-block; margin-right: 10px;">
                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                        <input type="hidden" name="action" value="approve">
                                        <button type="submit" class="btn" style="background: #27ae60; color: white;" onclick="return confirm('Are you sure you want to approve this application?')">
                                            ✅ Approve
                                        </button>
                                    </form>
                                    
                                    <form method="POST" style="display: inline-block;">
                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                        <input type="hidden" name="action" value="decline">
                                        <button type="submit" class="btn" style="background: #e74c3c; color: white;" onclick="return confirm('Are you sure you want to decline this application?')">
                                            ❌ Decline
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
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
