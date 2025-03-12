<?php
    /*
    Plugin Name: Stripe Integrations WP
    Description: A plugin to integrate Stripe products into WordPress.
    Version: 1.0
    Author: Codeuiapp
    */

    if (!defined('ABSPATH')) {
      exit; // Exit if accessed directly
    }

    // Start session
    if (!session_id()) {
      session_start();
    }

    // Include necessary files
    require_once plugin_dir_path(__FILE__) . 'admin/settings.php';
    require_once plugin_dir_path(__FILE__) . 'admin/products.php';
    require_once plugin_dir_path(__FILE__) . 'public/single-product.php';
    require_once plugin_dir_path(__FILE__) . 'public/products.php';
    require_once plugin_dir_path(__FILE__) . 'public/cart.php';
    require_once plugin_dir_path(__FILE__) . 'public/checkout.php';
    require_once plugin_dir_path(__FILE__) . 'vendor/autoload.php';
    

    // Register settings and admin pages
    function stripe_integrations_wp_admin_menu() {
      add_menu_page(
        'Stripe Integrations WP',
        'Stripe Integrations WP',
        'manage_options',
        'stripe-integrations-wp',
        'stripe_integrations_wp_settings_page',
        'dashicons-cart',
        6
      );

      add_submenu_page(
        'stripe-integrations-wp',
        'Add Products',
        'Add Products',
        'manage_options',
        'stripe-integrations-wp-add-products',
        'stripe_integrations_wp_add_products_page'
      );
    }
    add_action('admin_menu', 'stripe_integrations_wp_admin_menu');

    // Enqueue scripts and styles
    function stripe_integrations_wp_enqueue_scripts() {
      wp_enqueue_script('stripe-js', 'https://js.stripe.com/v3/', array(), null, true);
      wp_enqueue_script('stripe-integrations-wp-js', plugin_dir_url(__FILE__) . 'public/js/stripe-integrations-wp.js', array('jquery'), null, true);
      wp_enqueue_style('stripe-integrations-wp-css', plugin_dir_url(__FILE__) . 'public/style.css');
    }
    add_action('wp_enqueue_scripts', 'stripe_integrations_wp_enqueue_scripts');

    // AJAX actions
    add_action('wp_ajax_stripe_add_to_cart', 'stripe_integrations_wp_add_to_cart');
    add_action('wp_ajax_nopriv_stripe_add_to_cart', 'stripe_integrations_wp_add_to_cart');

    add_action('wp_ajax_stripe_remove_from_cart', 'stripe_integrations_wp_remove_from_cart');
    add_action('wp_ajax_nopriv_stripe_remove_from_cart', 'stripe_integrations_wp_remove_from_cart');

    add_action('wp_ajax_stripe_handle_checkout', 'stripe_integrations_wp_handle_checkout');
    add_action('wp_ajax_nopriv_stripe_handle_checkout', 'stripe_integrations_wp_handle_checkout');

    // Add to cart
    function stripe_integrations_wp_add_to_cart() {
      if (isset($_POST['product_id'])) {
        $product_id = sanitize_text_field($_POST['product_id']);
        if (!isset($_SESSION['cart'])) {
          $_SESSION['cart'] = [];
        }
        if (isset($_SESSION['cart'][$product_id])) {
          $_SESSION['cart'][$product_id]++;
        } else {
          $_SESSION['cart'][$product_id] = 1;
        }
        wp_send_json_success();
      } else {
        wp_send_json_error();
      }
    }

    // Remove from cart
    function stripe_integrations_wp_remove_from_cart() {
      if (isset($_POST['product_id'])) {
        $product_id = sanitize_text_field($_POST['product_id']);
        if (isset($_SESSION['cart'][$product_id])) {
          unset($_SESSION['cart'][$product_id]);
          wp_send_json_success();
        } else {
          wp_send_json_error();
        }
      } else {
        wp_send_json_error();
      }
    }

    // Handle checkout
    function stripe_integrations_wp_handle_checkout() {
      if (isset($_SESSION['cart'])) {
        $api_key = get_option('stripe_integrations_wp_settings')['stripe_api_key'];
        if (!$api_key) {
          wp_send_json_error(['message' => 'Stripe API Key is not set.']);
          return;
        }

        require_once plugin_dir_path(__FILE__) . 'vendor/stripe/stripe-php/init.php';

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

        try {
          $session = \Stripe\Checkout\Session::create([
            'payment_method_types' => ['card'],
            'line_items' => $line_items,
            'mode' => 'payment',
            'success_url' => home_url('/checkout-success') . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => home_url('/checkout-cancel'),
          ]);

          wp_send_json_success(['redirect_url' => $session->url]);
        } catch (\Stripe\Exception\ApiErrorException $e) {
          wp_send_json_error(['message' => $e->getMessage()]);
        }
      } else {
        wp_send_json_error(['message' => 'Cart is empty']);
      }
    }
    ?>
