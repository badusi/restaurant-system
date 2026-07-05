<?php
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../config/stripe.php';

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

// Handle payment processing
if ($_POST) {
    $payment_method = $_POST['payment_method'] ?? '';
    
    try {
        switch ($payment_method) {
            case 'stripe':
                // Stripe payment processing
                $payment_success = processStripePayment($order, $_POST);
                break;
                
            case 'paypal':
                // PayPal payment processing
                $payment_success = processPayPalPayment($order, $_POST);
                break;
                
            case 'razorpay':
                // Razorpay payment processing
                $payment_success = processRazorpayPayment($order, $_POST);
                break;
                
            default:
                $payment_error = "Invalid payment method selected";
        }
        
        if ($payment_success) {
            // Update order status
            $query = "UPDATE orders SET payment_status = 'completed', status = 'processing' WHERE id = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$order_id]);
            
            // Create transaction record
            $transaction_id = 'TXN_' . time() . '_' . $order_id;
            $query = "INSERT INTO transactions (order_id, transaction_id, amount, status, payment_gateway) VALUES (?, ?, ?, 'success', ?)";
            $stmt = $db->prepare($query);
            $stmt->execute([$order_id, $transaction_id, $order['total_amount'], $payment_method]);
        }
        
    } catch (Exception $e) {
        $payment_error = $e->getMessage();
    }
}

function processStripePayment($order, $post_data) {
    // This is a simplified Stripe integration
    // In production, you would use Stripe's PHP SDK
    
    $stripe_token = $post_data['stripeToken'] ?? '';
    
    if (empty($stripe_token)) {
        throw new Exception("Invalid payment token");
    }
    
    // Simulate Stripe API call
    // In real implementation, you would make actual API calls to Stripe
    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => "https://api.stripe.com/v1/charges",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query([
            'amount' => $order['total_amount'] * 100, // Stripe uses cents
            'currency' => 'usd',
            'source' => $stripe_token,
            'description' => 'Order #' . $order['id']
        ]),
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . STRIPE_SECRET_KEY,
            'Content-Type: application/x-www-form-urlencoded'
        ]
    ]);
    
    $response = curl_exec($curl);
    $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    
    if ($http_code === 200) {
        return true;
    } else {
        throw new Exception("Payment failed. Please try again.");
    }
}

function processPayPalPayment($order, $post_data) {
    // PayPal payment processing
    $paypal_payment_id = $post_data['paypal_payment_id'] ?? '';
    
    if (empty($paypal_payment_id)) {
        throw new Exception("Invalid PayPal payment ID");
    }
    
    // Simulate PayPal API verification
    // In real implementation, you would verify the payment with PayPal API
    return true;
}

