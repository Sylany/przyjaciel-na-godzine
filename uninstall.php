<?php
/**
 * Lokalizacja: /uninstall.php
 * Fired when the plugin is uninstalled
 */

// If uninstall not called from WordPress, exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

global $wpdb;

// Delete options
delete_option('png_settings');
delete_option('png_db_version');
delete_option('png_install_date');

// Delete page options
$page_options = array(
    'png_page_moje_ogloszenia',
    'png_page_dodaj_ogloszenie',
    'png_page_edytuj_profil',
    'png_page_moj_profil',
    'png_page_wiadomosci',
    'png_page_znajdz_towarzysza',
    'png_page_platnosc',
    'png_page_platnosc_sukces',
    'png_page_platnosc_anulowana',
    'png_page_ulubione',
    'png_page_statystyki',
    'png_page_subskrypcje',
    'png_page_weryfikacja'
);

foreach ($page_options as $option) {
    delete_option($option);
}

// Delete transients
delete_transient('png_hourly_stats');
delete_transient('png_daily_stats');

// Remove scheduled events
wp_clear_scheduled_hook('png_daily_maintenance');
wp_clear_scheduled_hook('png_hourly_stats');

// Remove custom roles
remove_role('png_verified');
remove_role('png_premium');

// Delete all posts of type 'listing'
$listings = get_posts(array(
    'post_type' => 'listing',
    'numberposts' => -1,
    'post_status' => 'any'
));

foreach ($listings as $listing) {
    wp_delete_post($listing->ID, true);
}

// Delete taxonomy terms
$taxonomies = array('listing_category', 'listing_tag');
foreach ($taxonomies as $taxonomy) {
    $terms = get_terms(array(
        'taxonomy' => $taxonomy,
        'hide_empty' => false
    ));
    
    foreach ($terms as $term) {
        wp_delete_term($term->term_id, $taxonomy);
    }
}

// Delete user meta
$wpdb->query("DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE '_png_%'");

// Delete uploaded files (optional - uncomment to enable)
/*
$upload_dir = wp_upload_dir();
$png_dirs = array(
    $upload_dir['basedir'] . '/png-listings',
    $upload_dir['basedir'] . '/png-avatars',
    $upload_dir['basedir'] . '/png-covers',
    $upload_dir['basedir'] . '/png-verifications',
    $upload_dir['basedir'] . '/png-temp'
);

foreach ($png_dirs as $dir) {
    if (is_dir($dir)) {
        png_delete_directory($dir);
    }
}

function png_delete_directory($dir) {
    if (!is_dir($dir)) {
        return;
    }
    
    $files = array_diff(scandir($dir), array('.', '..'));
    
    foreach ($files as $file) {
        $path = $dir . '/' . $file;
        is_dir($path) ? png_delete_directory($path) : unlink($path);
    }
    
    return rmdir($dir);
}
*/

// ==========================================
// DATABASE TABLES CLEANUP
// ==========================================
// IMPORTANT: Uncomment the section below ONLY if you want to
// completely remove all plugin data from database on uninstall.
// This action is IRREVERSIBLE!
// ==========================================

/*
$tables = array(
    'png_user_profiles',
    'png_listings',
    'png_messages',
    'png_reviews',
    'png_payments',
    'png_reports',
    'png_notifications',
    'png_favorites',
    'png_statistics',
    'png_subscriptions',
    'png_verifications',
    'png_bookings'
);

foreach ($tables as $table) {
    $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}{$table}");
}
*/

// ==========================================
// CLEAN COMPLETE
// ==========================================
// Log uninstall for debugging (optional)
error_log('Przyjaciel na Godzinę: Plugin uninstalled successfully at ' . current_time('mysql'));