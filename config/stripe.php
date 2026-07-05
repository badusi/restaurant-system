<?php
// Stripe Configuration
// Replace with your actual Stripe keys
define('STRIPE_PUBLISHABLE_KEY', 'pk_test_your_publishable_key_here');
define('STRIPE_SECRET_KEY', 'sk_test_your_secret_key_here');

// PayPal Configuration
define('PAYPAL_CLIENT_ID', 'your_paypal_client_id_here');
define('PAYPAL_CLIENT_SECRET', 'your_paypal_client_secret_here');
define('PAYPAL_MODE', 'sandbox'); // 'sandbox' for testing, 'live' for production

// Razorpay Configuration (for international payments)
define('RAZORPAY_KEY_ID', 'your_razorpay_key_id_here');
define('RAZORPAY_KEY_SECRET', 'your_razorpay_key_secret_here');
?>
