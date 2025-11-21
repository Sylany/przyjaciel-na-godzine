<?php
/**
 * Lokalizacja: /includes/class-png-statistics.php
 */

if (!defined('ABSPATH')) {
    exit;
}

class PNG_Statistics {
    
    /**
     * Increment listing view
     */
    public static function increment_view($listing_id) {
        global $wpdb;
        
        // Update post meta
        $views = (int) get_post_meta($listing_id, '_views', true);
        update_post_meta($listing_id, '_views', $views + 1);
        
        // Update custom table
        $wpdb->query($wpdb->prepare("
            UPDATE {$wpdb->prefix}png_listings 
            SET views = views + 1 
            WHERE id = %d
        ", $listing_id));
        
        // Log view
        self::log_stat('listing_view', $listing_id, array(
            'user_id' => get_current_user_id(),
            'ip' => PNG_Security::get_client_ip()
        ));
    }
    
    /**
     * Track search
     */
    public static function track_search($query, $results_count) {
        self::log_stat('search', 0, array(
            'query' => $query,
            'results' => $results_count,
            'user_id' => get_current_user_id()
        ));
    }
    
    /**
     * Track message sent
     */
    public static function track_message($sender_id, $receiver_id) {
        self::log_stat('message_sent', 0, array(
            'sender_id' => $sender_id,
            'receiver_id' => $receiver_id
        ));
    }
    
    /**
     * Log statistic
     */
    private static function log_stat($stat_type, $related_id = 0, $data = array()) {
        global $wpdb;
        
        return $wpdb->insert(
            $wpdb->prefix . 'png_statistics',
            array(
                'stat_type' => $stat_type,
                'stat_key' => $related_id,
                'stat_value' => json_encode($data),
                'period' => 'daily',
                'date' => current_time('Y-m-d'),
                'created_at' => current_time('mysql')
            ),
            array('%s', '%s', '%s', '%s', '%s', '%s')
        );
    }
    
    /**
     * Get dashboard statistics
     */
    public static function get_dashboard_stats() {
        global $wpdb;
        
        return array(
            'total_users' => self::get_total_users(),
            'total_listings' => self::get_total_listings(),
            'active_listings' => self::get_active_listings(),
            'total_messages' => self::get_total_messages(),
            'total_reviews' => self::get_total_reviews(),
            'total_earnings' => self::get_total_earnings(),
            'new_users_today' => self::get_new_users_today(),
            'new_listings_today' => self::get_new_listings_today(),
            'messages_today' => self::get_messages_today(),
            'popular_categories' => self::get_popular_categories(),
            'top_users' => self::get_top_users(),
            'recent_activity' => self::get_recent_activity()
        );
    }
    
    /**
     * Get user statistics
     */
    public static function get_user_stats($user_id) {
        global $wpdb;
        
        return array(
            'profile_views' => self::get_user_profile_views($user_id),
            'listing_views' => self::get_user_listing_views($user_id),
            'total_messages' => self::get_user_messages_count($user_id),
            'response_rate' => self::get_user_response_rate($user_id),
            'avg_rating' => self::get_user_avg_rating($user_id),
            'total_earnings' => self::get_user_earnings($user_id),
            'views_by_date' => self::get_views_by_date($user_id, 30),
            'top_listings' => self::get_user_top_listings($user_id)
        );
    }
    
    /**
     * Get total users
     */
    private static function get_total_users() {
        return count_users()['total_users'];
    }
    
    /**
     * Get total listings
     */
    private static function get_total_listings() {
        $count = wp_count_posts('listing');
        return $count->publish + $count->pending + $count->draft;
    }
    
    /**
     * Get active listings
     */
    private static function get_active_listings() {
        $count = wp_count_posts('listing');
        return $count->publish;
    }
    
    /**
     * Get total messages
     */
    private static function get_total_messages() {
        global $wpdb;
        return (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}png_messages");
    }
    
    /**
     * Get total reviews
     */
    private static function get_total_reviews() {
        global $wpdb;
        return (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}png_reviews");
    }
    
    /**
     * Get total earnings
     */
    private static function get_total_earnings() {
        global $wpdb;
        return (float) $wpdb->get_var("
            SELECT SUM(amount) FROM {$wpdb->prefix}png_payments 
            WHERE status = 'completed'
        ");
    }
    
    /**
     * Get new users today
     */
    private static function get_new_users_today() {
        global $wpdb;
        
        $today = current_time('Y-m-d');
        
        return (int) $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) FROM {$wpdb->users}
            WHERE DATE(user_registered) = %s
        ", $today));
    }
    
    /**
     * Get new listings today
     */
    private static function get_new_listings_today() {
        global $wpdb;
        
        $today = current_time('Y-m-d');
        
        return (int) $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) FROM {$wpdb->posts}
            WHERE post_type = 'listing'
            AND DATE(post_date) = %s
        ", $today));
    }
    
    /**
     * Get messages today
     */
    private static function get_messages_today() {
        global $wpdb;
        
        $today = current_time('Y-m-d');
        
        return (int) $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) FROM {$wpdb->prefix}png_messages
            WHERE DATE(created_at) = %s
        ", $today));
    }
    
