<?php
require_once '../../config/database.php';
require_once '../../config/session.php';

requireRole('staff_goods');

$database = new Database();
$db = $database->getConnection();

// Get Meals with low stock (less than 10)
$query = "SELECT p.*, c.name as category_name FROM Meals p 
          LEFT JOIN categories c ON p.category_id = c.id 
          WHERE p.stock_quantity < 10 
          ORDER BY p.stock_quantity ASC";
$stmt = $db->prepare($query);
$stmt->execute();
$low_stock = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all Meals for inventory management
$query = "SELECT p.*, c.name as category_name FROM Meals p 
          LEFT JOIN categories c ON p.category_id = c.id 
          ORDER BY p.name";
$stmt = $db->prepare($query);
$stmt->execute();
$all_Meals = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle stock update
if ($_POST && isset($_POST['update_stock'])) {
    $Meal_id = $_POST['meal_id'];
    $new_stock = intval($_POST['new_stock']);
    
    try {
        $query = "UPDATE meals SET stock_quantity = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$new_stock, $Meal_id]);
        
        header('Location: inventory.php');
        exit();
    } catch (Exception $e) {
        $error = "Failed to update stock";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🧑‍🍳 Inventory Management - Staff Portal</title>
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
                <li><a href="dashboard.php">Meals</a></li>
                <li><a href="add-meal.php">Add Meal</a></li>
                <li><a href="categories.php">Categories</a></li>
                <li><a href="inventory.php" class="active">Inventory</a></li>
            </ul>
        </aside>

        <main class="content">
            <h2>Inventory Management</h2>
            
            <!-- Low Stock Alert -->
            <?php if (!empty($low_stock)): ?>
                <div class="alert alert-error" style="margin-bottom: 30px;">
                    <strong>Low Stock Alert!</strong> <?php echo count($low_stock); ?> Meals have low stock (less than 10 items).
                </div>
                
                <div style="background: white; padding: 20px; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); margin-bottom: 30px;">
                    <h3 style="color: #e74c3c; margin-bottom: 20px;">Low Stock Meals</h3>
                    <div class="data-table">
                        <table>
                            <thead>
                                <tr>
                                    <th>Meal</th>
                                    <th>Category</th>
                                    <th>Current Stock</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($low_stock as $Meal): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($Meal['name']); ?></td>
                                        <td><?php echo htmlspecialchars($Meal['category_name']); ?></td>
                                        <td style="color: #e74c3c; font-weight: bold;"><?php echo $Meal['stock_quantity']; ?></td>
                                        <td>
                                            <button onclick="updateStock(<?php echo $Meal['id']; ?>, <?php echo $Meal['stock_quantity']; ?>)" class="btn btn-primary">Update Stock</button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>

            <!-- All Meals Inventory -->
            <div style="background: white; padding: 20px; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.1);">
                <h3>All Meals Inventory</h3>
                <div class="data-table">
                    <table>
                        <thead>
                            <tr>
                                <th>Meal</th>
                                <th>Category</th>
                                <th>Price</th>
                                <th>Stock</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($all_Meals as $Meal): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($Meal['name']); ?></td>
                                    <td><?php echo htmlspecialchars($Meal['category_name']); ?></td>
                                    <td>₦<?php echo number_format($Meal['price'], 2); ?></td>
                                    <td>
                                        <span style="color: <?php echo $Meal['stock_quantity'] < 10 ? '#e74c3c' : '#27ae60'; ?>; font-weight: bold;">
                                            <?php echo $Meal['stock_quantity']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="status-badge <?php echo $Meal['status'] === 'active' ? 'status-completed' : 'status-pending'; ?>">
                                            <?php echo ucfirst($Meal['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button onclick="updateStock(<?php echo $Meal['id']; ?>, <?php echo $Meal['stock_quantity']; ?>)" class="btn btn-secondary">Update Stock</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <!-- Stock Update Modal -->
    <div id="stockModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000;">
        <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 30px; border-radius: 10px; min-width: 300px;">
            <h3>Update Stock</h3>
            <form method="POST">
                <input type="hidden" name="Meal_id" id="modalMealId">
                <div class="form-group">
                    <label for="new_stock">New Stock Quantity</label>
                    <input type="number" name="new_stock" id="modalNewStock" min="0" required>
                </div>
                <div style="display: flex; gap: 10px; margin-top: 20px;">
                    <button type="submit" name="update_stock" class="btn btn-primary">Update</button>
                    <button type="button" onclick="closeModal()" class="btn btn-secondary">Cancel</button>
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
        function updateStock(MealId, currentStock) {
            document.getElementById('modalMealId').value = MealId;
            document.getElementById('modalNewStock').value = currentStock;
            document.getElementById('stockModal').style.display = 'block';
        }

        function closeModal() {
            document.getElementById('stockModal').style.display = 'none';
        }

        // Close modal when clicking outside
        document.getElementById('stockModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });
    </script>
</body>
</html>
