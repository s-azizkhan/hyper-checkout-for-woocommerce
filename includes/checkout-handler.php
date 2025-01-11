<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

function hc_get_link_info($link_hash)
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'hyper_checkout_links';

    $link = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE link_hash = %s", $link_hash));

    return $link;
}

function hc_update_link_use_count($link_hash){
    global $wpdb;
    $table_name = $wpdb->prefix . 'hyper_checkout_links';

    // Update used column by +1
    $wpdb->query(
        $wpdb->prepare(
            "UPDATE $table_name SET used = used + 1 WHERE link_hash = %s",
            $link_hash
        )
    );
}


// Handle Checkout Links
add_action('wp_loaded', function () {
    if (!isset($_GET['hyper_checkout'])) {
        return;
    }
    $link_hash = sanitize_text_field($_GET['hyper_checkout']);

    $link = hc_get_link_info($link_hash);
    if (!$link) {
        wp_die('Invalid Checkout Link.');
    }

    if ($link->logged_in_only && !is_user_logged_in()) {
        wp_die('This link is for logged-in users only.');
    }

    if ($link->use_once && $link->used) {
        wp_die('This link has already been used.');
    }

    WC()->cart->empty_cart();
    $products = json_decode($link->products, true);

    foreach ($products as $product) {
        WC()->cart->add_to_cart($product['id'], $product['quantity'], 0, array(), array(
            'hc_id' => $link->link_hash,
            'hc_discount' => $product['discount'],
        ));
    }

    //  set link to cookie
    setcookie('hc_link_info', json_encode($link), time() + 6000, '/');

    hc_update_link_use_count($link_hash);

    wp_redirect(wc_get_checkout_url());
    exit;
});

function hc_get_link_from_cookie()
{
    if (!isset($_COOKIE['hc_link_info'])) {
        return null;
    }
    $linkData = $_COOKIE['hc_link_info'];
    $linkData = json_decode(stripslashes($linkData));
    if (empty($linkData)) {
        return null;
    }

    return $linkData;
}

add_filter('woocommerce_package_rates', 'hyper_checkout_free_shipping');
function hyper_checkout_free_shipping($rates)
{
    try {

        $linkData = hc_get_link_from_cookie();
        if (!$linkData) {
            return $rates;
        }

        if ($linkData->free_shipping) {

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

// apply the discount based on link config
add_action('woocommerce_before_calculate_totals', 'hc_apply_discount', 9999);
function hc_apply_discount($cart)
{
    try {
        $linkData = hc_get_link_from_cookie();
        if (!$linkData) {
            return;
        }

        foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
            if (isset($cart_item['hc_discount']) && $cart_item['hc_discount']) {
                // Get product price
                $product_obj = wc_get_product($cart_item['product_id']);
                if (!$product_obj) {
                    continue;
                }
                $original_price = floatval($product_obj->get_price());
                $discount_percentage = floatval($cart_item['hc_discount']);

                // Calculate discounted price
                $discounted_price = max(0, $original_price - ($original_price * ($discount_percentage / 100)));
                $cart_item['data']->set_price($discounted_price);
            }
        }
    } catch (\Throwable $th) {
        error_log('Error setting discount price: ' . $th->getMessage());
    }
}
