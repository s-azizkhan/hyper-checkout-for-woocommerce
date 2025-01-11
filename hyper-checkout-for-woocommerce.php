<?php
/**
 * Plugin Name: Hyper Checkout for WooCommerce
 * Description: Create special checkout links that add products, apply discounts, enable free shipping, and apply conditions.
 * Version: 1.0.0
 * Author: Aziz Khan
 * Text Domain: hyper-checkout-for-woocommerce
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

require_once plugin_dir_path(__FILE__) . 'includes/admin-page.php';
require_once plugin_dir_path(__FILE__) . 'includes/checkout-handler.php';

function hyper_checkout_enqueue_scripts($hook) {
    if ($hook !== 'toplevel_page_hyper-checkout') {
        return;
    }
    wp_enqueue_script('hyper-checkout-bootstrap-js', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js', ['jquery'], null, true);
    wp_enqueue_style('hyper-checkout-bootstrap-css', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css');
    wp_enqueue_script('hyper-checkout-js', plugin_dir_url(__FILE__) . 'assets/js/admin.js', ['jquery'], null, true);
    wp_enqueue_style('hyper-checkout-css', plugin_dir_url(__FILE__) . 'assets/css/admin.css');
}
add_action('admin_enqueue_scripts', 'hyper_checkout_enqueue_scripts');
