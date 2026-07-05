<?php
require_once '../config/database.php';
require_once '../config/session.php';

requireRole('user');

$database = new Database();
$db = $database->getConnection();

// Get user details
$query = "SELECT * FROM users WHERE id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$error = '';
$success = '';

if ($_POST) {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($full_name) || empty($email)) {
        $error = "Name and email are required";
    } else {
        try {
            // Check if email is already taken by another user
            $query = "SELECT id FROM users WHERE email = ? AND id != ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$email, $_SESSION['user_id']]);
            
            if ($stmt->rowCount() > 0) {
                $error = "Email is already taken by another user";
            } else {
                // Update basic info
                $query = "UPDATE users SET full_name = ?, email = ?, phone = ?, address = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
                $stmt = $db->prepare($query);
                $stmt->execute([$full_name, $email, $phone, $address, $_SESSION['user_id']]);
                
                // Update session data
                $_SESSION['full_name'] = $full_name;
                
                // Handle password change
                if (!empty($new_password)) {
                    if (empty($current_password)) {
                        $error = "Current password is required to change password";
                    } elseif ($new_password !== $confirm_password) {
                        $error = "New passwords do not match";
                    } elseif (strlen($new_password) < 6) {
                        $error = "New password must be at least 6 characters long";
                    } elseif (!password_verify($current_password, $user['password'])) {
                        $error = "Current password is incorrect";
                    } else {
                        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                        $query = "UPDATE users SET password = ? WHERE id = ?";
                        $stmt = $db->prepare($query);
                        $stmt->execute([$hashed_password, $_SESSION['user_id']]);
                        
                        $success = "Profile and password updated successfully!";
                    }
                } else {
                    $success = "Profile updated successfully!";
                }
                
                // Refresh user data
                $query = "SELECT * FROM users WHERE id = ?";
                $stmt = $db->prepare($query);
                $stmt->execute([$_SESSION['user_id']]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
            }
        } catch (Exception $e) {
            $error = "Failed to update profile. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🧑‍🍳 My Profile - Godswill</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .dashboard {
            display: flex;
            min-height: 100vh; /* Ensure full screen height */
            overflow: hidden;
        }

        .content {
            flex: 1;
            padding: 2rem;
            overflow-y: auto;
            max-height: 100vh;
        }
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
                <h1>🧑‍🍳 Godswill</h1>
            </div>
            <nav class="nav">
                <span>Welcome, <?php echo $_SESSION['full_name']; ?></span>
                <a href="dashboard.php">🍴 Dashboard</a>
                <a href="cart.php">🧺 Meal Cart</a>
                <a href="orders.php">Orders</a>
                <a href="../auth/logout.php">Logout</a>
            </nav>
        </div>
    </header>

    <div class="dashboard">
        <aside class="sidebar">
            <ul>
                <li><a href="dashboard.php">🍴 Dashboard</a></li>
                <li><a href="cart.php">Meal Cart</a></li>
                <li><a href="orders.php">My Orders</a></li>
                <li><a href="chat.php">Customer Support</a></li>
                <li><a href="profile.php" class="active">Profile</a></li>
            </ul>
        </aside>

        <main class="content">
            <h2>My Profile</h2>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">
                <!-- Profile Information -->
                <div style="background: white; padding: 30px; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.1);">
                    <h3>Profile Information</h3>
                    <form method="POST">
                        <div class="form-group">
                            <label for="full_name">Full Name</label>
                            <input type="text" name="full_name" id="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="phone">Phone Number</label>
                            <input type="tel" name="phone" id="phone" value="<?php echo htmlspecialchars($user['phone']); ?>">
                        </div>

                        <div class="form-group">
                            <label for="address">Address</label>
                            <textarea name="address" id="address" rows="4"><?php echo htmlspecialchars($user['address']); ?></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary">Update Profile</button>
                    </form>
                </div>

                <!-- Change Password -->
                <div style="background: white; padding: 30px; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.1);">
                    <h3>Change Password</h3>
                    <form method="POST">
                        <!-- Hidden fields to maintain profile data -->
                        <input type="hidden" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>">
                        <input type="hidden" name="email" value="<?php echo htmlspecialchars($user['email']); ?>">
                        <input type="hidden" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>">
                        <input type="hidden" name="address" value="<?php echo htmlspecialchars($user['address']); ?>">

                        <div class="form-group">
                            <label for="current_password">Current Password</label>
                            <input type="password" name="current_password" id="current_password">
                        </div>

                        <div class="form-group">
                            <label for="new_password">New Password</label>
                            <input type="password" name="new_password" id="new_password" minlength="6">
                        </div>

                        <div class="form-group">
                            <label for="confirm_password">Confirm New Password</label>
                            <input type="password" name="confirm_password" id="confirm_password" minlength="6">
                        </div>

                        <button type="submit" class="btn btn-primary">Change Password</button>
                    </form>

                    <div class="account" style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee;">
                        <h4>Account Information</h4>
                        <p><strong>Username:</strong> <?php echo htmlspecialchars($user['username']); ?></p>
                        <p><strong>Member Since:</strong> <?php echo date('F j, Y', strtotime($user['created_at'])); ?></p>
                        <p><strong>Last Updated:</strong> <?php echo date('F j, Y g:i A', strtotime($user['updated_at'])); ?></p>
                    </div>
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
