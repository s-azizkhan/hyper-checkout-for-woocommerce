<?php
/**
 * Plugin Name:       Hyper Checkout for WooCommerce
 * Plugin URI:        https://gethypercheckout.com
 * Description:       Create special checkout links that add products, apply discounts, enable free shipping, and apply conditions for a seamless checkout experience.
 * Version:           1.0.0
 * Author:            Hypertics AI
 * Author URI:        https://HyperticsAI.com
 * Text Domain:       hyper-checkout-for-woocommerce
 * Domain Path:       /languages
 * Requires at least: 5.0
 * Requires PHP:      7.4
 * Tested up to:      6.4
 * Requires Plugins: woocommerce
 * WC requires at least: 4.0
 * WC tested up to:   8.0
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

define('HCFW_TABLE_NAME', 'hyper_checkout_links');
define('HCFW_DOMAIN', 'hcfw');
define("HCFW_LINK_COOKIE_NAME", "hcfw_link_info");
define("HCFW_LINK_ID", 'hc');

// Include necessary files
require_once plugin_dir_path(__FILE__) . 'includes/helpers/class-helpers.php';
require_once plugin_dir_path(__FILE__) . 'includes/database/class-database.php';
require_once plugin_dir_path(__FILE__) . 'includes/admin/class-admin-page.php';
require_once plugin_dir_path(__FILE__) . 'includes/admin/class-admin-scripts.php';
require_once plugin_dir_path(__FILE__) . 'includes/frontend/class-checkout-handler.php';

// Activation and Deactivation Hooks
register_activation_hook(__FILE__, ['HCFW_Database', 'activate']);
register_deactivation_hook(__FILE__, ['HCFW_Database', 'deactivate']);

// WooCommerce Compatibility
add_action('before_woocommerce_init', function () {
    if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
    }
});