<?php
// Zabezpieczenie przed bezpośdirectnim dostępem
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Usuwanie opcji
delete_option('png_options');

// Usuwanie custom post types
$listings = get_posts(array(
    'post_type' => 'listing',
    'numberposts' => -1,
    'post_status' => 'any'
));

foreach ($listings as $listing) {
    wp_delete_post($listing->ID, true);
}

// Usuwanie tabel
global $wpdb;
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}png_reports");

// Czyszczenie rewrite rules
flush_rewrite_rules();