function processRazorpayPayment($order, $post_data) {
    // Razorpay payment processing
    $razorpay_payment_id = $post_data['razorpay_payment_id'] ?? '';
    $razorpay_signature = $post_data['razorpay_signature'] ?? '';
    
    if (empty($razorpay_payment_id) || empty($razorpay_signature)) {
        throw new Exception("Invalid Razorpay payment details");
    }
    
    // Simulate Razorpay signature verification
    // In real implementation, you would verify the signature
    return true;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🧑‍🍳 Payment Gateway - Godswill</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="https://js.stripe.com/v3/"></script>
    <script src="https://www.paypal.com/sdk/js?client-id=<?php echo PAYPAL_CLIENT_ID; ?>"></script>
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
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
        <main class="content" style="max-width: 800px; margin: 0 auto;">
            <?php if ($payment_success): ?>
                <div style="text-align: center; background: white; padding: 50px; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.1);">
                    <div style="color: #27ae60; font-size: 4rem; margin-bottom: 20px;">✓</div>
                    <h2 style="color: #27ae60; margin-bottom: 20px;">Payment Successful!</h2>
                    <p style="margin-bottom: 30px;">Your payment has been processed successfully. Order #<?php echo $order_id; ?> is now being prepared.</p>
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
                        <a href="payment-gateway.php?order_id=<?php echo $order_id; ?>" class="btn btn-primary" style="margin-right: 10px;">Try Again</a>
                        <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
                    </div>
                </div>
            <?php else: ?>
                <h2>Secure Payment Gateway</h2>
                
                <div style="background: white; padding: 30px; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); margin-bottom: 30px;">
                    <h3>Order Summary</h3>
                    <p><strong>Order ID:</strong> #<?php echo $order_id; ?></p>
                    <p><strong>Amount:</strong> $<?php echo number_format($order['total_amount'], 2); ?></p>
                </div>

                <!-- Payment Method Selection -->
                <div style="background: white; padding: 30px; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.1);">
                    <h3>Choose Payment Method</h3>
                    
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin: 30px 0;">
                        <!-- Stripe Payment -->
                        <div class="payment-option" style="border: 2px solid #ddd; border-radius: 10px; padding: 20px; text-align: center; cursor: pointer;" onclick="selectPayment('stripe')">
                            <h4>Credit/Debit Card</h4>
                            <p style="color: #666;">Powered by Stripe</p>
                            <div style="margin-top: 15px;">
                                <img src="https://via.placeholder.com/40x25/1a73e8/ffffff?text=VISA" alt="Visa" style="margin: 0 5px;">
                                <img src="https://via.placeholder.com/40x25/eb001b/ffffff?text=MC" alt="Mastercard" style="margin: 0 5px;">
                                <img src="https://via.placeholder.com/40x25/006fcf/ffffff?text=AMEX" alt="Amex" style="margin: 0 5px;">
                            </div>
                        </div>

                        <!-- PayPal Payment -->
                        <div class="payment-option" style="border: 2px solid #ddd; border-radius: 10px; padding: 20px; text-align: center; cursor: pointer;" onclick="selectPayment('paypal')">
                            <h4>PayPal</h4>
                            <p style="color: #666;">Pay with your PayPal account</p>
                            <div style="margin-top: 15px;">
                                <img src="https://via.placeholder.com/80x25/0070ba/ffffff?text=PayPal" alt="PayPal">
                            </div>
                        </div>

                        <!-- Razorpay Payment -->
                        <div class="payment-option" style="border: 2px solid #ddd; border-radius: 10px; padding: 20px; text-align: center; cursor: pointer;" onclick="selectPayment('razorpay')">
                            <h4>UPI/Wallet</h4>
                            <p style="color: #666;">Powered by Razorpay</p>
                            <div style="margin-top: 15px;">
                                <img src="https://via.placeholder.com/40x25/ff6600/ffffff?text=UPI" alt="UPI" style="margin: 0 5px;">
                                <img src="https://via.placeholder.com/40x25/00baf2/ffffff?text=GPay" alt="GPay" style="margin: 0 5px;">
                            </div>
                        </div>
                    </div>

                    <!-- Stripe Payment Form -->
                    <div id="stripe-form" style="display: none;">
                        <h4>Card Payment</h4>
                        <form id="stripe-payment-form">
                            <div id="stripe-card-element" style="padding: 15px; border: 1px solid #ddd; border-radius: 5px; margin: 15px 0;">
                                <!-- Stripe Elements will create form elements here -->
                            </div>
                            <div id="stripe-card-errors" role="alert" style="color: #e74c3c; margin: 10px 0;"></div>
                            <button type="submit" id="stripe-submit" class="btn btn-primary" style="width: 100%;">
                                Pay $<?php echo number_format($order['total_amount'], 2); ?>
                            </button>
                        </form>
                    </div>

                    <!-- PayPal Payment Form -->
                    <div id="paypal-form" style="display: none;">
                        <h4>PayPal Payment</h4>
                        <div id="paypal-button-container" style="margin: 20px 0;"></div>
                    </div>

                    <!-- Razorpay Payment Form -->
                    <div id="razorpay-form" style="display: none;">
                        <h4>UPI/Wallet Payment</h4>
                        <button id="razorpay-button" class="btn btn-primary" style="width: 100%; margin: 20px 0;">
                            Pay $<?php echo number_format($order['total_amount'], 2); ?> with Razorpay
                        </button>
                    </div>
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
        let selectedPayment = '';

        function selectPayment(method) {
            selectedPayment = method;
            
            // Reset all payment options
            document.querySelectorAll('.payment-option').forEach(option => {
                option.style.borderColor = '#ddd';
            });
            
            // Hide all forms
            document.getElementById('stripe-form').style.display = 'none';
            document.getElementById('paypal-form').style.display = 'none';
            document.getElementById('razorpay-form').style.display = 'none';
            
            // Highlight selected option and show form
            event.target.closest('.payment-option').style.borderColor = '#3498db';
            document.getElementById(method + '-form').style.display = 'block';
            
            // Initialize payment method
            if (method === 'stripe') {
                initializeStripe();
            } else if (method === 'paypal') {
                initializePayPal();
            } else if (method === 'razorpay') {
                initializeRazorpay();
            }
        }

        // Stripe Integration
        function initializeStripe() {
            const stripe = Stripe('<?php echo STRIPE_PUBLISHABLE_KEY; ?>');
            const elements = stripe.elements();
            
            const cardElement = elements.create('card');
            cardElement.mount('#stripe-card-element');
            
            const form = document.getElementById('stripe-payment-form');
            form.addEventListener('submit', async (event) => {
                event.preventDefault();
                
                const {token, error} = await stripe.createToken(cardElement);
                
                if (error) {
                    document.getElementById('stripe-card-errors').textContent = error.message;
                } else {
                    // Submit token to server
                    const formData = new FormData();
                    formData.append('payment_method', 'stripe');
                    formData.append('stripeToken', token.id);
                    
                    fetch('payment-gateway.php?order_id=<?php echo $order_id; ?>', {
                        method: 'POST',
                        body: formData
                    }).then(() => {
                        location.reload();
                    });
                }
            });
        }

        // PayPal Integration
        function initializePayPal() {
            paypal.Buttons({
                createOrder: function(data, actions) {
                    return actions.order.create({
                        purchase_units: [{
                            amount: {
                                value: '<?php echo $order['total_amount']; ?>'
                            }
                        }]
                    });
                },
                onApprove: function(data, actions) {
                    return actions.order.capture().then(function(details) {
                        // Submit payment details to server
                        const formData = new FormData();
                        formData.append('payment_method', 'paypal');
                        formData.append('paypal_payment_id', data.orderID);
                        
                        fetch('payment-gateway.php?order_id=<?php echo $order_id; ?>', {
                            method: 'POST',
                            body: formData
                        }).then(() => {
                            location.reload();
                        });
                    });
                }
            }).render('#paypal-button-container');
        }

        // Razorpay Integration
        function initializeRazorpay() {
            document.getElementById('razorpay-button').onclick = function() {
                const options = {
                    key: '<?php echo RAZORPAY_KEY_ID; ?>',
                    amount: <?php echo $order['total_amount'] * 100; ?>, // Amount in paise
                    currency: 'USD',
                    name: 'Godswill',
                    description: 'Order #<?php echo $order_id; ?>',
                    handler: function(response) {
                        // Submit payment details to server
                        const formData = new FormData();
                        formData.append('payment_method', 'razorpay');
                        formData.append('razorpay_payment_id', response.razorpay_payment_id);
                        formData.append('razorpay_signature', response.razorpay_signature);
                        
                        fetch('payment-gateway.php?order_id=<?php echo $order_id; ?>', {
                            method: 'POST',
                            body: formData
                        }).then(() => {
                            location.reload();
                        });
                    },
                    prefill: {
                        name: '<?php echo $_SESSION['full_name']; ?>',
                        email: '<?php echo $_SESSION['email'] ?? ''; ?>'
                    }
                };
                
                const rzp = new Razorpay(options);
                rzp.open();
            };
        }
    </script>
</body>
</html>
