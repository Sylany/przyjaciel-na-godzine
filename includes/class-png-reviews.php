<?php

class PNG_Reviews {
    
    public static function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $table_name = $wpdb->prefix . 'png_reviews';
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            listing_id bigint(20) NOT NULL,
            reviewer_id bigint(20) NOT NULL,
            reviewee_id bigint(20) NOT NULL,
            rating int(11) NOT NULL,
            comment text,
            is_approved tinyint(1) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY listing_id (listing_id),
            KEY reviewer_id (reviewer_id),
            KEY reviewee_id (reviewee_id),
            KEY is_approved (is_approved)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    public static function add_review($listing_id, $reviewer_id, $reviewee_id, $rating, $comment) {
        global $wpdb;
        
        // Check if review already exists
        $existing = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) FROM {$wpdb->prefix}png_reviews 
            WHERE listing_id = %d AND reviewer_id = %d
        ", $listing_id, $reviewer_id));
        
        if ($existing > 0) {
            return false; // User already reviewed this listing
        }
        
        return $wpdb->insert(
            $wpdb->prefix . 'png_reviews',
            array(
                'listing_id' => $listing_id,
                'reviewer_id' => $reviewer_id,
                'reviewee_id' => $reviewee_id,
                'rating' => $rating,
                'comment' => wp_kses_post($comment),
                'is_approved' => 1, // Auto-approve for now
                'created_at' => current_time('mysql')
            ),
            array('%d', '%d', '%d', '%d', '%s', '%d', '%s')
        );
    }
    
    public static function get_listing_reviews($listing_id, $approved_only = true) {
        global $wpdb;
        
        $sql = $wpdb->prepare("
            SELECT r.*, u.display_name as reviewer_name 
            FROM {$wpdb->prefix}png_reviews r
            LEFT JOIN {$wpdb->prefix}png_user_profiles u ON r.reviewer_id = u.user_id
            WHERE r.listing_id = %d
        ", $listing_id);
        
        if ($approved_only) {
            $sql .= " AND r.is_approved = 1";
        }
        
        $sql .= " ORDER BY r.created_at DESC";
        
        return $wpdb->get_results($sql);
    }
    
    public static function get_user_avg_rating($user_id) {
        global $wpdb;
        
        return $wpdb->get_var($wpdb->prepare("
            SELECT AVG(rating) FROM {$wpdb->prefix}png_reviews 
            WHERE reviewee_id = %d AND is_approved = 1
        ", $user_id));
    }
    
    public static function handle_add_review() {
        if (!wp_verify_nonce($_POST['png_nonce'], 'png_add_review')) {
            wp_die('Security check failed');
        }
        
        $listing_id = intval($_POST['listing_id']);
        $reviewer_id = get_current_user_id();
        $reviewee_id = intval($_POST['reviewee_id']);
        $rating = intval($_POST['rating']);
        $comment = wp_kses_post($_POST['comment']);
        
        self::add_review($listing_id, $reviewer_id, $reviewee_id, $rating, $comment);
        
        // Update user average rating
        $avg_rating = self::get_user_avg_rating($reviewee_id);
        
        global $wpdb;
        $wpdb->update(
            $wpdb->prefix . 'png_user_profiles',
            array('avg_rating' => $avg_rating),
            array('user_id' => $reviewee_id)
        );
        
        wp_redirect(wp_get_referer() . '?message=review_added');
        exit;
    }
}

add_action('admin_post_png_add_review', array('PNG_Reviews', 'handle_add_review'));