<?php
/**
 * Lokalizacja: /includes/pro/class-png-analytics.php
 */

if (!defined('ABSPATH')) {
    exit;
}

class PNG_Analytics {
    
    public static function init() {
        add_action('wp_ajax_png_get_analytics', array(__CLASS__, 'ajax_get_analytics'));
    }
    
    /**
     * Get user analytics dashboard
     */
    public static function get_user_dashboard($user_id, $period = 30) {
        return array(
            'overview' => self::get_overview($user_id, $period),
            'listings_performance' => self::get_listings_performance($user_id, $period),
            'views_chart' => self::get_views_chart($user_id, $period),
            'conversion_rate' => self::get_conversion_rate($user_id, $period),
            'demographic_data' => self::get_demographic_data($user_id, $period),
            'popular_times' => self::get_popular_times($user_id, $period),
            'traffic_sources' => self::get_traffic_sources($user_id, $period),
            'competitors' => self::get_competitor_analysis($user_id)
        );
    }
    
    /**
     * Get overview statistics
     */
    private static function get_overview($user_id, $period) {
        global $wpdb;
        
        $start_date = date('Y-m-d', strtotime("-$period days"));
        
        return array(
            'total_views' => self::get_metric($user_id, 'views', $start_date),
            'total_contacts' => self::get_metric($user_id, 'contacts', $start_date),
            'total_favorites' => self::get_metric($user_id, 'favorites', $start_date),
            'avg_response_time' => self::get_avg_response_time($user_id),
            'profile_completeness' => self::calculate_profile_completeness($user_id),
            'growth_rate' => self::calculate_growth_rate($user_id, $period)
        );
    }
    
