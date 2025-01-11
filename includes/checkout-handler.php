<?php
// File location: includes/checkout-handler.php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// Handle Checkout Links
add_action('wp_loaded', function () {
    if (!isset($_GET[HCFW_LINK_ID])) {
        return;
    }

    $link_hash = sanitize_text_field($_GET[HCFW_LINK_ID]);
    $link = hcfw_get_link_info($link_hash);

    if (!$link) {
        wp_die('Invalid Checkout Link.');
    }

    // Check if the link is restricted to logged-in users
    if ($link->config['logged_in_only'] && !is_user_logged_in()) {
        wc_add_notice('This link is for logged-in users only, please log in and try again.', 'error');
        // build link & redirect to login page with refer
        $my_account_irl = wc_get_page_permalink('myaccount');
        // TODO: add the hc link to the query that execute after logged in
        wp_redirect($my_account_irl);
        exit;

    }

    // Check if the link should be used only once
    if ($link->config['use_once'] && $link->usage_count > 0) {
        wp_die('This link has already been used.');
    }

    // Empty the WooCommerce cart
    WC()->cart->empty_cart();

    // Empty the WooCommerce session
    // WC()->session->destroy_session(); // TODO: test this after uncomment

    // Add products to cart
    foreach ($link->products as $product) {
        WC()->cart->add_to_cart(
            $product->product_id,
            $product->quantity,
            0,
            [],
            [
                'hcfw_hash' => $link->link_hash,
            ]
        );
    }

    // Store link details in a cookie
    setcookie(HCFW_LINK_COOKIE_NAME, $link_hash, time() + 6000, '/');

    // Update link usage count
    hcfw_update_link_use_count($link_hash);

    // Redirect to the checkout page
    wp_redirect(wc_get_checkout_url());
    exit;
});

/**
 * Retrieve the link data stored in a cookie.
 */
function hcfw_get_link_from_cookie()
{
    if (!isset($_COOKIE[HCFW_LINK_COOKIE_NAME])) {
        return null;
    }

    $link_hash = trim($_COOKIE[HCFW_LINK_COOKIE_NAME]);
    return $link_hash;
}

/**
 * Apply free shipping if the link configuration allows it.
 */
add_filter('woocommerce_package_rates', 'hyper_checkout_free_shipping');
function hyper_checkout_free_shipping($rates)
{
    try {
        $linkHash = hcfw_get_link_from_cookie();
        if (!$linkHash) {
            return $rates;
        }

        if (hcfw_get_metadata($linkHash, 'free_shipping')) {
            foreach ($rates as $rate_id => $rate) {
                if ($rate->method_id === 'free_shipping') {
                    $rates = [$rate_id => $rate];
                    break;
                }
            }
        }
    } catch (\Throwable $th) {
        error_log($th->getMessage());
    }
    return $rates;
}

/**
 * Apply discounts to products in the cart based on link configuration.
 */
add_action('woocommerce_before_calculate_totals', 'hcfw_apply_discount', 9999);
function hcfw_apply_discount($cart)
{
    try {
        $linkHash = hcfw_get_link_from_cookie();
        if (!$linkHash) {
            return;
        }

        foreach ($cart->get_cart() as $cart_item_key => &$cart_item) {
            if (!isset($cart_item['hcfw_hash']) || $cart_item['hcfw_hash'] !== $linkHash) {
                continue;
            }
            // Get product price
            $product_obj = wc_get_product($cart_item['product_id']);
            if (!$product_obj) {
                continue;
            }

            $original_price = floatval($product_obj->get_price());

            if($original_price <= 0){
                continue;
            }

            $product_id = isset($cart_item['variation_id']) && $cart_item['variation_id']  ? $cart_item['variation_id'] : $cart_item['product_id'];
            $discount_percentage = floatval(hcfw_get_product_discount($linkHash, $product_id));
            if(!$discount_percentage) {
                continue;
            }

            // Calculate discounted price
            $discounted_price = max(0, $original_price - ($original_price * ($discount_percentage / 100)));
            $cart_item['data']->set_price($discounted_price);
        }
    } catch (\Throwable $th) {
        error_log('Error setting discount price: ' . $th->getMessage());
    }
}

/**
 * Store the hyper checkout hash in the order metadata after order creation.
 */
add_action('woocommerce_checkout_update_order_meta', 'hcfw_store_checkout_hash_in_order', 10, 2);
function hcfw_store_checkout_hash_in_order($order_id, $data)
{
    $linkHash = hcfw_get_link_from_cookie();
    if (!$linkHash) {
        return;
    }

    // Store the link hash in order metadata
    update_post_meta($order_id, '_hcfw_hash', sanitize_text_field($linkHash));

    // Optionally, clear the cookie after order creation
    setcookie(HCFW_LINK_COOKIE_NAME, '', time() - 3600, '/');
}
