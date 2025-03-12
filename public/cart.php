<?php
function stripe_integration_display_cart() {
  echo '<div class="stripe-cart-wrapper">';
  echo '<div class="cart-icon">';
  echo '<span class="cart-count">0</span>';
  echo '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path fill="currentColor" d="M17 18a2 2 0 0 1 2 2a2 2 0 0 1-2 2a2 2 0 0 1-2-2c0-1.11.89-2 2-2M1 2h3.27l.94 2H20a1 1 0 0 1 1 1c0 .17-.05.34-.12.5l-3.58 6.47c-.34.61-1 1.03-1.75 1.03H8.1l-.9 1.63l-.03.12a.25.25 0 0 0 .25.25H19v2H7a2 2 0 0 1-2-2c0-.35.09-.68.24-.96l1.36-2.45L3 4H1V2m6 16a2 2 0 0 1 2 2a2 2 0 0 1-2 2a2 2 0 0 1-2-2c0-1.11.89-2 2-2m9-7l2.78-5H6.14l2.36 5H16Z"/></svg>';
  echo '</div>';
  
  if (isset($_SESSION['cart'])) {
    $cart = $_SESSION['cart'];
    $api_key = get_option('stripe_integration_settings')['stripe_api_key'];
    
    if (!$api_key) {
      echo '<div class="notice notice-error is-dismissible"><p>Stripe API Key is not set.</p></div>';
      return;
    }

    require_once plugin_dir_path(__FILE__) . '../vendor/stripe/stripe-php/init.php';
    \Stripe\Stripe::setApiKey($api_key);

    try {
      echo '<div class="stripe-cart">';
      echo '<h2>Your Cart</h2>';
      echo '<ul>';
      $total = 0;
      
      foreach ($cart as $product_id => $quantity) {
        $product = \Stripe\Product::retrieve($product_id);
        $price = \Stripe\Price::retrieve($product->default_price);
        $item_total = $price->unit_amount_decimal * $quantity;
        $total += $item_total;
        
        echo '<li>';
        echo '<img src="' . esc_url($product->images[0]) . '" alt="' . esc_attr($product->name) . '" />';
        echo '<div class="cart-item-details">';
        echo '<div class="cart-item-name">' . esc_html($product->name) . '</div>';
        echo '<div class="cart-item-price">$' . esc_html($price->unit_amount_decimal / 100) . ' x ' . esc_html($quantity) . '</div>';
        echo '</div>';
        echo '<button class="remove-from-cart" data-product-id="' . esc_attr($product_id) . '">&times;</button>';
        echo '</li>';
      }
      
      echo '</ul>';
      echo '<div class="cart-total">Total: $' . esc_html($total / 100) . '</div>';
      echo '<button class="checkout-button">Checkout</button>';
      echo '</div>';
    } catch (\Stripe\Exception\ApiErrorException $e) {
      echo '<div class="notice notice-error is-dismissible"><p>Error fetching products: ' . esc_html($e->getMessage()) . '</p></div>';
    }
  } else {
    echo '<div class="stripe-cart">';
    echo '<h2>Your Cart</h2>';
    echo '<p>Your cart is empty</p>';
    echo '</div>';
  }
  
  echo '</div>';
}
add_shortcode('stripe_cart', 'stripe_integration_display_cart');

?>
