<?php
/**
 * Plugin Name: Hyper Checkout for WooCommerce
 * Description: Create special checkout links that add products, apply discounts, enable free shipping, and apply conditions.
 * Version: 1.0.0
 * Author: Aziz Khan
 * Text Domain: hyper-checkout-for-woocommerce
 */
// File location: hyper-checkout-for-woocommerce.php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

define('HCFW_TABLE_NAME', 'hyper_checkout_links');
define('HCFW_DOMAIN', 'hcfw');
define("HCFW_LINK_COOKIE_NAME", "hcfw_link_info");
define("HCFW_LINK_ID", 'hc');

// Activate Plugin
register_activation_hook(__FILE__, 'hyper_checkout_for_woocommerce_activate');
function hyper_checkout_for_woocommerce_activate()
{
    hcfw_create_table();
}
function hcfw_create_table()
{
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

    // Table for storing links
    $table_links = $wpdb->prefix . "hcfw_links";
    $sql_links = "CREATE TABLE $table_links (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        link_name VARCHAR(255) NOT NULL UNIQUE,
        link_hash VARCHAR(255) NOT NULL UNIQUE,
        usage_count INT UNSIGNED DEFAULT 0,
        is_active BOOLEAN DEFAULT TRUE,
        created_by BIGINT UNSIGNED NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        deleted_at DATETIME NULL,
        FOREIGN KEY (created_by) REFERENCES {$wpdb->prefix}users(ID) ON DELETE CASCADE,
        INDEX idx_link_hash (link_hash),
        INDEX idx_created_by (created_by)
    ) $charset_collate;";

    // Table for storing link configurations (normalized)
    $table_config = $wpdb->prefix . "hcfw_link_metadata";
    $sql_config = "CREATE TABLE $table_config (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        link_id BIGINT UNSIGNED NOT NULL,
        meta_key VARCHAR(255) NOT NULL,
        meta_value TEXT NOT NULL,
        FOREIGN KEY (link_id) REFERENCES $table_links(id) ON DELETE CASCADE
    ) $charset_collate;";

    // Table for storing link products (normalized)
    $table_products = $wpdb->prefix . "hcfw_link_products";
    $sql_products = "CREATE TABLE $table_products (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        link_id BIGINT UNSIGNED NOT NULL,
        product_id BIGINT UNSIGNED NOT NULL,
        quantity INT UNSIGNED NOT NULL DEFAULT 1,
        discount DECIMAL(10,2) NOT NULL DEFAULT 0,
        FOREIGN KEY (link_id) REFERENCES $table_links(id) ON DELETE CASCADE,
        FOREIGN KEY (product_id) REFERENCES {$wpdb->prefix}posts(ID) ON DELETE CASCADE
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql_links);
    dbDelta($sql_config);
    dbDelta($sql_products);
}

register_deactivation_hook(__FILE__, 'hcfw_delete_tables');
function hcfw_delete_tables()
{
    global $wpdb;
    $table_links = $wpdb->prefix . "hcfw_links";
    $table_config = $wpdb->prefix . "hcfw_link_metadata";
    $table_products = $wpdb->prefix . "hcfw_link_products";
    
    // Drop the tables
    $wpdb->query("DROP TABLE IF EXISTS $table_products");
    $wpdb->query("DROP TABLE IF EXISTS $table_config");
    $wpdb->query("DROP TABLE IF EXISTS $table_links");
}

require_once plugin_dir_path(__FILE__) . 'includes/helpers.php';
require_once plugin_dir_path(__FILE__) . 'includes/admin-page.php';
require_once plugin_dir_path(__FILE__) . 'includes/checkout-handler.php';

function hyper_checkout_enqueue_scripts($hook)
{
    if ($hook !== 'toplevel_page_hyper-checkout') {
        return;
    }
    wp_enqueue_script('hyper-checkout-bootstrap-js', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js', ['jquery'], null, true);
    wp_enqueue_style('hyper-checkout-bootstrap-css', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css');
    wp_enqueue_style('fontawesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css');
    wp_enqueue_script('hyper-checkout-js', plugin_dir_url(__FILE__) . 'assets/js/admin.js', ['jquery', 'select2'], time(), true);
    wp_enqueue_style('hyper-checkout-css', plugin_dir_url(__FILE__) . 'assets/css/admin.css');

    // Localize this options
    // Fetch all products
    $product_args = ['limit' => -1, 'status' => 'publish'];
    $products = wc_get_products($product_args);
    $product_options = '';

    foreach ($products as $product) {
        // Check if the product is a variable product
        if ($product->is_type('variable')) {
            $variations = $product->get_children(); // Get variation IDs

            foreach ($variations as $variation_id) {
                $variation = wc_get_product($variation_id);
                if ($variation) {
                    $product_options .= '<option value="' . esc_attr($variation_id) . '">' . esc_html($product->get_name() . ' - ' . $variation->get_attribute_summary() . ' (ID: ' . $variation_id . ')') . '</option>';
                }
            }
        } else {
            // Simple product
            $product_options .= '<option value="' . esc_attr($product->get_id()) . '">' . esc_html($product->get_name() . ' (ID: ' . $product->get_id() . ')') . '</option>';
        }
    }
    wp_localize_script(
        'hyper-checkout-js',
        'hcfwObj',
        [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('hcfw_nonce'),
            'product_options' => $product_options
        ]
        );
    
}
add_action('admin_enqueue_scripts', 'hyper_checkout_enqueue_scripts');
