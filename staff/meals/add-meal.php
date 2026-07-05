<?php
require_once '../../config/database.php';
require_once '../../config/session.php';

requireRole('staff_goods');

$database = new Database();
$db = $database->getConnection();

// Get categories
$query = "SELECT * FROM categories ORDER BY name";
$stmt = $db->prepare($query);
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

$error = '';
$success = '';

if ($_POST) {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $price = floatval($_POST['price']);
    $discount_price = !empty($_POST['discount_price']) ? floatval($_POST['discount_price']) : null;
    $category_id = intval($_POST['category_id']);
    $stock_quantity = intval($_POST['stock_quantity']);

    // image uploads or url
   $image_url = trim($_POST['image_url'] ?? '');
    $image_file = null;

    if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['image_file']['tmp_name'];
        $fileName = $_FILES['image_file']['name'];
        $fileExt = pathinfo($fileName, PATHINFO_EXTENSION);
        $newFileName = uniqid() . '.' . $fileExt;

        $uploadFileDir = '../../uploads/';
        $destPath = $uploadFileDir . $newFileName;

        if (!is_dir($uploadFileDir)) {
            mkdir($uploadFileDir, 0755, true);
        }

        if (move_uploaded_file($fileTmpPath, $destPath)) {
            $image_file = $newFileName; // store only the filename in the DB
        } else {
            $error = "Error uploading the image.";
        }
    }


    $status = $_POST['status'];
    $availability = $_POST['availability'];
    $delivery_time_min = intval($_POST['delivery_time_min']);
    $delivery_time_max = intval($_POST['delivery_time_max']);

    if (empty($name) || empty($description) || $price <= 0 || $category_id <= 0) {
        $error = "Please fill all required fields with valid values";
    } elseif ($delivery_time_min <= 0 || $delivery_time_max <= 0 || $delivery_time_min > $delivery_time_max) {
        $error = "Please enter valid delivery time (min should be less than or equal to max)";
    } else {
        try {
            $query = "INSERT INTO meals (name, description, price, discount_price, category_id, stock_quantity, image_url, image_file, status, availability, delivery_time_min, delivery_time_max, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $db->prepare($query);
            $stmt->execute([$name, $description, $price, $discount_price, $category_id, $stock_quantity, $image_url, $image_file, $status, $availability, $delivery_time_min, $delivery_time_max, $_SESSION['user_id']]);
            
            $success = "Meal added successfully!";
            
            // Clear form
            $_POST = [];
        } catch (Exception $e) {
            $error = "Failed to add Meal. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🧑‍🍳 Add Meal - Staff Portal</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        .dashboard {
            min-height: 100vh;
            overflow-y: auto;
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
                <li><a href="dashboard.php">Meal</a></li>
                <li><a href="add-meal.php" class="active">Add Meal</a></li>
                <li><a href="categories.php">Categories</a></li>
                <li><a href="inventory.php">Inventory</a></li>
            </ul>
        </aside>

        <main class="content">
            <h2>Add New Meal</h2>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>

            <div style="background: white; padding: 30px; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); max-width: 600px;">
                <form method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="name">Meal Name *</label>
                        <input type="text" name="name" id="name" value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="description">Description *</label>
                        <textarea name="description" id="description" rows="4" required><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="category_id">Category *</label>
                        <select name="category_id" id="category_id" required>
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>" <?php echo (($_POST['category_id'] ?? '') == $category['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <div class="form-group">
                            <label for="price">Regular Price (₦) *</label>
                            <input type="number" name="price" id="price" step="0.01" min="0" value="<?php echo $_POST['price'] ?? ''; ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="discount_price">Discount Price (₦)</label>
                            <input type="number" name="discount_price" id="discount_price" step="0.01" min="0" value="<?php echo $_POST['discount_price'] ?? ''; ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="stock_quantity">Stock Quantity *</label>
                        <input type="number" name="stock_quantity" id="stock_quantity" min="0" value="<?php echo $_POST['stock_quantity'] ?? '0'; ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="availability">Availability *</label>
                        <select name="availability" id="availability" required onchange="updateDeliverytime()">
                            <option value="dine-in" <?php echo (($_POST['availability'] ?? 'dine-in') == 'dine-in') ? 'selected' : ''; ?>>Dine in</option>
                            <option value="takeaway" <?php echo (($_POST['availability'] ?? '') == 'takeaway') ? 'selected' : ''; ?>>Takeaway</option>
                            <option value="delivery" <?php echo (($_POST['availability'] ?? '') == 'delivery') ? 'selected' : ''; ?>>Delivery</option>
                        </select>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <div class="form-group">
                            <label for="delivery_time_min">Min Delivery time *</label>
                            <input type="number" name="delivery_time_min" id="delivery_time_min" min="15" value="<?php echo $_POST['delivery_time_min'] ?? '15'; ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="delivery_time_max">Max Delivery time *</label>
                            <input type="number" name="delivery_time_max" id="delivery_time_max" min="840" value="<?php echo $_POST['delivery_time_max'] ?? '840'; ?>" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="image_file">Upload Image</label>
                        <input type="file" name="image_file" id="image_file" accept="image/*">
                    </div>

                    <div class="form-group">
                        <label for="image_url">Or Enter Image URL</label>
                        <input type="url" name="image_url" id="image_url" value="<?php echo htmlspecialchars($_POST['image_url'] ?? ''); ?>" placeholder="https://example.com/image.jpg">
                    </div>


                    <div class="form-group">
                        <label for="status">Status</label>
                        <select name="status" id="status">
                            <option value="active" <?php echo (($_POST['status'] ?? 'active') == 'active') ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo (($_POST['status'] ?? '') == 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>

                    <div style="display: flex; gap: 15px; margin-top: 30px;">
                        <button type="submit" class="btn btn-primary">Add Meal</button>
                        <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </main>
    </div>
              <!-- Footer -->
    <footer style="background: linear-gradient(135deg, #023f05 0%, #31c205 100%); color: white; padding: 1rem 0; text-align: center;">
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> Godswill Meal System. Developed by Badusi</p>
        </div>
    </footer>
    <script>
        function updateDeliverytime() {
            const availability = document.getElementById('availability').value;
            const mintime = document.getElementById('delivery_time_min');
            const maxtime = document.getElementById('delivery_time_max');
            
            if (availability === 'store') {
                mintime.value = 15;
                maxtime.value = 30;
            } else {
                mintime.value = 60;
                maxtime.value = 840;
            }
        }
    </script>
</body>
</html>
