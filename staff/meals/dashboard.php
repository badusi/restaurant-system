<?php
require_once '../../config/database.php';
require_once '../../config/session.php';

requireRole('staff_goods');

$database = new Database();
$db = $database->getConnection();

// Get Meals
$query = "SELECT m.*, c.name as category_name FROM meals m 
          LEFT JOIN categories c ON m.category_id = c.id 
          ORDER BY m.created_at DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$meals = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get categories
$query = "SELECT * FROM categories ORDER BY name";
$stmt = $db->prepare($query);
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🧑‍🍳 Staff Dashboard - Meal Management</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
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
                <h1>🧑‍🍳 Godswill - Staff Portal</h1>
            </div>
            <nav class="nav">
                <span>Welcome, <?php echo $_SESSION['full_name']; ?> (Meal Manager)</span>
                <a href="../../auth/logout.php">Logout</a>
            </nav>
        </div>
    </header>

    <div class="dashboard">
        <aside class="sidebar">
            <ul>
                <li><a href="dashboard.php" class="active">Meal</a></li>
                <li><a href="add-meal.php">Add Meal</a></li>
                <li><a href="categories.php">Categories</a></li>
                <li><a href="inventory.php">Inventory</a></li>
            </ul>
        </aside>

        <main class="content">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
                <h2>Meal Management</h2>
                <a href="add-meal.php" class="btn btn-primary">+ Add New Meal</a>
            </div>
            
            <div class="data-table">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Discount Price</th>
                            <th>Stock</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($meals as $meal): ?>
                            <tr>
                                <td><?php echo $meal['id']; ?></td>
                                <td><?php echo htmlspecialchars($meal['name']); ?></td>
                                <td><?php echo htmlspecialchars($meal['category_name']); ?></td>
                                <td>₦<?php echo number_format($meal['price'], 2); ?></td>
                                <td>
                                    <?php if ($meal['discount_price']): ?>
                                        ₦<?php echo number_format($meal['discount_price'], 2); ?>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $meal['stock_quantity']; ?></td>
                                <td>
                                    <span class="status-badge <?php echo $meal['status'] === 'active' ? 'status-completed' : 'status-pending'; ?>">
                                        <?php echo ucfirst($meal['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="edit-meal.php?id=<?php echo $meal['id']; ?>" class="btn btn-secondary" style="margin-right: 5px;">Edit</a>
                                    <button onclick="deleteMeal(<?php echo $meal['id']; ?>)" class="btn" style="background: #e74c3c; color: white;">Delete</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
              <!-- Footer -->
    <footer style="background: linear-gradient(135deg, #023f05 0%, #31c205 100%); color: white; padding: 1rem 0; text-align: center;">
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> Godswill Meal System. Developed by Kayode Ilesanmi, Lawal Fawas and Areo David</p>
        </div>
    </footer>
    <script>
        function deleteMeal(mealId) {
            if (confirm('Are you sure you want to delete this Meal?')) {
                fetch('../../api/meals.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'delete',
                        meal_id: mealId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                });
            }
        }
    </script>
</body>
</html>