    /**
     * Get popular categories
     */
    private static function get_popular_categories($limit = 5) {
        global $wpdb;
        
        return $wpdb->get_results($wpdb->prepare("
            SELECT t.name, COUNT(tr.object_id) as count
            FROM {$wpdb->terms} t
            INNER JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id
            INNER JOIN {$wpdb->term_relationships} tr ON tt.term_taxonomy_id = tr.term_taxonomy_id
            INNER JOIN {$wpdb->posts} p ON tr.object_id = p.ID
            WHERE tt.taxonomy = 'listing_category'
            AND p.post_type = 'listing'
            AND p.post_status = 'publish'
            GROUP BY t.term_id
            ORDER BY count DESC
            LIMIT %d
        ", $limit));
    }
    
    /**
     * Get top users
     */
    private static function get_top_users($limit = 10) {
        global $wpdb;
        
        return $wpdb->get_results($wpdb->prepare("
            SELECT 
                up.user_id,
                u.display_name,
                up.avg_rating,
                up.total_reviews,
                up.level,
                COUNT(p.ID) as listing_count
            FROM {$wpdb->prefix}png_user_profiles up
            INNER JOIN {$wpdb->users} u ON up.user_id = u.ID
            LEFT JOIN {$wpdb->posts} p ON u.ID = p.post_author AND p.post_type = 'listing'
            WHERE up.status = 'active'
            GROUP BY up.user_id
            ORDER BY up.avg_rating DESC, up.total_reviews DESC
            LIMIT %d
        ", $limit));
    }
    
    /**
     * Get recent activity
     */
    private static function get_recent_activity($limit = 10) {
        global $wpdb;
        
        return $wpdb->get_results($wpdb->prepare("
            SELECT * FROM {$wpdb->prefix}png_statistics
            ORDER BY created_at DESC
            LIMIT %d
        ", $limit));
    }
    
    /**
     * Get user profile views
     */
    private static function get_user_profile_views($user_id) {
        return (int) get_user_meta($user_id, '_profile_views', true);
    }
    
    /**
     * Get user listing views
     */
    private static function get_user_listing_views($user_id) {
        global $wpdb;
        
        return (int) $wpdb->get_var($wpdb->prepare("
            SELECT SUM(views) FROM {$wpdb->prefix}png_listings
            WHERE user_id = %d
        ", $user_id));
    }
    
    /**
     * Get user messages count
     */
    private static function get_user_messages_count($user_id) {
        global $wpdb;
        
        return (int) $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) FROM {$wpdb->prefix}png_messages
            WHERE sender_id = %d OR receiver_id = %d
        ", $user_id, $user_id));
    }
    
    /**
     * Get user response rate
     */
    private static function get_user_response_rate($user_id) {
        global $wpdb;
        
        $total_received = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(DISTINCT conversation_id)
            FROM {$wpdb->prefix}png_messages
            WHERE receiver_id = %d
        ", $user_id));
        
        if (!$total_received) {
            return 100;
        }
        
        $responded = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(DISTINCT conversation_id)
            FROM {$wpdb->prefix}png_messages
            WHERE sender_id = %d
        ", $user_id));
        
        return round(($responded / $total_received) * 100, 1);
    }
    
    /**
     * Get user average rating
     */
    private static function get_user_avg_rating($user_id) {
        global $wpdb;
        
        return (float) $wpdb->get_var($wpdb->prepare("
            SELECT AVG(rating) FROM {$wpdb->prefix}png_reviews
            WHERE reviewee_id = %d AND status = 'approved'
        ", $user_id));
    }
    
    /**
     * Get user earnings
     */
    private static function get_user_earnings($user_id) {
        global $wpdb;
        
        $profile = $wpdb->get_row($wpdb->prepare("
            SELECT total_earnings FROM {$wpdb->prefix}png_user_profiles
            WHERE user_id = %d
        ", $user_id));
        
        return $profile ? (float) $profile->total_earnings : 0;
    }
    
    /**
     * Get views by date
     */
    private static function get_views_by_date($user_id, $days = 30) {
        global $wpdb;
        
        $start_date = date('Y-m-d', strtotime("-$days days"));
        
        return $wpdb->get_results($wpdb->prepare("
            SELECT 
                DATE(s.created_at) as date,
                COUNT(*) as views
            FROM {$wpdb->prefix}png_statistics s
            INNER JOIN {$wpdb->prefix}png_listings l ON s.stat_key = l.id
            WHERE s.stat_type = 'listing_view'
            AND l.user_id = %d
            AND s.created_at >= %s
            GROUP BY DATE(s.created_at)
            ORDER BY date ASC
        ", $user_id, $start_date));
    }
    
    /**
     * Get user top listings
     */
    private static function get_user_top_listings($user_id, $limit = 5) {
        global $wpdb;
        
        return $wpdb->get_results($wpdb->prepare("
            SELECT id, title, views, favorites
            FROM {$wpdb->prefix}png_listings
            WHERE user_id = %d
            AND status = 'publish'
            ORDER BY views DESC
            LIMIT %d
        ", $user_id, $limit));
    }
    
    /**
     * Update hourly stats
     */
    public static function update_hourly_stats() {
        // Calculate and cache stats
        $stats = self::get_dashboard_stats();
        set_transient('png_hourly_stats', $stats, HOUR_IN_SECONDS);
    }
    
    /**
     * Update daily stats
     */
    public static function update_daily_stats() {
        global $wpdb;
        
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        
        // Calculate daily stats
        $daily_stats = array(
            'new_users' => self::get_new_users_on_date($yesterday),
            'new_listings' => self::get_new_listings_on_date($yesterday),
            'messages_sent' => self::get_messages_on_date($yesterday),
            'payments' => self::get_payments_on_date($yesterday)
        );
        
        // Store in statistics table
        foreach ($daily_stats as $key => $value) {
            $wpdb->insert(
                $wpdb->prefix . 'png_statistics',
                array(
                    'stat_type' => 'daily_' . $key,
                    'stat_key' => $yesterday,
                    'stat_value' => $value,
                    'period' => 'daily',
                    'date' => $yesterday,
                    'created_at' => current_time('mysql')
                )
            );
        }
    }
    
    private static function get_new_users_on_date($date) {
        global $wpdb;
        return (int) $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) FROM {$wpdb->users}
            WHERE DATE(user_registered) = %s
        ", $date));
    }
    
    private static function get_new_listings_on_date($date) {
        global $wpdb;
        return (int) $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) FROM {$wpdb->posts}
            WHERE post_type = 'listing' AND DATE(post_date) = %s
        ", $date));
    }
    
    private static function get_messages_on_date($date) {
        global $wpdb;
        return (int) $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) FROM {$wpdb->prefix}png_messages
            WHERE DATE(created_at) = %s
        ", $date));
    }
    
    private static function get_payments_on_date($date) {
        global $wpdb;
        return (float) $wpdb->get_var($wpdb->prepare("
            SELECT SUM(amount) FROM {$wpdb->prefix}png_payments
            WHERE DATE(created_at) = %s AND status = 'completed'
        ", $date));
    }
}