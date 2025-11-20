<?php

class PNG_Payments {
    
    public static function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $table_name = $wpdb->prefix . 'png_payments';
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            listing_id bigint(20) DEFAULT NULL,
            payment_type varchar(50) NOT NULL,
            amount decimal(10,2) NOT NULL,
            currency varchar(3) DEFAULT 'PLN',
            provider varchar(20) NOT NULL,
            provider_payment_id varchar(255),
            status varchar(20) DEFAULT 'pending',
            payment_data text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY provider_payment_id (provider_payment_id),
            KEY status (status)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        // Log any errors
        if (!empty($wpdb->last_error)) {
            error_log('PNG Payments Table Error: ' . $wpdb->last_error);
        }
    }
    
    public static function payment_checkout_shortcode($atts) {
        if (!is_user_logged_in()) {
            return '<div class="png-container"><div class="png-alert png-alert-error">Musisz być zalogowany, aby dokonać płatności.</div></div>';
        }
        
        $payment_type = sanitize_text_field($_GET['type'] ?? '');
        $listing_id = isset($_GET['listing_id']) ? intval($_GET['listing_id']) : null;
        
        if (empty($payment_type)) {
            return '<div class="png-container"><div class="png-alert png-alert-error">Nieprawidłowy typ płatności.</div></div>';
        }
        
        ob_start();
        include PNG_PLUGIN_PATH . 'templates/payment-checkout.php';
        return ob_get_clean();
    }
    
    public static function payment_success_shortcode($atts) {
        ob_start();
        include PNG_PLUGIN_PATH . 'templates/payment-success.php';
        return ob_get_clean();
    }
    
    public static function payment_cancelled_shortcode($atts) {
        ob_start();
        include PNG_PLUGIN_PATH . 'templates/payment-cancelled.php';
        return ob_get_clean();
    }
    
    public static function create_payment($user_id, $amount, $currency, $payment_type, $listing_id = null, $provider = 'paypal') {
        global $wpdb;
        
        $payment_data = array(
            'user_id' => $user_id,
            'listing_id' => $listing_id,
            'payment_type' => $payment_type,
            'amount' => $amount,
            'currency' => $currency,
            'provider' => $provider,
            'status' => 'pending'
        );
        
        $result = $wpdb->insert(
            $wpdb->prefix . 'png_payments',
            $payment_data,
            array('%d', '%d', '%s', '%f', '%s', '%s', '%s')
        );
        
        if ($result) {
            return $wpdb->insert_id;
        }
        
        error_log('PNG Payment Creation Error: ' . $wpdb->last_error);
        return false;
    }
}

// Initialize payments table on plugin load
add_action('plugins_loaded', function() {
    PNG_Payments::create_tables();
});