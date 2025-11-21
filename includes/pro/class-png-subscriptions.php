<?php
/**
 * Lokalizacja: /includes/pro/class-png-subscriptions.php
 */

if (!defined('ABSPATH')) {
    exit;
}

class PNG_Subscriptions {
    
    public static function init() {
        add_action('png_check_expired_subscriptions', array(__CLASS__, 'check_expired_subscriptions'));
    }
    
    /**
     * Get subscription plans
     */
    public static function get_plans() {
        return array(
            'free' => array(
                'name' => __('Darmowy', 'png'),
                'price' => 0,
                'billing_cycle' => 'lifetime',
                'features' => array(
                    'max_listings' => 5,
                    'max_images' => 3,
                    'featured_listings' => 0,
                    'verified_badge' => false,
                    'priority_support' => false,
                    'analytics' => false,
                    'remove_ads' => false
                )
            ),
            'premium_monthly' => array(
                'name' => __('Premium Miesięczny', 'png'),
                'price' => 99,
                'billing_cycle' => 'monthly',
                'features' => array(
                    'max_listings' => 50,
                    'max_images' => 10,
                    'featured_listings' => 3,
                    'verified_badge' => true,
                    'priority_support' => true,
                    'analytics' => true,
                    'remove_ads' => true,
                    'boost_listings' => 5
                )
            ),
            'premium_yearly' => array(
                'name' => __('Premium Roczny', 'png'),
                'price' => 999,
                'billing_cycle' => 'yearly',
                'discount' => __('Oszczędź 17%', 'png'),
                'features' => array(
                    'max_listings' => 50,
                    'max_images' => 10,
                    'featured_listings' => 5,
                    'verified_badge' => true,
                    'priority_support' => true,
                    'analytics' => true,
                    'remove_ads' => true,
                    'boost_listings' => 10
                )
            ),
            'pro' => array(
                'name' => __('PRO', 'png'),
                'price' => 299,
                'billing_cycle' => 'monthly',
                'features' => array(
                    'max_listings' => 'unlimited',
                    'max_images' => 20,
                    'featured_listings' => 'unlimited',
                    'verified_badge' => true,
                    'priority_support' => true,
                    'analytics' => true,
                    'remove_ads' => true,
                    'boost_listings' => 'unlimited',
                    'api_access' => true,
                    'custom_branding' => true
                )
            )
        );
    }
    
