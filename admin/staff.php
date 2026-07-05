<?php
require_once '../config/database.php';
require_once '../config/session.php';

requireRole('admin');

$database = new Database();
$db = $database->getConnection();

// Get all staff members
$query = "SELECT u.*, 
          CASE 
            WHEN u.role = 'staff_goods' THEN (SELECT COUNT(*) FROM meals WHERE created_by = u.id)
            WHEN u.role = 'staff_cs' THEN (SELECT COUNT(*) FROM chat_conversations WHERE staff_id = u.id)
            WHEN u.role = 'delivery' THEN (SELECT COUNT(*) FROM orders WHERE delivery_person_id = u.id)
            ELSE 0
          END as activity_count
          FROM users u 
          WHERE u.role IN ('staff_goods', 'staff_cs', 'delivery') 
          ORDER BY u.created_at DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$staff = $stmt->fetchAll(PDO::FETCH_ASSOC);

$error = '';
$success = '';

// Handle role change
if ($_POST && isset($_POST['change_role'])) {
    $user_id = $_POST['user_id'];
    $new_role = $_POST['new_role'];
    
    try {
        $query = "UPDATE users SET role = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$new_role, $user_id]);
        
        $success = "Staff role updated successfully!";
        header('Location: staff.php');
        exit();
    } catch (Exception $e) {
        $error = "Failed to update staff role";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🧑‍🍳 Staff Management - Admin</title>
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
                <li><a href="staff.php" class="active">Staff</a></li>
                <li><a href="orders.php">Orders</a></li>
                <li><a href="transactions.php">Transactions</a></li>
                <li><a href="chats.php">Customer Chats</a></li>
                <li><a href="reports.php">Reports</a></li>
            </ul>
        </aside>

        <main class="content">
            <h2>Staff Management</h2>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <div style="background: white; padding: 20px; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.1);">
                <div style="display: flex; justify-content: between; align-items: center; margin-bottom: 20px;">
                    <h3>All Staff Members (<?php echo count($staff); ?>)</h3>
                </div>
                
                <div class="data-table">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Activity</th>
                                <th>Joined</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($staff as $member): ?>
                                <tr>
                                    <td><?php echo $member['id']; ?></td>
                                    <td><?php echo htmlspecialchars($member['full_name']); ?></td>
                                    <td><?php echo htmlspecialchars($member['email']); ?></td>
                                    <td>
                                        <span class="status-badge 
                                            <?php 
                                                echo ($member['role'] === 'staff_goods' || $member['role'] === 'delivery') 
                                                    ? 'status-completed' 
                                                    : 'status-pending'; 
                                            ?>">
                                            <?php 
                                                if ($member['role'] === 'staff_goods') {
                                                    echo 'meal Manager';
                                                } elseif ($member['role'] === 'delivery') {
                                                    echo 'Delivery';
                                                } else {
                                                    echo 'Customer Service';
                                                }
                                            ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php 
                                            if ($member['role'] == 'staff_goods') {
                                                echo $member['activity_count'] . ' meals created';
                                            } elseif ($member['role'] == 'staff_cs') {
                                                echo $member['activity_count'] . ' conversations handled';
                                            } else {
                                                echo $member['activity_count'] . ' deliveries handled';
                                            }
                                        ?>
                                    </td>
                                    <td><?php echo date('M j, Y', strtotime($member['created_at'])); ?></td>
                                    <td>
                                        <button onclick="changeRole(<?php echo $member['id']; ?>, '<?php echo $member['role']; ?>')" class="btn btn-secondary">Change Role</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>

                    </table>
                </div>
            </div>
        </main>
    </div>

    <!-- Role Change Modal -->
    <div id="roleModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000;">
        <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 30px; border-radius: 10px; min-width: 300px;">
            <h3>Change Staff Role</h3>
            <form method="POST">
                <input type="hidden" name="user_id" id="modalUserId">
                <div class="form-group">
                    <label for="new_role">New Role</label>
                    <select name="new_role" id="modalNewRole" required>
                        <option value="staff_goods">meal Manager</option>
                        <option value="staff_cs">Customer Service</option>
                         <option value="delivery">Delivery</option>
                        <option value="user">Regular User</option>
                    </select>
                </div>
                <div style="display: flex; gap: 10px; margin-top: 20px;">
                    <button type="submit" name="change_role" class="btn btn-primary">Change Role</button>
                    <button type="button" onclick="closeRoleModal()" class="btn btn-secondary">Cancel</button>
                </div>
            </form>
        </div>
    </div>
              <!-- Footer -->
    <footer style="background: linear-gradient(135deg, #023f05 0%, #31c205 100%); color: white; padding: 1rem 0; text-align: center;">
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> Godswill Meal System. Developed by Kayode Ilesanmi, Lawal Fawas and Areo David</p>
        </div>
    </footer>
    <script>
        function changeRole(userId, currentRole) {
            document.getElementById('modalUserId').value = userId;
            document.getElementById('modalNewRole').value = currentRole;
            document.getElementById('roleModal').style.display = 'block';
        }

        function closeRoleModal() {
            document.getElementById('roleModal').style.display = 'none';
        }

        // Close modal when clicking outside
        document.getElementById('roleModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeRoleModal();
            }
        });
    </script>
</body>
</html>
