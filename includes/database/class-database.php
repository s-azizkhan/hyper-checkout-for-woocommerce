<?php

if (!defined('ABSPATH')) {
    exit;
}

class HCFW_Database {

    public static function activate() {
        self::create_tables();
    }

    public static function deactivate() {
        self::delete_tables();
    }

    private static function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

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

        $table_config = $wpdb->prefix . "hcfw_link_metadata";
        $sql_config = "CREATE TABLE $table_config (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            link_id BIGINT UNSIGNED NOT NULL,
            meta_key VARCHAR(255) NOT NULL,
            meta_value TEXT NOT NULL,
            FOREIGN KEY (link_id) REFERENCES $table_links(id) ON DELETE CASCADE
        ) $charset_collate;";

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

    private static function delete_tables() {
        global $wpdb;
        $table_links = $wpdb->prefix . "hcfw_links";
        $table_config = $wpdb->prefix . "hcfw_link_metadata";
        $table_products = $wpdb->prefix . "hcfw_link_products";

        $wpdb->query("DROP TABLE IF EXISTS $table_products");
        $wpdb->query("DROP TABLE IF EXISTS $table_config");
        $wpdb->query("DROP TABLE IF EXISTS $table_links");
    }
}