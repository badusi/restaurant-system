<?php
require_once '../config/database.php';
require_once '../config/session.php';

requireRole('user');

$database = new Database();
$db = $database->getConnection();

$order_id = $_GET['order_id'] ?? 0;

// Get order details
$query = "SELECT * FROM orders WHERE id = ? AND user_id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$order_id, $_SESSION['user_id']]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    header('Location: orders.php');
    exit();
}

$payment_success = false;
$payment_error = '';

if ($_POST) {
    // Simulate payment processing
    $card_number = $_POST['card_number'] ?? '';
    $expiry = $_POST['expiry'] ?? '';
    $cvv = $_POST['cvv'] ?? '';
    
    // Simple validation (in real implementation, use proper payment gateway)
    if (strlen($card_number) >= 16 && !empty($expiry) && strlen($cvv) >= 3) {
        // Simulate payment success/failure (80% success rate)
        $payment_result = rand(1, 10) <= 8;
        
        if ($payment_result) {
            // Update order and create transaction
            $transaction_id = 'TXN_' . time() . '_' . $order_id;
            
            try {
                $db->beginTransaction();
                
                // Update order
                $query = "UPDATE orders SET payment_status = 'completed', status = 'processing' WHERE id = ?";
                $stmt = $db->prepare($query);
                $stmt->execute([$order_id]);
                
                // Create transaction record
                $query = "INSERT INTO transactions (order_id, transaction_id, amount, status, payment_gateway) VALUES (?, ?, ?, 'success', ?)";
                $stmt = $db->prepare($query);
                $stmt->execute([$order_id, $transaction_id, $order['total_amount'], $order['payment_method']]);
                
                $db->commit();
                $payment_success = true;
                
            } catch (Exception $e) {
                $db->rollBack();
                $payment_error = "Payment processing failed. Please try again.";
            }
        } else {
            // Payment failed
            $query = "UPDATE orders SET payment_status = 'failed' WHERE id = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$order_id]);
            
            $payment_error = "Payment failed. Please check your card details and try again.";
        }
    } else {
        $payment_error = "Please enter valid payment details.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🧑‍🍳 Payment - Godswill</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .dashboard{
            overflow: scroll;
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
                <a href="../auth/logout.php">Logout</a>
            </nav>
        </div>
    </header>

    <div class="dashboard">
        <main class="content" style="max-width: 600px; margin: 0 auto;">
            <?php if ($payment_success): ?>
                <div style="text-align: center; background: white; padding: 50px; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.1);">
                    <div style="color: #27ae60; font-size: 4rem; margin-bottom: 20px;">✓</div>
                    <h2 style="color: #27ae60; margin-bottom: 20px;">Payment Successful!</h2>
                    <p style="margin-bottom: 30px;">Your order has been placed successfully. You will receive a confirmation email shortly.</p>
                    <div style="background: #f8f9fa; padding: 20px; border-radius: 5px; margin-bottom: 30px;">
                        <p><strong>Order ID:</strong> #<?php echo $order_id; ?></p>
                        <p><strong>Amount:</strong> ₦<?php echo number_format($order['total_amount'], 2); ?></p>
                        <p><strong>Status:</strong> Processing</p>
                    </div>
                    <div>
                        <a href="orders.php" class="btn btn-primary" style="margin-right: 10px;">View Orders</a>
                        <a href="dashboard.php" class="btn btn-secondary">Continue Shopping</a>
                    </div>
                </div>
            <?php elseif ($payment_error): ?>
                <div style="text-align: center; background: white; padding: 50px; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.1);">
                    <div style="color: #e74c3c; font-size: 4rem; margin-bottom: 20px;">✗</div>
                    <h2 style="color: #e74c3c; margin-bottom: 20px;">Payment Failed</h2>
                    <div class="alert alert-error"><?php echo $payment_error; ?></div>
                    <div style="margin-top: 30px;">
                        <a href="payment.php?order_id=<?php echo $order_id; ?>" class="btn btn-primary" style="margin-right: 10px;">Try Again</a>
                        <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
                    </div>
                </div>
            <?php else: ?>
                <h2>Payment Gateway</h2>
                
                <div style="background: white; padding: 30px; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.1);">
                    <div style="margin-bottom: 30px; padding: 20px; background: #f8f9fa; border-radius: 5px;">
                        <h3>Order Summary</h3>
                        <p><strong>Order ID:</strong> #<?php echo $order_id; ?></p>
                        <p><strong>Amount:</strong> ₦<?php echo number_format($order['total_amount'], 2); ?></p>
                        <p><strong>Payment Method:</strong> <?php echo ucwords(str_replace('_', ' ', $order['payment_method'])); ?></p>
                    </div>

                    <form method="POST">
                        <h3 style="margin-bottom: 20px;">Payment Details</h3>
                        
                        <div class="form-group">
                            <label for="card_number">Card Number</label>
                            <input type="text" name="card_number" id="card_number" placeholder="1234 5678 9012 3456" maxlength="19" required>
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                            <div class="form-group">
                                <label for="expiry">Expiry Date</label>
                                <input type="text" name="expiry" id="expiry" placeholder="MM/YY" maxlength="5" required>
                            </div>
                            <div class="form-group">
                                <label for="cvv">CVV</label>
                                <input type="text" name="cvv" id="cvv" placeholder="123" maxlength="4" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="cardholder_name">Cardholder Name</label>
                            <input type="text" name="cardholder_name" id="cardholder_name" value="<?php echo htmlspecialchars($_SESSION['full_name']); ?>" required>
                        </div>

                        <button type="submit" class="btn btn-primary" style="width: 100%; font-size: 1.1rem; padding: 15px;">
                            Pay ₦<?php echo number_format($order['total_amount'], 2); ?>
                        </button>
                    </form>

                    <p style="text-align: center; margin-top: 20px; color: #666; font-size: 0.9rem;">
                        <strong>Note:</strong> This is a demo payment gateway. Use any 16-digit number for testing.
                    </p>
                </div>
            <?php endif; ?>
        </main>
    </div>
              <!-- Footer -->
    <footer style="background: linear-gradient(135deg, #023f05 0%, #31c205 100%); color: white; padding: 1rem 0; text-align: center;">
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> Godswill Meal System. Developed by Badusi</p>
        </div>
    </footer>
    <script>
        // Format card number input
        document.getElementById('card_number').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\s/g, '').replace(/[^0-9]/gi, '');
            let formattedValue = value.match(/.{1,4}/g)?.join(' ') || value;
            e.target.value = formattedValue;
        });

        // Format expiry date input
        document.getElementById('expiry').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length >= 2) {
                value = value.substring(0, 2) + '/' + value.substring(2, 4);
            }
            e.target.value = value;
        });

        // Only allow numbers in CVV
        document.getElementById('cvv').addEventListener('input', function(e) {
            e.target.value = e.target.value.replace(/[^0-9]/g, '');
        });
    </script>
</body>
</html>
