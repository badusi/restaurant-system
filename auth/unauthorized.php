<?php
// Get the current script path to determine correct navigation links
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];
$base_url = $protocol . '://' . $host;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🧑‍🍳 Unauthorized Access - Godswill</title>
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
                <a href="login.php">Login</a>
                <a href="../index.php">Home</a>
            </nav>
        </div>
    </header>

    <main class="main">
        <div style="text-align: center; padding: 100px 20px; max-width: 600px; margin: 0 auto;">
            <div style="font-size: 6rem; color: #e74c3c; margin-bottom: 20px;">🚫</div>
            <h2 style="color: #2c3e50; margin-bottom: 20px;">Access Denied</h2>
            <p style="color: #666; font-size: 1.1rem; margin-bottom: 30px;">
                You don't have permission to access this page. Please contact your administrator if you believe this is an error.
            </p>
            <div>
                <a href="login.php" class="btn btn-primary" style="margin-right: 10px;">Login</a>
                <a href="../index.html" class="btn btn-secondary">Go Home</a>
            </div>
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
