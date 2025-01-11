<?php
// File location: includes/helpers.php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * Inserts or updates a link along with configurations and products.
 */
function hcfw_insert_or_update_link($link_name, $link_hash, $products, $created_by, $free_shipping = false, $logged_in_only = false, $use_once = false)
{
    global $wpdb;
    $table_links = $wpdb->prefix . "hcfw_links";
    $table_config = $wpdb->prefix . "hcfw_link_metadata";
    $table_products = $wpdb->prefix . "hcfw_link_products";

    // Check if the link already exists
    $existing_link = $wpdb->get_row($wpdb->prepare(
        "SELECT id FROM $table_links WHERE link_hash = %s",
        $link_hash
    ));

    if ($existing_link) {
        $link_id = $existing_link->id;

        // Update link (excluding usage_count)
        $wpdb->update(
            $table_links,
            ['updated_at' => current_time('mysql')],
            ['id' => $link_id]
        );

        // Update configurations
        $wpdb->delete($table_config, ['link_id' => $link_id]);
        $wpdb->insert($table_config, ['link_id' => $link_id, 'meta_key' => 'free_shipping', 'meta_value' => (int) $free_shipping]);
        $wpdb->insert($table_config, ['link_id' => $link_id, 'meta_key' => 'logged_in_only', 'meta_value' => (int) $logged_in_only]);
        $wpdb->insert($table_config, ['link_id' => $link_id, 'meta_key' => 'use_once', 'meta_value' => (int) $use_once]);

        // Update products
        $wpdb->delete($table_products, ['link_id' => $link_id]);
        foreach ($products as $product) {
            $wpdb->insert(
                $table_products,
                [
                    'link_id' => $link_id,
                    'product_id' => $product['id'],
                    'quantity' => $product['quantity'],
                    'discount' => $product['discount'],
                ]
            );
        }
    } else {
        // Insert new link
        $wpdb->insert(
            $table_links,
            [
                'link_name' => $link_name,
                'link_hash' => $link_hash,
                'created_by' => $created_by,
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql'),
            ]
        );

        $link_id = $wpdb->insert_id;

        // Insert configurations
        $wpdb->insert($table_config, ['link_id' => $link_id, 'meta_key' => 'free_shipping', 'meta_value' => (int) $free_shipping]);
        $wpdb->insert($table_config, ['link_id' => $link_id, 'meta_key' => 'logged_in_only', 'meta_value' => (int) $logged_in_only]);
        $wpdb->insert($table_config, ['link_id' => $link_id, 'meta_key' => 'use_once', 'meta_value' => (int) $use_once]);

        // Insert products
        foreach ($products as $product) {
            $wpdb->insert(
                $table_products,
                [
                    'link_id' => $link_id,
                    'product_id' => $product['id'],
                    'quantity' => $product['quantity'],
                    'discount' => $product['discount'],
                ]
            );
        }
    }
}

/**
 * Toggles link status (active/inactive).
 */
function hcfw_toggle_link_status($link_hash, $status)
{
    global $wpdb;
    $table_links = $wpdb->prefix . "hcfw_links";

    $wpdb->update(
        $table_links,
        ['is_active' => (int) $status, 'updated_at' => current_time('mysql')],
        ['link_hash' => $link_hash]
    );
}

/**
 * Retrieves all links with their configurations and products.
 */
