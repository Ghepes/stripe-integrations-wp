<?php
function stripe_integration_handle_checkout() {
  if (!session_id()) {
    session_start();
  }

  header('Content-Type: application/json');

  try {
    if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
      throw new Exception('Cart is empty');
    }

    $api_key = get_option('stripe_integration_settings')['stripe_api_key'];
    if (empty($api_key)) {
      throw new Exception('Stripe API key is not configured');
    }

    require_once plugin_dir_path(__FILE__) . '../vendor/stripe/stripe-php/init.php';
    \Stripe\Stripe::setApiKey($api_key);

    $cart = $_SESSION['cart'];
    $line_items = [];

    foreach ($cart as $product_id => $quantity) {
      $product = \Stripe\Product::retrieve($product_id);
      $price = \Stripe\Price::retrieve($product->default_price);
      $line_items[] = [
        'price' => $price->id,
        'quantity' => $quantity,
      ];
    }

    $session = \Stripe\Checkout\Session::create([
      'payment_method_types' => ['card'],
      'line_items' => $line_items,
      'mode' => 'payment',
      'success_url' => home_url('/checkout-success') . '?session_id={CHECKOUT_SESSION_ID}',
      'cancel_url' => home_url('/checkout-cancel'),

    ]);

    echo json_encode([
      'success' => true,
      'redirect_url' => $session->url
    ]);
    exit;

  } catch (Exception $e) {
    echo json_encode([
      'success' => false,
      'message' => $e->getMessage()
    ]);
    exit;
  }
}

add_action('wp_ajax_stripe_checkout', 'stripe_integration_handle_checkout');
add_action('wp_ajax_nopriv_stripe_checkout', 'stripe_integration_handle_checkout');
?>


