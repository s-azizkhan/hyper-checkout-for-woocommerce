<?php
// File location: includes/admin-page.php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// Admin Menu
add_action('admin_menu', function () {
    add_submenu_page(
        'woocommerce-marketing',      // Parent slug
        'Hyper Checkout',             // Page title
        'Hyper Checkout',             // Menu title
        'manage_options',             // Capability
        'hyper-checkout',             // Menu slug
        'hyper_checkout_admin_page'   // Callback function
    );
});

function hyper_checkout_admin_page()
{
    // Include the admin page template
    include plugin_dir_path(__FILE__) . '../views/admin-page.php';
}

/**
 * Handles the creation of a Hyper Checkout link via AJAX.
 */
add_action('wp_ajax_hcfw_create_link', 'hcfw_ajax_create_link');
function hcfw_ajax_create_link()
{
    // Check nonce for security
    check_ajax_referer('hcfw_nonce', 'security');

    // Validate required fields
    if (!isset($_POST['link_name']) || !isset($_POST['products'])) {
        wp_send_json_error(['message' => 'Missing required fields']);
    }

    // Sanitize input
    $link_name = sanitize_text_field($_POST['link_name']);
    $free_shipping = isset($_POST['free_shipping']) ? 1 : 0;
    $logged_in_only = isset($_POST['logged_in_only']) ? 1 : 0;
    $use_once = isset($_POST['use_once']) ? 1 : 0;
    $link_hash = wp_generate_password(12, false);

    // Properly structure products array
    $structured_products = hcfwTransformArray($_POST['products']);

    // Insert or update the link using the new DB structure
    hcfw_insert_or_update_link($link_name, $link_hash, $structured_products, get_current_user_id(), $free_shipping, $logged_in_only, $use_once);

    // Return success response
    return wp_send_json_success(['message' => 'Hyper Checkout Link created successfully!']);
}

/**
 * Soft delete a link via AJAX.
 */
add_action('wp_ajax_hcfw_delete_link', 'hcfw_ajax_delete_link');
function hcfw_ajax_delete_link()
{
    check_ajax_referer('hcfw_nonce', 'security');

    if (!isset($_POST['delete_link']) || !$_POST['delete_link']) {
        return wp_send_json_error(['message' => 'Invalid request']);
    }

    $link_hash = sanitize_text_field($_POST['delete_link']);
    hcfw_soft_delete_link($link_hash);

    return wp_send_json_success(['message' => 'Checkout link deleted successfully!']);
}