function hcfw_get_links()
{
    global $wpdb;
    $table_links = $wpdb->prefix . "hcfw_links";
    $table_config = $wpdb->prefix . "hcfw_link_metadata";
    $table_products = $wpdb->prefix . "hcfw_link_products";

    $links = $wpdb->get_results("SELECT * FROM $table_links WHERE deleted_at IS NULL ORDER BY created_at DESC");

    foreach ($links as $key => $link) {
        // Fetch configurations
        $configurations = $wpdb->get_results(
            $wpdb->prepare("SELECT meta_key, meta_value FROM $table_config WHERE link_id = %d", $link->id),
            OBJECT_K
        );

        $links[$key]->config = [
            'free_shipping' => (bool) ($configurations['free_shipping']->meta_value ?? false),
            'logged_in_only' => (bool) ($configurations['logged_in_only']->meta_value ?? false),
            'use_once' => (bool) ($configurations['use_once']->meta_value ?? false),
        ];

        // Fetch products
        $links[$key]->products = $wpdb->get_results(
            $wpdb->prepare("SELECT product_id, quantity, discount FROM $table_products WHERE link_id = %d", $link->id)
        );
    }

    return $links;
}

/**
 * Retrieves detailed link info by link_hash.
 */
function hcfw_get_link_info($link_hash)
{
    global $wpdb;
    $table_links = $wpdb->prefix . "hcfw_links";
    $table_config = $wpdb->prefix . "hcfw_link_metadata";
    $table_products = $wpdb->prefix . "hcfw_link_products";

    $link = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_links WHERE link_hash = %s AND deleted_at IS NULL", $link_hash));

    if (!$link) {
        return null;
    }

    // Fetch configurations
    $configurations = $wpdb->get_results(
        $wpdb->prepare("SELECT meta_key, meta_value FROM $table_config WHERE link_id = %d", $link->id),
        OBJECT_K
    );

    $link->config = [
        'free_shipping' => (bool) ($configurations['free_shipping']->meta_value ?? false),
        'logged_in_only' => (bool) ($configurations['logged_in_only']->meta_value ?? false),
        'use_once' => (bool) ($configurations['use_once']->meta_value ?? false),
    ];

    // Fetch products
    $link->products = $wpdb->get_results(
        $wpdb->prepare("SELECT product_id, quantity, discount FROM $table_products WHERE link_id = %d", $link->id)
    );

    return $link;
}

/**
 * Increments the usage count for a link.
 */
function hcfw_update_link_use_count($link_hash)
{
    global $wpdb;
    $table_links = $wpdb->prefix . "hcfw_links";

    $wpdb->query(
        $wpdb->prepare(
            "UPDATE $table_links SET usage_count = usage_count + 1, updated_at = CURRENT_TIMESTAMP WHERE link_hash = %s",
            $link_hash
        )
    );
}

/**
 * Performs a soft delete on a link (sets deleted_at timestamp instead of removing).
 */
function hcfw_soft_delete_link($link_hash)
{
    global $wpdb;
    $table_links = $wpdb->prefix . "hcfw_links";

    $wpdb->update(
        $table_links,
        array(
            'deleted_at' => (new \DateTime())->format('Y-m-d H:i:s'),
        ),
        array('link_hash' => $link_hash)
    );
}

function hcfw_get_metadata($link_hash, $meta_key, $default = null)
{
    global $wpdb;
    $table_config = $wpdb->prefix . "hcfw_link_metadata";
    $table_links = $wpdb->prefix . "hcfw_links";

    $query = "SELECT lm.meta_value from $table_config lm INNER JOIN $table_links l ON l.id = lm.link_id WHERE l.link_hash = %s AND lm.meta_key = %s";

    $meta_value = $wpdb->get_var($wpdb->prepare($query, $link_hash, $meta_key));

    return $meta_value ?? $default;
}

function hcfw_get_product_discount($link_hash, $product_id, $default = 0)
{
    global $wpdb;
    $table_products = $wpdb->prefix . "hcfw_link_products";
    $table_links = $wpdb->prefix . "hcfw_links";

    $query = "SELECT lp.discount from $table_products lp INNER JOIN $table_links l ON l.id = lp.link_id WHERE l.link_hash = %s AND lp.product_id = %d";

    $meta_value = $wpdb->get_var($wpdb->prepare($query, $link_hash, $product_id));

    return $meta_value ?? $default;
}

function hcfwTransformArray($inputArray)
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
