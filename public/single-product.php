<?php

// Only run on single-product page
if (strpos($_SERVER['REQUEST_URI'], '/single-product') === false) {
    return;
}

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if Stripe library exists
$stripe_lib_path = plugin_dir_path(__FILE__) . '../vendor/stripe/stripe-php/init.php';
if (!file_exists($stripe_lib_path)) {
    die('Stripe PHP library not found. Please install the Stripe PHP SDK.');
}

require_once $stripe_lib_path;

// Get API key from settings
$api_key = get_option('stripe_integration_settings')['stripe_api_key'] ?? '';
if (empty($api_key)) {
    die('Stripe API key is not configured.');
}

\Stripe\Stripe::setApiKey($api_key);

// Verify product ID exists
if (!isset($_GET['id'])) {
    die('Product ID not provided.');
}

// Sanitize and validate product ID
$product_id = sanitize_text_field($_GET['id']);
if (!preg_match('/^prod_[a-zA-Z0-9]{14,}$/', $product_id)) {
    die('Invalid product ID format.');
}

try {
    // Retrieve product from Stripe
    $product = \Stripe\Product::retrieve($product_id, [
        'expand' => ['default_price']
    ]);
    
    // Get price details
    $price = $product->default_price;
    
    // Verify product exists
    if (!$product || !$price) {
        die('Product not found.');
    }

    // Handle price object
    $price_amount = 0;
    if (is_object($price) && property_exists($price, 'unit_amount_decimal')) {
        $price_amount = $price->unit_amount_decimal / 100;
    } elseif (is_string($price)) {
        // If price is a string ID, retrieve the price object
        $price = \Stripe\Price::retrieve($price);
        $price_amount = $price->unit_amount_decimal / 100;
    }

    // Prepare metadata
    $metadata = $product->metadata;
    $description = $metadata->productDescription ?? 'No description available';
    $features = [];
    
    // Extract features from metadata
    foreach ($metadata as $key => $value) {
        if (strpos($key, 'feature_') === 0) {
            $features[] = $value;
        }
    }

} catch (\Stripe\Exception\ApiErrorException $e) {
    die('Error retrieving product: ' . $e->getMessage());
} catch (Exception $e) {
    die('An unexpected error occurred: ' . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo esc_html($product->name); ?></title>
    <link rel="stylesheet" href="<?php echo plugin_dir_url(__FILE__); ?>css/stripe-integrations.css">
    <style>
        .product-container { 
            max-width: 800px; 
            margin: 0 auto; 
            padding: 20px; 
        }
        .product-image { 
            max-width: 100%; 
            height: auto; 
            border-radius: 8px; 
        }
        .product-price { 
            font-size: 24px; 
            color: #6c5ce7; 
            font-weight: bold; 
            margin: 20px 0;
        }
        .product-features { 
            margin: 20px 0; 
            padding-left: 20px;
        }
        .product-features li { 
            margin: 10px 0; 
            list-style: disc;
        }
        .buy-button {
            background-color: #6c5ce7;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            margin-top: 20px;
        }
        .buy-button:hover {
            background-color: #5a4dbf;
        }
    </style>
</head>
<body>
    <div class="product-container">
        <?php if (!empty($product->images)): ?>
            <img src="<?php echo esc_url($product->images[0]); ?>" 
                 alt="<?php echo esc_attr($product->name); ?>" 
                 class="product-image">
        <?php endif; ?>
        
        <h1><?php echo esc_html($product->name); ?></h1>
        <p class="product-price">
            $<?php echo number_format($price_amount, 2); ?>
        </p>
        
        <div class="product-description">
            <?php echo esc_html($description); ?>
        </div>
        
        <?php if (!empty($features)): ?>
            <ul class="product-features">
                <?php foreach ($features as $feature): ?>
                    <li><?php echo esc_html($feature); ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
        
        <button class="buy-button" 
                onclick="addToCart('<?php echo esc_js($product->id); ?>')">
            Add to Cart
        </button>
    </div>

    <script>
    function addToCart(productId) {
        fetch('/wp-admin/admin-ajax.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=stripe_add_to_cart>product_id=' + encodeURIComponent(productId),
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Product added to cart!');
            } else {
                alert('Failed to add product to cart');
            }
        });
    }
    </script>
</body>
</html>

    </script>
</body>
</html>
