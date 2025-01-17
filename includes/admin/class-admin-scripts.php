<?php

if (!defined('ABSPATH')) {
    exit;
}

class HCFW_Admin_Scripts {

    public function __construct() {
        add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);
    }

    public function enqueue_scripts($hook) {
        if ($hook !== 'marketing_page_hyper-checkout') {
            return;
        }

        wp_enqueue_script('hyper-checkout-bootstrap-js', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js', ['jquery'], null, true);
        wp_enqueue_style('hyper-checkout-bootstrap-css', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css');
        wp_enqueue_style('fontawesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css');
        wp_enqueue_script('hyper-checkout-js', plugin_dir_url(__FILE__) . '../../assets/js/admin.js', ['jquery', 'select2'], time(), true);
        wp_enqueue_style('hyper-checkout-css', plugin_dir_url(__FILE__) . '../../assets/css/admin.css');

        $product_args = ['limit' => -1, 'status' => 'publish'];
        $products = wc_get_products($product_args);
        $product_options = '';

        foreach ($products as $product) {
            if ($product->is_type('variable')) {
                $variations = $product->get_children();
                foreach ($variations as $variation_id) {
                    $variation = wc_get_product($variation_id);
                    if ($variation) {
                        $product_options .= '<option value="' . esc_attr($variation_id) . '">' . esc_html($product->get_name() . ' - ' . $variation->get_attribute_summary() . ' (ID: ' . $variation_id . ')') . '</option>';
                    }
                }
            } else {
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
}

new HCFW_Admin_Scripts();