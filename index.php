<?php
require_once './config/database.php';

$database = new Database();
$db = $database->getConnection();

// Fetch categories
// Get all active categories
$categories_stmt = $db->query("SELECT id, name FROM categories");
$categories = $categories_stmt->fetchAll(PDO::FETCH_ASSOC);

// Prepare array for meals grouped by category
$meals_by_category = [];

foreach ($categories as $cat) {
    $stmt = $db->prepare("SELECT * FROM meals WHERE category_id = ? ORDER BY id DESC LIMIT 10");
    $stmt->execute([$cat['id']]);
    $meals_by_category[$cat['name']] = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>





<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🧑‍🍳 Godswill Resturant Platform</title>
    <link rel="stylesheet" href="assets/css/style1.css">
    <style>
        /* meal Grid */
        .meal-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 20px;
        margin-top: 20px;
        }

        .meal-card {
        background: white;
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        transition: transform 0.3s;
        }

        .meal-card:hover {
        transform: translateY(-5px);
        }

        .meal-image {
        height: 200px;
        background: #f0f0f0;
        display: flex;
        align-items: center;
        justify-content: center;
        }

        .meal-info {
        padding: 20px;
        }

        .meal-info h4 {
        margin-bottom: 10px;
        color: #2c3e50;
        }

        .meal-price {
        font-size: 1.2rem;
        font-weight: bold;
        color: #e74c3c;
        margin-bottom: 10px;
        }

        .original-price {
        text-decoration: line-through;
        color: #95a5a6;
        margin-right: 10px;
        }

    </style>
</head>
<body>
    <header class="header">
        <div class="container">
            <div class="logo">
                <h1>🧑‍🍳 Godswill Restaurant</h1>
            </div>
            <nav class="nav">
                <a href="auth/login.php">Login</a>
                <a href="auth/register.php">Register</a>
            </nav>
        </div>
    </header>

    <main class="main">
        <section class="hero">
            <div class="container">
                <h2>Welcome to Godswill Resturant</h2>
                <p>Your one-stop destination for all your shopping needs</p>
                <div class="cta-buttons">
                    <a href="auth/register.php" class="btn btn-primary">Get Started</a>
                    <a href="auth/login.php" class="btn btn-secondary">Login</a>
                </div>
            </div>
        </section>

        <section class="meals-showcase">
            <div class="container">
                <h2>Popular Meals</h2>

                <?php foreach ($meals_by_category as $category => $meals): ?>
                    <h3><?php echo htmlspecialchars($category); ?></h3>
                    <div class="meal-grid">
                        <?php foreach ($meals as $meal): ?>
                            <div class="meal-card">
                                <?php
                                    $imageSrc = '';
                                    if (!empty($meal['image_url']) && filter_var($meal['image_url'], FILTER_VALIDATE_URL)) {
                                        $imageSrc = $meal['image_url'];
                                    } elseif (!empty($meal['image_file'])) {
                                        $imageSrc = 'uploads/' . $meal['image_file'];
                                    }
                                ?>
                                <?php if (!empty($imageSrc)): ?>
                                    <img src="<?php echo htmlspecialchars($imageSrc); ?>" alt="<?php echo htmlspecialchars($meal['name']); ?>" style="width: 100%; height: 150px; object-fit: cover;">
                                <?php endif; ?>

                                <h4><?php echo htmlspecialchars($meal['name']); ?></h4>
                                <p><?php echo htmlspecialchars($meal['description']); ?></p>
                                <p>
                                    <?php if ($meal['discount_price']): ?>
                                        <span style="text-decoration: line-through;">₦<?php echo number_format($meal['price'], 2); ?></span>
                                        <span style="color: #e74c3c;">₦<?php echo number_format($meal['discount_price'], 2); ?></span>
                                    <?php else: ?>
                                        ₦<?php echo number_format($meal['price'], 2); ?>
                                    <?php endif; ?>
                                </p>
                                <a href="auth/login.php" class="btn btn-primary">Add to Cart</a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>


        <section class="features">
            <div class="container">
                <h3>Our Services</h3>
                <div class="feature-grid">
                    <div class="feature-card">
                        <h4>Wide Meal Range</h4>
                        <p>Main Dishes, Appetizers, Beverages and more</p>
                    </div>
                    <div class="feature-card">
                        <h4>Secure Payments</h4>
                        <p>Safe and secure payment gateway integration</p>
                    </div>
                    <div class="feature-card">
                        <h4>24/7 Support</h4>
                        <p>Customer service available round the clock</p>
                    </div>
                    <div class="feature-card">
                        <h4>Fast Delivery</h4>
                        <p>Quick and reliable delivery worldwide</p>
                    </div>
                </div>
            </div>
        </section>
    </main>

          <!-- Footer -->
    <footer style="background: linear-gradient(135deg, #023f05 0%, #31c205 100%); color: white; padding: 1rem 0; text-align: center;">
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> Godswill Meal System. Developed by Kayode Ilesanmi, Lawal Fawas and Areo David</p>
        </div>
    </footer>
</body>
</html>