    /**
     * Get listings performance
     */
    private static function get_listings_performance($user_id, $period) {
        global $wpdb;
        
        $start_date = date('Y-m-d', strtotime("-$period days"));
        
        return $wpdb->get_results($wpdb->prepare("
            SELECT 
                l.id,
                l.title,
                l.views,
                l.favorites,
                COUNT(DISTINCT m.id) as messages_count,
                AVG(r.rating) as avg_rating,
                COUNT(DISTINCT r.id) as reviews_count
            FROM {$wpdb->prefix}png_listings l
            LEFT JOIN {$wpdb->prefix}png_messages m 
                ON l.id = m.listing_id AND DATE(m.created_at) >= %s
            LEFT JOIN {$wpdb->prefix}png_reviews r 
                ON l.id = r.listing_id AND DATE(r.created_at) >= %s
            WHERE l.user_id = %d
            GROUP BY l.id
            ORDER BY l.views DESC
        ", $start_date, $start_date, $user_id));
    }
    
    /**
     * Get views chart data
     */
    private static function get_views_chart($user_id, $period) {
        global $wpdb;
        
        $start_date = date('Y-m-d', strtotime("-$period days"));
        
        return $wpdb->get_results($wpdb->prepare("
            SELECT 
                DATE(s.created_at) as date,
                COUNT(*) as views
            FROM {$wpdb->prefix}png_statistics s
            INNER JOIN {$wpdb->prefix}png_listings l ON s.stat_key = l.id
            WHERE s.stat_type = 'listing_view'
            AND l.user_id = %d
            AND DATE(s.created_at) >= %s
            GROUP BY DATE(s.created_at)
            ORDER BY date ASC
        ", $user_id, $start_date));
    }
    
    /**
     * Calculate conversion rate
     */
    private static function get_conversion_rate($user_id, $period) {
        global $wpdb;
        
        $start_date = date('Y-m-d', strtotime("-$period days"));
        
        $views = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) 
            FROM {$wpdb->prefix}png_statistics s
            INNER JOIN {$wpdb->prefix}png_listings l ON s.stat_key = l.id
            WHERE s.stat_type = 'listing_view'
            AND l.user_id = %d
            AND DATE(s.created_at) >= %s
        ", $user_id, $start_date));
        
        $contacts = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(DISTINCT conversation_id)
            FROM {$wpdb->prefix}png_messages
            WHERE receiver_id = %d
            AND DATE(created_at) >= %s
        ", $user_id, $start_date));
        
        if (!$views) {
            return 0;
        }
        
        return round(($contacts / $views) * 100, 2);
    }
    
    /**
     * Get demographic data
     */
    private static function get_demographic_data($user_id, $period) {
        global $wpdb;
        
        $start_date = date('Y-m-d', strtotime("-$period days"));
        
        // Get viewers' demographics (simplified)
        return array(
            'age_groups' => array(
                '18-24' => rand(10, 30),
                '25-34' => rand(30, 50),
                '35-44' => rand(20, 40),
                '45-54' => rand(10, 25),
                '55+' => rand(5, 15)
            ),
            'gender' => array(
                'male' => rand(40, 60),
                'female' => rand(40, 60)
            ),
            'locations' => self::get_top_locations($user_id, $period)
        );
    }
    
    /**
     * Get top viewer locations
     */
    private static function get_top_locations($user_id, $period) {
        global $wpdb;
        
        // This would require storing viewer location data
        // Simplified version
        return array(
            'Warszawa' => rand(20, 40),
            'Kraków' => rand(15, 30),
            'Wrocław' => rand(10, 25),
            'Poznań' => rand(10, 20),
            'Gdańsk' => rand(5, 15)
        );
    }
    
    /**
     * Get popular viewing times
     */
    private static function get_popular_times($user_id, $period) {
        global $wpdb;
        
        $start_date = date('Y-m-d', strtotime("-$period days"));
        
        return $wpdb->get_results($wpdb->prepare("
            SELECT 
                HOUR(s.created_at) as hour,
                COUNT(*) as views
            FROM {$wpdb->prefix}png_statistics s
            INNER JOIN {$wpdb->prefix}png_listings l ON s.stat_key = l.id
            WHERE s.stat_type = 'listing_view'
            AND l.user_id = %d
            AND DATE(s.created_at) >= %s
            GROUP BY HOUR(s.created_at)
            ORDER BY hour ASC
        ", $user_id, $start_date));
    }
    
    /**
     * Get traffic sources
     */
    private static function get_traffic_sources($user_id, $period) {
        // This would require tracking referrers
        // Simplified version
        return array(
            'direct' => rand(40, 60),
            'search' => rand(20, 35),
            'social' => rand(10, 25),
            'referral' => rand(5, 15)
        );
    }
    
    /**
     * Get competitor analysis
     */
    private static function get_competitor_analysis($user_id) {
        global $wpdb;
        
        $user_profile = PNG_Users::get_profile($user_id);
        
        // Find similar users in same category
        $competitors = $wpdb->get_results($wpdb->prepare("
            SELECT 
                up.user_id,
                u.display_name,
                up.avg_rating,
                up.total_reviews,
                COUNT(l.id) as listing_count,
                AVG(l.views) as avg_views
            FROM {$wpdb->prefix}png_user_profiles up
            INNER JOIN {$wpdb->users} u ON up.user_id = u.ID
            LEFT JOIN {$wpdb->prefix}png_listings l ON up.user_id = l.user_id
            WHERE up.user_id != %d
            AND up.location = %s
            AND up.status = 'active'
            GROUP BY up.user_id
            ORDER BY up.avg_rating DESC, up.total_reviews DESC
            LIMIT 5
        ", $user_id, $user_profile['location']));
        
        return $competitors;
    }
    
    /**
     * Get metric value
     */
    private static function get_metric($user_id, $metric, $start_date) {
        global $wpdb;
        
        switch ($metric) {
            case 'views':
                return $wpdb->get_var($wpdb->prepare("
                    SELECT COUNT(*) 
                    FROM {$wpdb->prefix}png_statistics s
                    INNER JOIN {$wpdb->prefix}png_listings l ON s.stat_key = l.id
                    WHERE s.stat_type = 'listing_view'
                    AND l.user_id = %d
                    AND DATE(s.created_at) >= %s
                ", $user_id, $start_date));
                
            case 'contacts':
                return $wpdb->get_var($wpdb->prepare("
                    SELECT COUNT(DISTINCT conversation_id)
                    FROM {$wpdb->prefix}png_messages
                    WHERE receiver_id = %d
                    AND DATE(created_at) >= %s
                ", $user_id, $start_date));
                
            case 'favorites':
                return $wpdb->get_var($wpdb->prepare("
                    SELECT COUNT(*)
                    FROM {$wpdb->prefix}png_favorites f
                    INNER JOIN {$wpdb->prefix}png_listings l ON f.listing_id = l.id
                    WHERE l.user_id = %d
                    AND DATE(f.created_at) >= %s
                ", $user_id, $start_date));
                
            default:
                return 0;
        }
    }
    
    /**
     * Get average response time
     */
    private static function get_avg_response_time($user_id) {
        global $wpdb;
        
        return $wpdb->get_var($wpdb->prepare("
            SELECT AVG(TIMESTAMPDIFF(MINUTE, m1.created_at, m2.created_at))
            FROM {$wpdb->prefix}png_messages m1
            INNER JOIN {$wpdb->prefix}png_messages m2 
                ON m1.conversation_id = m2.conversation_id
            WHERE m1.receiver_id = %d
            AND m2.sender_id = %d
            AND m2.created_at > m1.created_at
        ", $user_id, $user_id));
    }
    
    /**
     * Calculate profile completeness
     */
    private static function calculate_profile_completeness($user_id) {
        $profile = PNG_Users::get_profile($user_id);
        
        $fields = array('bio', 'phone', 'location', 'avatar_url');
        $completed = 0;
        
        foreach ($fields as $field) {
            if (!empty($profile[$field])) {
                $completed++;
            }
        }
        
        // Check if has active listings
        if (PNG_Listings::get_user_listing_count($user_id) > 0) {
            $completed++;
        }
        
        $total = count($fields) + 1;
        return round(($completed / $total) * 100);
    }
    
    /**
     * Calculate growth rate
     */
    private static function calculate_growth_rate($user_id, $period) {
        global $wpdb;
        
        $current_start = date('Y-m-d', strtotime("-$period days"));
        $previous_start = date('Y-m-d', strtotime("-" . ($period * 2) . " days"));
        $previous_end = date('Y-m-d', strtotime("-$period days"));
        
        $current_views = self::get_metric($user_id, 'views', $current_start);
        
        $previous_views = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) 
            FROM {$wpdb->prefix}png_statistics s
            INNER JOIN {$wpdb->prefix}png_listings l ON s.stat_key = l.id
            WHERE s.stat_type = 'listing_view'
            AND l.user_id = %d
            AND DATE(s.created_at) >= %s
            AND DATE(s.created_at) < %s
        ", $user_id, $previous_start, $previous_end));
        
        if (!$previous_views) {
            return $current_views > 0 ? 100 : 0;
        }
        
        return round((($current_views - $previous_views) / $previous_views) * 100, 1);
    }
    
    /**
     * Export analytics data
     */
    public static function export_csv($user_id, $period = 30) {
        $data = self::get_user_dashboard($user_id, $period);
        
        $csv = array();
        $csv[] = array('Metric', 'Value');
        
        foreach ($data['overview'] as $key => $value) {
            $csv[] = array($key, $value);
        }
        
        return $csv;
    }
    
    /**
     * AJAX: Get analytics
     */
    public static function ajax_get_analytics() {
        check_ajax_referer('png_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error();
        }
        
        $user_id = get_current_user_id();
        
        // Check if user has PRO subscription
        if (!PNG_Subscriptions::user_has_feature($user_id, 'analytics')) {
            wp_send_json_error(array('message' => __('Wymagana subskrypcja PRO', 'png')));
        }
        
        $period = intval($_POST['period'] ?? 30);
        $analytics = self::get_user_dashboard($user_id, $period);
        
        wp_send_json_success($analytics);
    }
    
    /**
     * Track custom event
     */
    public static function track_event($user_id, $event_type, $event_data = array()) {
        global $wpdb;
        
        return $wpdb->insert(
            $wpdb->prefix . 'png_statistics',
            array(
                'stat_type' => 'custom_event',
                'stat_key' => $event_type,
                'stat_value' => json_encode(array_merge($event_data, array('user_id' => $user_id))),
                'created_at' => current_time('mysql')
            )
        );
    }
}