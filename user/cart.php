<?php
require_once '../config/database.php';
require_once '../config/session.php';

requireRole('user');

$database = new Database();
$db = $database->getConnection();

// Get cart meals
$query = "SELECT c.*, m.name, m.description, m.price, m.discount_price, m.image_url , m.image_file
          FROM cart c 
          JOIN meals m ON c.meal_id = m.id 
          WHERE c.user_id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$_SESSION['user_id']]);
$cart_meals = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total = 0;
foreach ($cart_meals as $meal) {
    $price = $meal['discount_price'] ?: $meal['price'];
    $total += $price * $meal['quantity'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🧑‍🍳 Shopping Cart - Godswill</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
         .dashboard {
            height: auto;
            overflow-y: visible;
        }
        .cart-meal {
            display: flex;
            align-items: center;
            background: white;
            padding: 15px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .cart-meal-image {
            width: 100px;
            height: 100px;
            margin-right: 20px;
            border-radius: 10px;
            overflow: hidden;
            background: #f4f4f4;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .cart-meal-info {
            flex-grow: 1;
        }
        .cart-meal-price {
            font-size: 1.2rem;
            font-weight: bold;
            color: #e74c3c;
        }
        .quantity-controls {
            display: flex;
            align-items: center;
            margin-top: 10px;
        }
        .quantity-btn {
            background: #3498db;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 3px;
            cursor: pointer;
        }
        .quantity-btn:hover {
            background: #2980b9;
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
                <span>Welcome, <?php echo $_SESSION['full_name']; ?></span>
                <a href="dashboard.php">🍴 Dashboard</a>
                <a href="orders.php">Orders</a>
                <a href="chat.php">Support</a>
                <a href="../auth/logout.php">Logout</a>
            </nav>
        </div>
    </header>

    <div class="dashboard">
        <aside class="sidebar">
            <ul>
                <li><a href="dashboard.php">🍴 Dashboard</a></li>
                <li><a href="cart.php" class="active">🧺 Meal Cart</a></li>
                <li><a href="orders.php">My Orders</a></li>
                <li><a href="chat.php">Customer Support</a></li>
                <li><a href="profile.php">Profile</a></li>
            </ul>
        </aside>

        <main class="content">
            <h2>Shopping Cart</h2>
            
            <?php if (empty($cart_meals)): ?>
                <div style="text-align: center; padding: 50px;">
                    <h3>Your cart is empty</h3>
                    <p>Start shopping to add meals to your cart</p>
                    <a href="dashboard.php" class="btn btn-primary">Continue Shopping</a>
                </div>
            <?php else: ?>
                <div style="margin-bottom: 30px;">
                    <?php foreach ($cart_meals as $meal): ?>
                        <div class="cart-meal">
                            <div class="cart-meal-image">
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
                            <div class="cart-meal-info">
                                <h4><?php echo htmlspecialchars($meal['name']); ?></h4>
                                <p><?php echo htmlspecialchars($meal['description']); ?></p>
                                <div class="cart-meal-price">
                                    <?php 
                                    $price = $meal['discount_price'] ?: $meal['price'];
                                    echo '₦' . number_format($price, 2);
                                    ?>
                                </div>
                                <div class="quantity-controls">
                                    <button class="quantity-btn" onclick="updateQuantity(<?php echo $meal['id']; ?>, -1)">-</button>
                                    <span style="padding: 0 15px; font-weight: bold;"><?php echo $meal['quantity']; ?></span>
                                    <button class="quantity-btn" onclick="updateQuantity(<?php echo $meal['id']; ?>, 1)">+</button>
                                    <button onclick="removeFromCart(<?php echo $meal['id']; ?>)" style="margin-left: 20px; background: #e74c3c; color: white; border: none; padding: 5px 10px; border-radius: 3px; cursor: pointer;">Remove</button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div style="background: white; padding: 30px; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.1);">
                    <h3 style="margin-bottom: 20px;">Order Summary</h3>
                    <div style="display: flex; justify-content: space-between; font-size: 1.2rem; font-weight: bold; margin-bottom: 20px;">
                        <span>Total: </span>
                        <span style="color: #e74c3c;">₦<?php echo number_format($total, 2); ?></span>
                    </div>
                    <a href="checkout.php" class="btn btn-primary" style="width: 100%; text-align: center; display: block; text-decoration: none;">
                        Proceed to Checkout
                    </a>
                </div>
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
        function updateQuantity(cartId, change) {
            fetch('../api/cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'update',
                    cart_id: cartId,
                    change: change
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

        function removeFromCart(cartId) {
            if (confirm('Are you sure you want to remove this meal?')) {
                fetch('../api/cart.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'remove',
                        cart_id: cartId
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
