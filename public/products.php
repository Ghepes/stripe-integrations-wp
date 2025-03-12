<?php
function stripe_integration_display_products() {
    // Only render in main content area
    if (!in_the_loop() && !is_main_query()) {
        return '';
    }

    $api_key = get_option('stripe_integration_settings')['stripe_api_key'];
    if (!$api_key) {
        return '<div class="notice notice-error is-dismissible"><p>Stripe API Key is not set.</p></div>';
    }

    // Check if Stripe library exists
    $stripe_lib_path = plugin_dir_path(__FILE__) . '../vendor/stripe/stripe-php/init.php';
    if (!file_exists($stripe_lib_path)) {
        return '<div class="notice notice-error is-dismissible">
            <p>Stripe PHP library not found. Please install the Stripe PHP SDK.</p>
            <p>Run: <code>composer require stripe/stripe-php</code></p>
        </div>';
    }

    require_once $stripe_lib_path;
    \Stripe\Stripe::setApiKey($api_key);

    // Get current page from query parameter
    $current_page = isset($_GET['stripe_page']) ? (int)$_GET['stripe_page'] : 1;
    $per_page = 40;
    $starting_after = isset($_GET['starting_after']) ? $_GET['starting_after'] : null;

    try {
        $params = [
            'limit' => $per_page,
            'expand' => ['data.default_price']
        ];
        
        if ($starting_after) {
            $params['starting_after'] = $starting_after;
        }

        $products = \Stripe\Product::all($params);
        
        $output = '<div class="stripe-products-wrapper" style="max-width: 1200px; margin: 10 auto; padding: 10px;">';
        $output .= '<div class="stripe-products-header" style="text-align: center; margin-bottom: 10px;">';
        $output .= '</div>';

        // Grila dinamica pentru produsele afi?ate
       $output .= '<div class="stripe-products" style="
           display: grid;
           grid-template-columns: repeat(auto-fit, minmax(4fr, 1fr));
           gap: 15px;
           width: 100%;
           max-width: 100%;
           justify-content: center;
         ">';


        foreach ($products->data as $product) {
            $price = $product->default_price;
            $output .= '<div class="stripe-product" style="border: 1px solid #ddd; padding: 15px; border-radius: 10px; text-align: center; background: #fff;">';
            $output .= '<a href="single-product?id=' . esc_attr($product->id) . '" style="text-decoration: none; color: inherit;">';

           // Afi?eaza imaginea principala a produsului
            $output .= '<img src="' . esc_url($product->images[0]) . '" alt="' . esc_attr($product->name) . '" style="width: 100%; max-height: 250px; object-fit: contain;" />';
            $output .= '<h3>' . esc_html($product->name) . '</h3>';

            // Pre?ul produsului
            $output .= '<p class="product-price" style="font-weight: bold;">$' . esc_html($price->unit_amount_decimal / 100.00) . '</p>';

            $output .= '</a>';
           // ?? **Afi?eaza iconi?ele Vendor**
            $output .= '<div class="vendor-icons" style="display: flex; gap: 5px; justify-content: center; margin-bottom: 10px;">';

            if (!empty($product->metadata)) {
               for ($i = 1; $i <= 5; $i++) {
            $iconKey = "icon{$i}Vendor";
               if (!empty($product->metadata[$iconKey])) {
                 $output .= '<img src="' . esc_url($product->metadata[$iconKey]) . '" alt="Vendor ' . $i . '" style="width: 24px; height: 24px; border-radius: 50%;" />';
                  }
               }
            }

            $output .= '</div>';
            $output .= '<button class="add-to-cart" data-product-id="' . esc_attr($product->id) . '" style="background-color: #6c5ce7; color: white; border: none; padding: 10px 15px; border-radius: 5px; cursor: pointer;">Add to Cart</button>';
            $output .= '</div>';
        }
      
        $output .= '</div>';
        $output .= '</div>';
        
        return $output;
    } catch (\Stripe\Exception\ApiErrorException $e) {
        return '<div class="notice notice-error is-dismissible"><p>Error fetching products: ' . esc_html($e->getMessage()) . '</p></div>';
    }
}

add_shortcode('stripe_products', 'stripe_integration_display_products');
?>
