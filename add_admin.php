<?php
$mysqli = new mysqli('localhost', 'root', '', 'restaurant_system');


if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

// New admin credentials
$username = "admin";
$email = "admin@restaurant.com";  // Change this to the new admin's email
$password = "admin123";    // Change this to a strong password
$full_name = "System Administrator";
$role = "admin";
$status = "approved";

// Hash the password
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// Insert into the database
$stmt = $mysqli->prepare("INSERT INTO users (username, email, password, full_name, role, status) VALUES (?, ?, ?, ?, ?, ?)");
$stmt->bind_param("ssssss", $username, $email, $hashed_password, $full_name, $role, $status);

if ($stmt->execute()) {
    echo "✅ New admin added successfully!";
} else {
    echo "❌ Error: " . $stmt->error;
}

$stmt->close();
$mysqli->close();
?>
