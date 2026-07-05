<?php
require_once '../config/database.php';
require_once '../config/session.php';

requireRole('user');

$database = new Database();
$db = $database->getConnection();

// Get search query if provided
$search_query = $_GET['search'] ?? '';
$category_filter = $_GET['category'] ?? '';

// Build the query with search and filter functionality
$query = "SELECT m.*, c.name as category_name FROM meals m 
          LEFT JOIN categories c ON m.category_id = c.id 
          WHERE m.status = 'active'";

$params = [];

if (!empty($search_query)) {
    $query .= " AND (p.name LIKE ? OR p.description LIKE ? OR c.name LIKE ?)";
    $search_param = '%' . $search_query . '%';
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

if (!empty($category_filter)) {
    $query .= " AND c.id = ?";
    $params[] = $category_filter;
}

$query .= " ORDER BY c.name, m.name";

$stmt = $db->prepare($query);
$stmt->execute($params);
$meals = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Group meals by category
$categories = [];
foreach ($meals as $meal) {
    $categories[$meal['category_name']][] = $meal;
}

// Get all categories for filter dropdown
$cat_query = "SELECT * FROM categories ORDER BY name";
$cat_stmt = $db->prepare($cat_query);
$cat_stmt->execute();
$all_categories = $cat_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get cart count for header
$cart_query = "SELECT SUM(quantity) as cart_count FROM cart WHERE user_id = ?";
$cart_stmt = $db->prepare($cart_query);
$cart_stmt->execute([$_SESSION['user_id']]);
$cart_count = $cart_stmt->fetch(PDO::FETCH_ASSOC)['cart_count'] ?? 0;

// Get user's orders with delivery info
$orders_query = "SELECT o.*, d.full_name as delivery_person_name, d.phone as delivery_person_phone 
                 FROM orders o 
                 LEFT JOIN users d ON o.delivery_person_id = d.id 
                 WHERE o.user_id = ? AND o.status IN ('picked_up', 'out_for_delivery') 
                 ORDER BY o.pickup_time DESC";
$orders_stmt = $db->prepare($orders_query);
$orders_stmt->execute([$_SESSION['user_id']]);
$active_deliveries = $orders_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Godswill</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .dashboard {
            height: auto;
            overflow-y: visible;
        }
        .search-container {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 30px
        }
        
        .search-form {
            display: flex;
            gap: 15px;
            align-items: end;
            flex-wrap: wrap;
        }
        
        .search-input-group {
            flex: 1;
            min-width: 250px;
        }
        
        .search-input-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #2c3e50;
        }
        
        .search-input-group input,
        .search-input-group select {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
        }
        
        .search-buttons {
            display: flex;
            gap: 10px;
        }
        
        .nav-icon {
            margin-right: 8px;
            font-size: 1.1em;
        }
        
        .cart-badge {
            background: #e74c3c;
            color: white;
            border-radius: 50%;
            padding: 2px 6px;
            font-size: 0.8em;
            margin-left: 5px;
        }
        
        .search-results-info {
            background: #e8f4fd;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border-left: 4px solid #31c205;
        }
        
        .clear-search {
            color: #31c205;
            text-decoration: none;
            font-weight: bold;
        }
        
        .clear-search:hover {
            text-decoration: underline;
        }
        
        .delivery-alert {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .delivery-info {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 10px;
            border-radius: 3px;
            margin-top: 10px;
            font-size: 0.9em;
        }
        
        @media (max-width: 768px) {
            .search-form {
                flex-direction: column;
            }
            
            .search-input-group {
                min-width: 100%;
            }
            
            .search-buttons {
                width: 100%;
                justify-content: center;
            }
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
                <h1>🧑‍🍳 Godswill</h1>
            </div>
            <nav class="nav">
                <span>👋 Welcome, <?php echo $_SESSION['full_name']; ?></span>
                <a href="cart.php">
                    <span class="nav-icon">🛒</span>Cart
                    <?php if ($cart_count > 0): ?>
                        <span class="cart-badge"><?php echo $cart_count; ?></span>
                    <?php endif; ?>
                </a>
                <a href="orders.php"><span class="nav-icon">📦</span>Orders</a>
                <a href="chat.php"><span class="nav-icon">💬</span>Support</a>
                <a href="../auth/logout.php"><span class="nav-icon">🚪</span>Logout</a>
            </nav>
        </div>
    </header>

    <div class="dashboard">
        <aside class="sidebar">
            <ul>
                <li><a href="dashboard.php" class="active">🍴 Dashboard</a></li>
                <li><a href="cart.php">🧺 Meal Cart</a></li>
                <li><a href="orders.php">📦 My Orders</a></li>
                <li><a href="chat.php">💬 Customer Support</a></li>
                <li><a href="profile.php">👤 Profile</a></li>
            </ul>
        </aside>

        <main class="content">
            <h2>👨‍🍳 Chef’s Specials</h2>
            
            <!-- Active Deliveries Alert -->
            <?php if (!empty($active_deliveries)): ?>
                <div class="delivery-alert">
                    <h4>🚚 Active Deliveries</h4>
                    <?php foreach ($active_deliveries as $delivery): ?>
                        <div style="margin-bottom: 10px; padding: 10px; background: white; border-radius: 5px;">
                            <strong>Order #<?php echo $delivery['id']; ?></strong> - 
                            Status: <span style="color: #28a745;"><?php echo ucfirst(str_replace('_', ' ', $delivery['status'])); ?></span>
                            <?php if ($delivery['delivery_person_name']): ?>
                                <br>
                                <small>
                                    📞 Delivery Person: <?php echo htmlspecialchars($delivery['delivery_person_name']); ?> 
                                    (<?php echo htmlspecialchars($delivery['delivery_person_phone']); ?>)
                                    <?php if ($delivery['estimated_delivery_time']): ?>
                                        <br>⏰ Estimated Delivery: <?php echo date('M j, g:i A', strtotime($delivery['estimated_delivery_time'])); ?>
                                    <?php endif; ?>
                                </small>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <!-- Search Container -->
            <div class="search-container">
                <form method="GET" class="search-form">
                    <div class="search-input-group">
                        <label for="search">🔍 Search meals</label>
                        <input type="text" 
                               name="search" 
                               id="search" 
                               placeholder="Search by meal name, description, or category..." 
                               value="<?php echo htmlspecialchars($search_query); ?>">
                    </div>
                    
                    <div class="search-input-group" style="min-width: 200px;">
                        <label for="category">📂 Filter by Category</label>
                        <select name="category" id="category">
                            <option value="">All Categories</option>
                            <?php foreach ($all_categories as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>" 
                                        <?php echo ($category_filter == $cat['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="search-buttons">
                        <button type="submit" class="btn btn-primary">🔍 Search</button>
                        <?php if (!empty($search_query) || !empty($category_filter)): ?>
                            <a href="dashboard.php" class="btn btn-secondary">🔄 Clear</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
            
            <!-- Search Results Info -->
            <?php if (!empty($search_query) || !empty($category_filter)): ?>
                <div class="search-results-info">
                    <strong>Search Results:</strong>
                    <?php if (!empty($search_query)): ?>
                        Showing results for "<em><?php echo htmlspecialchars($search_query); ?></em>"
                    <?php endif; ?>
                    <?php if (!empty($category_filter)): ?>
                        <?php 
                        $selected_cat = array_filter($all_categories, function($cat) use ($category_filter) {
                            return $cat['id'] == $category_filter;
                        });
                        $selected_cat = reset($selected_cat);
                        ?>
                        in category "<em><?php echo htmlspecialchars($selected_cat['name']); ?></em>"
                    <?php endif; ?>
                    - Found <?php echo count($meals); ?> meal(s)
                    <a href="dashboard.php" class="clear-search" style="margin-left: 15px;">Clear search</a>
                </div>
            <?php endif; ?>
            
            <?php if (empty($meals)): ?>
                <div style="text-align: center; padding: 50px; background: white; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.1);">
                    <div style="font-size: 4rem; margin-bottom: 20px;">🔍</div>
                    <h3>No meals found</h3>
                    <p>Try adjusting your search terms or browse all categories</p>
                    <a href="dashboard.php" class="btn btn-primary" style="margin-top: 20px;">View All meals</a>
                </div>
            <?php else: ?>
                <?php foreach ($categories as $category_name => $category_meals): ?>
                    <section style="margin-bottom: 40px;">
                        <h3 style="color: #2c3e50; margin-bottom: 20px; border-bottom: 2px solid #31c205; padding-bottom: 10px;">
                            📂 <?php echo htmlspecialchars($category_name); ?> (<?php echo count($category_meals); ?> items)
                        </h3>
                        
                        <div class="meal-grid">
                            <?php foreach ($category_meals as $meal): ?>
                                <div class="meal-card">
                                    <div class="meal-image">
                                            <?php
                                            $imageSrc = '';

                                            // 1. Use external URL if valid
                                            if (!empty($meal['image_url']) && filter_var($meal['image_url'], FILTER_VALIDATE_URL)) {
                                                $imageSrc = $meal['image_url'];
                                            }
                                            // 2. Else use uploaded image file if available
                                            elseif (!empty($meal['image_file'])) {
                                                $imageSrc = '/restaurant-system/uploads/' . $meal['image_file'];
                                            }
                                            ?>

                                            <?php if (!empty($imageSrc)): ?>
                                                <img src="<?php echo htmlspecialchars($imageSrc); ?>"
                                                    alt="<?php echo htmlspecialchars($meal['name']); ?>"
                                                    style="width: 100%; height: 100%; object-fit: cover;">
                                            <?php else: ?>
                                                <div style="font-size: 3rem; color: #ccc;">📦</div>
                                            <?php endif; ?>
                                        </div>

                                    <div class="meal-info">
                                        <h4><?php echo htmlspecialchars($meal['name']); ?></h4>
                                        <p style="color: #666; margin-bottom: 10px;"><?php echo htmlspecialchars($meal['description']); ?></p>
                                        <div class="meal-price">
                                            <?php if ($meal['discount_price']): ?>
                                                <span class="original-price">₦<?php echo number_format($meal['price'], 2); ?></span>
                                                💰 ₦<?php echo number_format($meal['discount_price'], 2); ?>
                                            <?php else: ?>
                                                💰 ₦<?php echo number_format($meal['price'], 2); ?>
                                            <?php endif; ?>
                                        </div>
                                        <div style="margin-bottom: 10px; color: #666; font-size: 0.9rem;">
                                            📦 Ready: <?php echo $meal['stock_quantity']; ?> available
                                        </div>
                                        <div class="delivery-info">
                                            <?php if ($meal['availability'] === 'dine-in'): ?>
                                                Dine In - Delivery: <?php echo $meal['delivery_time_min']; ?>-<?php echo $meal['delivery_time_max']; ?> working time
                                            <?php elseif ($meal['availability'] === 'takeaway'): ?>
                                                Takeaway - Delivery: <?php echo $meal['delivery_time_min']; ?>-<?php echo $meal['delivery_time_max']; ?> working time
                                            <?php else: ?>
                                                Delivery: <?php echo $meal['delivery_time_min']; ?>-<?php echo $meal['delivery_time_max']; ?> working time
                                            <?php endif; ?>
                                        </div>
                                        <button onclick="addToCart(<?php echo $meal['id']; ?>)" class="btn btn-primary" style="width: 100%; margin-top: 10px;">
                                            🛒 Add to Cart
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php endforeach; ?>
            <?php endif; ?>
        </main>
    </div>
              <!-- Footer -->
    <footer style="background: linear-gradient(135deg, #023f05 0%, #31c205 100%); color: white; padding: 1rem 0; text-align: center;">
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> Godswill Meal System. Developed by Kayode Ilesanmi, Lawal Fawas and Areo David</p>
        </div>
    </footer>
    <script>
        function addToCart(mealId) {
            fetch('../api/cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'add',
                    meal_id: mealId,
                    quantity: 1
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('✅ meal added to cart!');
                    // Update cart badge
                    location.reload();
                } else {
                    alert('❌ Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('❌ An error occurred');
            });
        }

        // Auto-submit search form when category changes
        document.getElementById('category').addEventListener('change', function() {
            this.form.submit();
        });

        // Add keyboard shortcut for search (Ctrl+K or Cmd+K)
        document.addEventListener('keydown', function(e) {
            if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                e.preventDefault();
                document.getElementById('search').focus();
            }
        });

        // Add search suggestions/autocomplete functionality
        document.getElementById('search').addEventListener('input', function() {
            const query = this.value.toLowerCase();
            if (query.length < 2) return;

            // Simple client-side filtering for instant feedback
            const mealCards = document.querySelectorAll('.meal-card');
            let visibleCount = 0;

            mealCards.forEach(card => {
                const mealName = card.querySelector('h4').textContent.toLowerCase();
                const mealDesc = card.querySelector('p').textContent.toLowerCase();
                
                if (mealName.includes(query) || mealDesc.includes(query)) {
                    card.style.display = 'block';
                    visibleCount++;
                } else {
                    card.style.display = 'none';
                }
            });

            // Hide empty categories
            document.querySelectorAll('section').forEach(section => {
                const visiblemeals = section.querySelectorAll('.meal-card[style*="block"], .meal-card:not([style*="none"])');
                if (visiblemeals.length === 0) {
                    section.style.display = 'none';
                } else {
                    section.style.display = 'block';
                }
            });
        });

        // Reset display when search is cleared
        document.getElementById('search').addEventListener('blur', function() {
            if (this.value === '') {
                document.querySelectorAll('.meal-card, section').forEach(el => {
                    el.style.display = 'block';
                });
            }
        });
    </script>
</body>
</html>

