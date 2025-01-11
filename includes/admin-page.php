<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// Admin Menu
add_action('admin_menu', function () {
    add_menu_page('Hyper Checkout', 'Hyper Checkout', 'manage_options', 'hyper-checkout', 'hyper_checkout_admin_page');
});

function hyper_checkout_admin_page()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'hyper_checkout_links';
    $links = $wpdb->get_results("SELECT * FROM $table_name");

    $products = get_posts(['post_type' => 'product', 'numberposts' => -1]);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'hyper_checkout_nonce')) {
            wp_die(__('Security check failed', 'hyper-checkout'));
        }

        // Insert new link
        if (isset($_POST['create_hyper_link']) && isset($_POST['products'])) {
            hc_handle_link_creation($_POST);
        }

        // Delete link
        if (isset($_POST['delete_link'])) {
            $link_id = intval($_POST['delete_link']);
            $wpdb->delete($table_name, ['id' => $link_id], ['%d']);
            wp_redirect(admin_url('admin.php?page=hyper-checkout&deleted=1'));
            exit;
        }
    }

    include plugin_dir_path(__FILE__) . '../views/admin-page.php';
}

function hc_handle_link_creation($data){
    global $wpdb;
    $table_name = $wpdb->prefix . 'hyper_checkout_links';

    // Properly structure products array
    $structured_products = hcTransformArray($data['products']);

    $free_shipping = isset($data['free_shipping']) ? 1 : 0;
    $logged_in_only = isset($data['logged_in_only']) ? 1 : 0;
    $use_once = isset($data['use_once']) ? 1 : 0;
    $link_hash = wp_generate_password(12, false);

    $wpdb->insert($table_name, [
        'link_hash' => $link_hash,
        'products' => json_encode($structured_products), // Store structured array
        'free_shipping' => $free_shipping,
        'logged_in_only' => $logged_in_only,
        'use_once' => $use_once,
    ]);

    wp_redirect(admin_url('admin.php?page=hyper-checkout&success=1'));
    exit;
}

function hcTransformArray($inputArray)
{
    $result = [];
    $temp = [];

    foreach ($inputArray as $item) {
        foreach ($item as $key => $value) {
            if ($key === 'id' && !empty($temp)) {
                $result[] = $temp;
                $temp = [];
            }
            $temp[$key] = $value;
        }
    }

    if (!empty($temp)) {
        $result[] = $temp;
    }

    return $result;
}
