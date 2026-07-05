<?php
require_once '../config/database.php';
require_once '../config/session.php';

$database = new Database();
$db = $database->getConnection();

$error = '';

if ($_POST) {
  $username = trim($_POST['username']);
  $password = $_POST['password'];

  if (empty($username) || empty($password)) {
      $error = "Please enter both username and password";
  } else {
      $query = "SELECT id, username, password, full_name, role, status FROM users WHERE username = ? OR email = ?";
      $stmt = $db->prepare($query);
      $stmt->execute([$username, $username]);
      
      if ($stmt->rowCount() > 0) {
          $user = $stmt->fetch(PDO::FETCH_ASSOC);
          
          if (password_verify($password, $user['password'])) {
              $_SESSION['user_id'] = $user['id'];
              $_SESSION['username'] = $user['username'];
              $_SESSION['full_name'] = $user['full_name'];
              $_SESSION['role'] = $user['role'];
              $_SESSION['status'] = $user['status'];
              
              // Redirect based on role
              switch ($user['role']) {
                  case 'admin':
                      header('Location: ../admin/dashboard.php');
                      break;
                  case 'staff_goods':
                      header('Location: ../staff/meals/dashboard.php');
                      break;
                  case 'staff_cs':
                      header('Location: ../staff/cs/dashboard.php');
                      break;
                  case 'delivery':
                      header('Location: ../delivery/dashboard.php');
                      break;
                  default:
                      header('Location: ../user/dashboard.php');
              }
              exit();
          } else {
              $error = "Invalid password";
          }
      } else {
          $error = "User not found";
      }
  }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>🧑‍🍳 Login - Godswill</title>
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
              <h1><a href="../index.php" style="color: #31c205; text-decoration: none;">🧑‍🍳 Godswill</a></h1>
          </div>
          <nav class="nav">
              <a href="register.php">Register</a>
              <a href="../index.php">Home</a>
          </nav>
      </div>
  </header>

  <main class="main">
      <div class="form-container">
          <h2 style="text-align: center; margin-bottom: 30px; color: #2c3e50;">Login</h2>
          
          <?php if ($error): ?>
              <div class="alert alert-error"><?php echo $error; ?></div>
          <?php endif; ?>

          <form method="POST">
              <div class="form-group">
                  <label for="username">Username or Email</label>
                  <input type="text" name="username" id="username" required>
              </div>

              <div class="form-group">
                  <label for="password">Password</label>
                  <input type="password" name="password" id="password" required>
              </div>

              <button type="submit" class="btn btn-primary" style="width: 100%;">Login</button>
          </form>

          <p style="text-align: center; margin-top: 20px;">
              Don't have an account? <a href="register.php" style="color: #3498db;">Register here</a>
          </p>
      </div>
  </main>
        <!-- Footer -->
    <footer style="background: linear-gradient(135deg, #023f05 0%, #31c205 100%); color: white; padding: 1rem 0; text-align: center;">
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> Godswill Meal System. Developed by Kayode Ilesanmi, Lawal Fawas and Areo David</p>
        </div>
    </footer>
</body>
</html>
