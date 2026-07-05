<?php
require_once '../config/database.php';
require_once '../config/session.php';

$database = new Database();
$db = $database->getConnection();

$error = '';
$success = '';

if ($_POST) {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $full_name = trim($_POST['full_name']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $role = $_POST['role'];

    // Validation
    if (empty($username) || empty($email) || empty($password) || empty($full_name)) {
        $error = "All required fields must be filled";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long";
    } else {
        // Check if username or email already exists
        $query = "SELECT id FROM users WHERE username = ? OR email = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$username, $email]);
        
        if ($stmt->rowCount() > 0) {
            $error = "Username or email already exists";
        } else {
            // Handle file uploads for staff roles
            $cv_file = null;
            $results_file = null;
            
            if ($role !== 'user') {
                $upload_dir = '../uploads/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                // Handle CV upload (required for all staff)
                if (isset($_FILES['cv_file']) && $_FILES['cv_file']['error'] === UPLOAD_ERR_OK) {
                    $cv_filename = time() . '_cv_' . basename($_FILES['cv_file']['name']);
                    $cv_path = $upload_dir . $cv_filename;
                    if (move_uploaded_file($_FILES['cv_file']['tmp_name'], $cv_path)) {
                        $cv_file = $cv_filename;
                    }
                }
                
                // Handle results upload (for CS and goods staff only)
                if (($role === 'staff_cs' || $role === 'staff_goods') && isset($_FILES['results_file']) && $_FILES['results_file']['error'] === UPLOAD_ERR_OK) {
                    $results_filename = time() . '_results_' . basename($_FILES['results_file']['name']);
                    $results_path = $upload_dir . $results_filename;
                    if (move_uploaded_file($_FILES['results_file']['tmp_name'], $results_path)) {
                        $results_file = $results_filename;
                    }
                }
                
                if (!$cv_file) {
                    $error = "CV file is required for staff registration";
                } elseif (($role === 'staff_cs' || $role === 'staff_goods') && !$results_file) {
                    $error = "Academic results file is required for this position";
                }
            }
            
            if (!$error) {
                // Insert new user
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $status = ($role === 'user') ? 'approved' : 'pending';
                
                $query = "INSERT INTO users (username, email, password, full_name, phone, address, role, status, cv_file, results_file) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $db->prepare($query);
                
                if ($stmt->execute([$username, $email, $hashed_password, $full_name, $phone, $address, $role, $status, $cv_file, $results_file])) {
                    if ($role === 'user') {
                        $success = "Registration successful! You can now login.";
                    } else {
                        $success = "Registration submitted! Your application is pending admin approval.";
                    }
                } else {
                    $error = "Registration failed. Please try again.";
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🧑‍🍳 Register - Godswill</title>
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
                <h1><a href="../index.html" style="color: #31c205; text-decoration: none;">🧑‍🍳 Godswill</a></h1>
            </div>
            <nav class="nav">
                <a href="login.php">Login</a>
                <a href="../index.php">Home</a>
            </nav>
        </div>
    </header>

    <main class="main">
        <div class="form-container">
            <h2 style="text-align: center; margin-bottom: 30px; color: #2c3e50;">Create Account</h2>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="role">Account Type</label>
                    <select name="role" id="role" required onchange="toggleFileUploads()">
                        <option value="user">Customer</option>
                        <option value="staff_goods">Staff - Meal Management</option>
                        <option value="staff_cs">Staff - Customer Service</option>
                        <option value="delivery">Delivery Personnel</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" name="username" id="username" required>
                </div>

                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" name="email" id="email" required>
                </div>

                <div class="form-group">
                    <label for="full_name">Full Name</label>
                    <input type="text" name="full_name" id="full_name" required>
                </div>

                <div class="form-group">
                    <label for="phone">Phone</label>
                    <input type="tel" name="phone" id="phone">
                </div>

                <div class="form-group">
                    <label for="address">Address</label>
                    <textarea name="address" id="address" rows="3"></textarea>
                </div>

                <!-- File upload fields for staff -->
                <div id="file-uploads" style="display: none;">
                    <div class="form-group">
                        <label for="cv_file">CV/Resume (Required for staff positions)</label>
                        <input type="file" name="cv_file" id="cv_file" accept=".pdf,.doc,.docx">
                        <small style="color: #666;">Upload your CV in PDF or Word format</small>
                    </div>
                    
                    <div class="form-group" id="results-upload" style="display: none;">
                        <label for="results_file">Academic Results (Required for CS and Meal Management)</label>
                        <input type="file" name="results_file" id="results_file" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                        <small style="color: #666;">Upload your academic certificates/transcripts</small>
                    </div>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" name="password" id="password" required>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" name="confirm_password" id="confirm_password" required>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%;">Register</button>
            </form>

            <p style="text-align: center; margin-top: 20px;">
                Already have an account? <a href="login.php" style="color: #3498db;">Login here</a>
            </p>
        </div>
    </main>
              <!-- Footer -->
    <footer style="background: linear-gradient(135deg, #023f05 0%, #31c205 100%); color: white; padding: 1rem 0; text-align: center;">
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> Godswill Meal System. Developed by Kayode Ilesanmi, Lawal Fawas and Areo David</p>
        </div>
    </footer>
    <script>
        function toggleFileUploads() {
            const role = document.getElementById('role').value;
            const fileUploads = document.getElementById('file-uploads');
            const resultsUpload = document.getElementById('results-upload');
            const cvFile = document.getElementById('cv_file');
            const resultsFile = document.getElementById('results_file');
            
            if (role === 'user') {
                fileUploads.style.display = 'none';
                cvFile.required = false;
                resultsFile.required = false;
            } else {
                fileUploads.style.display = 'block';
                cvFile.required = true;
                
                if (role === 'staff_cs' || role === 'staff_goods') {
                    resultsUpload.style.display = 'block';
                    resultsFile.required = true;
                } else {
                    resultsUpload.style.display = 'none';
                    resultsFile.required = false;
                }
            }
        }
    </script>
</body>
</html>
