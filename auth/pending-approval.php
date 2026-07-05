<?php
require_once '../config/session.php';
requireLogin();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🧑‍🍳 Pending Approval - Godswill</title>
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
                <h1>🧑‍🍳 Godswill</h1>
            </div>
            <nav class="nav">
                <a href="logout.php">Logout</a>
            </nav>
        </div>
    </header>

    <main class="main">
        <div class="form-container" style="text-align: center;">
            <div style="font-size: 4rem; margin-bottom: 20px;">⏳</div>
            <h2>Application Pending Approval</h2>
            <p style="margin: 20px 0; color: #666;">
                Thank you for your application! Your registration is currently being reviewed by our administrators.
            </p>
            <p style="margin: 20px 0; color: #666;">
                You will receive an email notification once your application has been approved or if additional information is needed.
            </p>
            <div style="background: #f8f9fa; padding: 20px; border-radius: 5px; margin: 20px 0;">
                <h4>What happens next?</h4>
                <ul style="text-align: left; margin: 10px 0;">
                    <li>Admin reviews your credentials and documents</li>
                    <li>Background verification (if applicable)</li>
                    <li>Approval decision within 2-3 business days</li>
                    <li>Email notification with next steps</li>
                </ul>
            </div>
            <a href="logout.php" class="btn btn-primary">Logout</a>
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
