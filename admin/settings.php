<?php
    function stripe_integrations_wp_settings_page() {
      ?>
      <div class="wrap">
        <h1>Stripe Integrations WP Settings</h1>
        <form method="post" action="options.php">
          <?php
          settings_fields('stripe_integrations_wp_settings');
          do_settings_sections('stripe-integrations-wp-settings');
          submit_button();
          ?>
        </form>
      </div>
      <?php
    }

    function stripe_integrations_wp_settings_init() {
      register_setting('stripe_integrations_wp_settings', 'stripe_integrations_wp_settings');

      add_settings_section(
        'stripe_integrations_wp_settings_section',
        __('Stripe API Settings', 'stripe-integrations-wp'),
        'stripe_integrations_wp_settings_section_callback',
        'stripe-integrations-wp-settings'
      );

      add_settings_field(
        'stripe_api_key',
        __('API Key', 'stripe-integrations-wp'),
        'stripe_integrations_wp_api_key_callback',
        'stripe-integrations-wp-settings',
        'stripe_integrations_wp_settings_section'
      );
    }
    add_action('admin_init', 'stripe_integrations_wp_settings_init');

    function stripe_integrations_wp_settings_section_callback() {
      echo __('Enter your Stripe API settings below:', 'stripe-integrations-wp');
    }

    function stripe_integrations_wp_api_key_callback() {
      $options = get_option('stripe_integrations_wp_settings');
      if (!is_array($options)) {
        $options = ['stripe_api_key' => ''];
      }
      ?>
      <input type="text" name="stripe_integrations_wp_settings[stripe_api_key]" value="<?php echo esc_attr($options['stripe_api_key']); ?>" />
      <?php
    }
