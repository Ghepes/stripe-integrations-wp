<?php
require 'stripe-setup.php'; // Include configura?ia Stripe

if (!isset($_GET['id'])) {
    die('Product ID not provided.');
}

$product_id = sanitize_text_field($_GET['id']);

// Ob?ine produsele din Stripe
$products = $stripe->products->all(['limit' => 100]);

// Gase?te produsul dupa ID
$product = null;
foreach ($products->data as $p) {
    if ($p->id === $product_id) {
        $product = $p;
        break;
    }
}

if (!$product) {
    die('Product not found.');
}

// Ob?ine pre?ul produsului
$price = $stripe->prices->retrieve($product->default_price);

// Afi?are pagina produs
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo esc_html($product->name); ?></title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .product-container { max-width: 800px; margin: auto; text-align: center; }
        .product-image { max-width: 100%; border-radius: 10px; }
        .product-price { font-size: 24px; font-weight: bold; color: #6c5ce7; }
        .buy-button { background: #ff4757; color: white; padding: 10px 15px; border: none; cursor: pointer; border-radius: 5px; font-size: 18px; }
    </style>
</head>
<body>
    <div class="product-container">
        <img src="<?php echo esc_url($product->images[0]); ?>" alt="<?php echo esc_attr($product->name); ?>" class="product-image">
        <h1><?php echo esc_html($product->name); ?></h1>
        <p class="product-price">$<?php echo esc_html($price->unit_amount_decimal / 100.00); ?></p>

        <p><?php echo esc_html($product->metadata->productDescription ?? "No description available."); ?></p>

        <button class="buy-button" onclick="alert('Added to cart!')">Add to Cart</button>
    </div>
</body>
</html>

?>