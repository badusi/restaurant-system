<?php
session_start();
if (isset($_POST['confirm_logout'])) {
    session_destroy(); // Destroy session
    header("Location: /restaurant-system/index.php"); // Redirect to login page
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🧑‍🍳 Logout Confirmation</title>
    <script>
        function confirmLogout() {
            if (confirm("Are you sure you want to log out?")) {
                document.getElementById("logoutForm").submit();
            } else {
                window.history.back(); // Goes back to previous page
            }
        }
    </script>
</head>
<body onload="confirmLogout()">
    <form id="logoutForm" method="POST" action="logout_redirect.php">
        <input type="hidden" name="confirm_logout" value="1">
    </form>
</body>
</html>