    /**
     * Get user subscription
     */
    public static function get_user_subscription($user_id) {
        global $wpdb;
        
        return $wpdb->get_row($wpdb->prepare("
            SELECT * FROM {$wpdb->prefix}png_subscriptions
            WHERE user_id = %d
            AND status = 'active'
            ORDER BY started_at DESC
            LIMIT 1
        ", $user_id));
    }
    
    /**
     * Create subscription
     */
    public static function create($user_id, $plan_type, $payment_id = null) {
        global $wpdb;
        
        $plans = self::get_plans();
        
        if (!isset($plans[$plan_type])) {
            return new WP_Error('invalid_plan', __('Nieprawidłowy plan subskrypcji.', 'png'));
        }
        
        $plan = $plans[$plan_type];
        
        // Calculate expiry date
        $expires_at = self::calculate_expiry($plan['billing_cycle']);
        
        $result = $wpdb->insert(
            $wpdb->prefix . 'png_subscriptions',
            array(
                'user_id' => $user_id,
                'plan_type' => $plan_type,
                'status' => 'active',
                'price' => $plan['price'],
                'billing_cycle' => $plan['billing_cycle'],
                'started_at' => current_time('mysql'),
                'expires_at' => $expires_at,
                'payment_id' => $payment_id,
                'auto_renew' => 1
            ),
            array('%d', '%s', '%s', '%f', '%s', '%s', '%s', '%d', '%d')
        );
        
        if ($result) {
            // Update user profile
            $wpdb->update(
                $wpdb->prefix . 'png_user_profiles',
                array(
                    'subscription_type' => $plan_type,
                    'subscription_expires' => $expires_at
                ),
                array('user_id' => $user_id)
            );
            
            // Give bonus points
            PNG_Users::add_points($user_id, 100, 'subscription_purchased');
            
            // Notify user
            PNG_Notifications::create(
                $user_id,
                'subscription_activated',
                __('Subskrypcja aktywowana!', 'png'),
                sprintf(__('Twoja subskrypcja %s jest teraz aktywna!', 'png'), $plan['name']),
                home_url('/subskrypcje')
            );
            
            do_action('png_subscription_created', $wpdb->insert_id, $user_id, $plan_type);
        }
        
        return $result ? $wpdb->insert_id : false;
    }
    
    /**
     * Cancel subscription
     */
    public static function cancel($subscription_id) {
        global $wpdb;
        
        $subscription = $wpdb->get_row($wpdb->prepare("
            SELECT * FROM {$wpdb->prefix}png_subscriptions WHERE id = %d
        ", $subscription_id));
        
        if (!$subscription) {
            return false;
        }
        
        $result = $wpdb->update(
            $wpdb->prefix . 'png_subscriptions',
            array(
                'status' => 'cancelled',
                'cancelled_at' => current_time('mysql'),
                'auto_renew' => 0
            ),
            array('id' => $subscription_id)
        );
        
        if ($result) {
            PNG_Notifications::create(
                $subscription->user_id,
                'subscription_cancelled',
                __('Subskrypcja anulowana', 'png'),
                __('Twoja subskrypcja została anulowana. Zachowasz dostęp do końca okresu rozliczeniowego.', 'png')
            );
            
            do_action('png_subscription_cancelled', $subscription_id, $subscription->user_id);
        }
        
        return $result;
    }
    
    /**
     * Check expired subscriptions
     */
    public static function check_expired_subscriptions() {
        global $wpdb;
        
        $expired = $wpdb->get_results("
            SELECT * FROM {$wpdb->prefix}png_subscriptions
            WHERE status = 'active'
            AND expires_at < NOW()
        ");
        
        foreach ($expired as $subscription) {
            if ($subscription->auto_renew) {
                // Try to renew
                self::auto_renew($subscription);
            } else {
                // Expire subscription
                self::expire($subscription->id);
            }
        }
    }
    
    /**
     * Auto-renew subscription
     */
    private static function auto_renew($subscription) {
        global $wpdb;
        
        // Here you would integrate with payment gateway to charge customer
        // For now, we'll just extend the subscription
        
        $plans = self::get_plans();
        $plan = $plans[$subscription->plan_type];
        
        $new_expires = self::calculate_expiry($plan['billing_cycle'], $subscription->expires_at);
        
        $wpdb->update(
            $wpdb->prefix . 'png_subscriptions',
            array('expires_at' => $new_expires),
            array('id' => $subscription->id)
        );
        
        PNG_Notifications::create(
            $subscription->user_id,
            'subscription_renewed',
            __('Subskrypcja odnowiona', 'png'),
            __('Twoja subskrypcja została automatycznie odnowiona.', 'png')
        );
        
        do_action('png_subscription_renewed', $subscription->id, $subscription->user_id);
    }
    
    /**
     * Expire subscription
     */
    private static function expire($subscription_id) {
        global $wpdb;
        
        $subscription = $wpdb->get_row($wpdb->prepare("
            SELECT * FROM {$wpdb->prefix}png_subscriptions WHERE id = %d
        ", $subscription_id));
        
        $wpdb->update(
            $wpdb->prefix . 'png_subscriptions',
            array('status' => 'expired'),
            array('id' => $subscription_id)
        );
        
        // Update user profile
        $wpdb->update(
            $wpdb->prefix . 'png_user_profiles',
            array(
                'subscription_type' => 'free',
                'subscription_expires' => null
            ),
            array('user_id' => $subscription->user_id)
        );
        
        PNG_Notifications::create(
            $subscription->user_id,
            'subscription_expired',
            __('Subskrypcja wygasła', 'png'),
            __('Twoja subskrypcja wygasła. Odnów ją, aby kontynuować korzystanie z funkcji premium.', 'png'),
            home_url('/subskrypcje')
        );
        
        do_action('png_subscription_expired', $subscription_id, $subscription->user_id);
    }
    
    /**
     * Get user features
     */
    public static function get_user_features($user_id) {
        $subscription = self::get_user_subscription($user_id);
        $plans = self::get_plans();
        
        if (!$subscription || $subscription->status !== 'active') {
            return $plans['free']['features'];
        }
        
        return $plans[$subscription->plan_type]['features'] ?? $plans['free']['features'];
    }
    
    /**
     * Check if user has feature
     */
    public static function user_has_feature($user_id, $feature) {
        $features = self::get_user_features($user_id);
        return isset($features[$feature]) && $features[$feature];
    }
    
    /**
     * Get remaining featured listings
     */
    public static function get_remaining_featured($user_id) {
        $features = self::get_user_features($user_id);
        
        if ($features['featured_listings'] === 'unlimited') {
            return 'unlimited';
        }
        
        $used = get_user_meta($user_id, '_featured_used_this_month', true) ?: 0;
        $available = $features['featured_listings'] - $used;
        
        return max(0, $available);
    }
    
    /**
     * Calculate expiry date
     */
    private static function calculate_expiry($billing_cycle, $from_date = null) {
        $from = $from_date ? strtotime($from_date) : time();
        
        switch ($billing_cycle) {
            case 'monthly':
                return date('Y-m-d H:i:s', strtotime('+1 month', $from));
            case 'yearly':
                return date('Y-m-d H:i:s', strtotime('+1 year', $from));
            case 'lifetime':
                return date('Y-m-d H:i:s', strtotime('+100 years', $from));
            default:
                return date('Y-m-d H:i:s', strtotime('+1 month', $from));
        }
    }
    
    /**
     * Get subscription history
     */
    public static function get_history($user_id) {
        global $wpdb;
        
        return $wpdb->get_results($wpdb->prepare("
            SELECT * FROM {$wpdb->prefix}png_subscriptions
            WHERE user_id = %d
            ORDER BY started_at DESC
        ", $user_id));
    }
    
    /**
     * Upgrade subscription
     */
    public static function upgrade($user_id, $new_plan_type, $payment_id = null) {
        // Cancel current subscription
        $current = self::get_user_subscription($user_id);
        if ($current) {
            self::cancel($current->id);
        }
        
        // Create new subscription
        return self::create($user_id, $new_plan_type, $payment_id);
    }
}