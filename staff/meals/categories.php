<?php
require_once '../../config/database.php';
require_once '../../config/session.php';

requireRole('staff_goods');

$database = new Database();
$db = $database->getConnection();

// Get categories with Meal count
$query = "SELECT c.*, COUNT(p.id) as meal_count 
          FROM categories c 
          LEFT JOIN Meals p ON c.id = p.category_id 
          GROUP BY c.id 
          ORDER BY c.name";
$stmt = $db->prepare($query);
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

$error = '';
$success = '';

if ($_POST) {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);

    if (empty($name)) {
        $error = "Category name is required";
    } else {
        try {
            $query = "INSERT INTO categories (name, description) VALUES (?, ?)";
            $stmt = $db->prepare($query);
            $stmt->execute([$name, $description]);
            
            $success = "Category added successfully!";
            header('Location: categories.php');
            exit();
        } catch (Exception $e) {
            $error = "Failed to add category. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🧑‍🍳 Categories - Staff Portal</title>
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
                <li><a href="categories.php" class="active">Categories</a></li>
                <li><a href="inventory.php">Inventory</a></li>
            </ul>
        </aside>

        <main class="content">
            <h2>Meal Categories</h2>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">
                <!-- Add Category Form -->
                <div>
                    <h3>Add New Category</h3>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-error"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>

                    <div style="background: white; padding: 20px; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.1);">
                        <form method="POST">
                            <div class="form-group">
                                <label for="name">Category Name *</label>
                                <input type="text" name="name" id="name" required>
                            </div>

                            <div class="form-group">
                                <label for="description">Description</label>
                                <textarea name="description" id="description" rows="3"></textarea>
                            </div>

                            <button type="submit" class="btn btn-primary">Add Category</button>
                        </form>
                    </div>
                </div>

                <!-- Categories List -->
                <div>
                    <h3>Existing Categories</h3>
                    <div class="data-table">
                        <table>
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Meals</th>
                                    <th>Created</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($categories as $category): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($category['name']); ?></strong>
                                            <?php if ($category['description']): ?>
                                                <br><small style="color: #666;"><?php echo htmlspecialchars($category['description']); ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo $category['meal_count']; ?></td>
                                        <td><?php echo date('M j, Y', strtotime($category['created_at'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
          <!-- Footer -->
    <footer style="background: linear-gradient(135deg, #023f05 0%, #31c205 100%); color: white; padding: 1rem 0; text-align: center;">
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> Godswill Meal System. Developed by Kayode Ilesanmi, Lawal Fawas and Areo David</p>
        </div>
    </footer>
</body>
</html>
