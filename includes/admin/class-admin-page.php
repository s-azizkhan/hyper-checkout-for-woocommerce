<?php

if (!defined('ABSPATH')) {
    exit;
}

class HCFW_Admin_Page
{

    public function __construct()
    {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('wp_ajax_hcfw_create_link', [$this, 'ajax_create_link_handler']);
        add_action('wp_ajax_hcfw_delete_link', [$this, 'ajax_delete_link_handler']);
    }

    public function add_admin_menu()
    {
        add_submenu_page(
            'woocommerce-marketing',
            __('Hyper Checkout', 'hyper-checkout-for-woocommerce'),
            __('Hyper Checkout', 'hyper-checkout-for-woocommerce'),
            'manage_options',
            'hyper-checkout',
            [$this, 'admin_page_content']
        );
    }

    public function admin_page_content()
    {
        // Include the admin page template
        include plugin_dir_path(__FILE__) . '../../views/admin-page.php';
    }

    /**
     * Handles the creation of a Hyper Checkout link via AJAX.
     */
    function ajax_create_link_handler()
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
        $structured_products = HCFW_Helpers::transformArray($_POST['products']);

        // Insert or update the link using the new DB structure
        HCFW_Helpers::insert_or_update_link($link_name, $link_hash, $structured_products, get_current_user_id(), $free_shipping, $logged_in_only, $use_once);

        // Return success response
        return wp_send_json_success(['message' => 'Hyper Checkout Link created successfully!']);
    }

    /**
     * Soft delete a link via AJAX.
     */
    function ajax_delete_link_handler()
    {
        check_ajax_referer('hcfw_nonce', 'security');

        if (!isset($_POST['delete_link']) || !$_POST['delete_link']) {
            return wp_send_json_error(['message' => 'Invalid request']);
        }

        $link_hash = sanitize_text_field($_POST['delete_link']);
        HCFW_Helpers::soft_delete_link($link_hash);

        return wp_send_json_success(['message' => 'Checkout link deleted successfully!']);
    }
}

new HCFW_Admin_Page();
