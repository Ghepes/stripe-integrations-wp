<?php
    function stripe_integrations_wp_add_products_page() {
      if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['product_name'])) {
        stripe_integrations_wp_add_product();
      }
      ?>
      <div class="wrap">
        <h1>Add Products to Stripe</h1>
        <form method="post" action="">
          <table class="form-table">
            <tr valign="top">
              <th scope="row">Product Name</th>
              <td><input type="text" name="product_name" required /></td>
            </tr>
            <tr valign="top">
              <th scope="row">Product Description</th>
              <td><textarea name="product_description" required></textarea></td>
            </tr>
            <tr valign="top">
              <th scope="row">Product Price (in cents)</th>
              <td><input type="number" name="product_price" required /></td>
            </tr>
            <tr valign="top">
              <th scope="row">Product Image URL</th>
              <td><input type="text" name="product_image" required /></td>
            </tr>
            <tr valign="top">
              <th scope="row">Product Code</th>
              <td><input type="text" name="product_code" required /></td>
            </tr>
          </table>
          <?php submit_button(); ?>
        </form>
      </div>
      <?php
    }

    function stripe_integrations_wp_add_product() {
      $api_key = get_option('stripe_integrations_wp_settings')['stripe_api_key'];
      if (!$api_key) {
        echo '<div class="notice notice-error is-dismissible"><p>Stripe API Key is not set.</p></div>';
        return;
      }

      $product_name = sanitize_text_field($_POST['product_name']);
      $product_description = sanitize_textarea_field($_POST['product_description']);
      $product_price = intval($_POST['product_price']);
      $product_image = esc_url_raw($_POST['product_image']);
      $product_code = sanitize_text_field($_POST['product_code']);

      require_once plugin_dir_path(__FILE__) . '../../vendor/stripe/stripe-php/init.php';

      \Stripe\Stripe::setApiKey($api_key);

      try {
        $product = \Stripe\Product::create([
          'name' => $product_name,
          'description' => $product_description,
          'images' => [$product_image],
          'metadata' => [
            'product_code' => $product_code,
          ],
        ]);

        \Stripe\Price::create([
          'product' => $product->id,
          'unit_amount' => $product_price,
          'currency' => 'usd',
        ]);

        echo '<div class="notice notice-success is-dismissible"><p>Product added successfully!</p></div>';
      } catch (\Stripe\Exception\ApiErrorException $e) {
        echo '<div class="notice notice-error is-dismissible"><p>Error adding product: ' . esc_html($e->getMessage()) . '</p></div>';
      }
    }
