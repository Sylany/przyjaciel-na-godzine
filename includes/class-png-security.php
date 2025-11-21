<?php
if (!defined('ABSPATH')) {
    exit;
}

class PNG_Security {
    
    private static $max_login_attempts = 5;
    private static $lockout_duration = 3600; // 1 hour
    
    public static function init() {
        add_action('wp_login_failed', array(__CLASS__, 'log_failed_login'));
        add_filter('authenticate', array(__CLASS__, 'check_login_attempts'), 30, 3);
        add_action('wp_login', array(__CLASS__, 'clear_login_attempts'), 10, 2);
        
        // Content security
        add_filter('png_before_save_listing', array(__CLASS__, 'sanitize_listing_content'));
        add_filter('png_before_save_message', array(__CLASS__, 'sanitize_message_content'));
        
        // Rate limiting
        add_action('png_check_rate_limit', array(__CLASS__, 'check_rate_limit'));
    }
    
    /**
     * Log failed login attempts
     */
    public static function log_failed_login($username) {
        $ip = self::get_client_ip();
        $attempts = get_transient('png_login_attempts_' . $ip);
        
        if ($attempts === false) {
            $attempts = array();
        }
        
        $attempts[] = time();
        
        set_transient('png_login_attempts_' . $ip, $attempts, self::$lockout_duration);
    }
    
    /**
     * Check login attempts and block if necessary
     */
    public static function check_login_attempts($user, $username, $password) {
        if (empty($username) || empty($password)) {
            return $user;
        }
        
        $ip = self::get_client_ip();
        $attempts = get_transient('png_login_attempts_' . $ip);
        
        if (is_array($attempts) && count($attempts) >= self::$max_login_attempts) {
            $error = new WP_Error();
            $error->add('too_many_attempts', 
                sprintf(__('Zbyt wiele prób logowania. Spróbuj ponownie za %d minut.', 'png'), 
                    ceil(self::$lockout_duration / 60))
            );
            return $error;
        }
        
        return $user;
    }
    
    /**
     * Clear login attempts on successful login
     */
    public static function clear_login_attempts($username, $user) {
        $ip = self::get_client_ip();
        delete_transient('png_login_attempts_' . $ip);
    }
    
    /**
     * Get client IP address
     */
    public static function get_client_ip() {
        $ip = '';
        
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        
        return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '0.0.0.0';
    }
    
    /**
     * Sanitize listing content
     */
    public static function sanitize_listing_content($data) {
        $data['title'] = sanitize_text_field($data['title']);
        $data['description'] = wp_kses_post($data['description']);
        $data['location'] = sanitize_text_field($data['location']);
        $data['price'] = floatval($data['price']);
        
        // Remove dangerous HTML
        $data['description'] = self::remove_dangerous_html($data['description']);
        
        return $data;
    }
    
    /**
     * Sanitize message content
     */
    public static function sanitize_message_content($message) {
        $message = sanitize_textarea_field($message);
        $message = self::remove_dangerous_patterns($message);
        
        return $message;
    }
    
    /**
     * Remove dangerous HTML tags and attributes
     */
    private static function remove_dangerous_html($content) {
        $allowed_tags = array(
            'p' => array(),
            'br' => array(),
            'strong' => array(),
            'em' => array(),
            'u' => array(),
            'ul' => array(),
            'ol' => array(),
            'li' => array(),
            'a' => array('href' => array(), 'title' => array())
        );
        
        return wp_kses($content, $allowed_tags);
    }
    
    /**
     * Remove dangerous patterns (URLs, scripts, etc.)
     */
    private static function remove_dangerous_patterns($content) {
        // Remove potentially malicious patterns
        $patterns = array(
            '/javascript:/i',
            '/on\w+\s*=/i', // Event handlers
            '/<script/i',
            '/<iframe/i',
            '/<object/i',
            '/<embed/i'
        );
        
        foreach ($patterns as $pattern) {
            $content = preg_replace($pattern, '', $content);
        }
        
        return $content;
    }
    
    /**
     * Validate file upload
     */
    public static function validate_file_upload($file) {
        $allowed_types = array('image/jpeg', 'image/png', 'image/gif', 'image/webp');
        $max_size = 5 * 1024 * 1024; // 5MB
        
        if (!in_array($file['type'], $allowed_types)) {
            return new WP_Error('invalid_type', __('Nieprawidłowy typ pliku. Dozwolone: JPG, PNG, GIF, WEBP', 'png'));
        }
        
        if ($file['size'] > $max_size) {
            return new WP_Error('file_too_large', __('Plik jest za duży. Maksymalny rozmiar: 5MB', 'png'));
        }
        
        // Check if it's really an image
        $check = getimagesize($file['tmp_name']);
        if ($check === false) {
            return new WP_Error('not_image', __('Plik nie jest obrazem.', 'png'));
        }
        
        return true;
    }
    
    /**
     * Generate secure token
     */
    public static function generate_token($length = 32) {
        return bin2hex(random_bytes($length / 2));
    }
    
    /**
     * Verify CSRF token
     */
    public static function verify_token($token, $action = 'png_action') {
        return wp_verify_nonce($token, $action);
    }
    
    /**
     * Rate limiting
     */
    public static function check_rate_limit($action, $limit = 10, $period = 3600) {
        if (!is_user_logged_in()) {
            $identifier = self::get_client_ip();
        } else {
            $identifier = 'user_' . get_current_user_id();
        }
        
        $key = 'png_rate_limit_' . $action . '_' . $identifier;
        $attempts = get_transient($key);
        
        if ($attempts === false) {
            set_transient($key, 1, $period);
            return true;
        }
        
        if ($attempts >= $limit) {
            return new WP_Error('rate_limit_exceeded', 
                sprintf(__('Przekroczono limit. Spróbuj ponownie za %d minut.', 'png'), 
                    ceil($period / 60))
            );
        }
        
        set_transient($key, $attempts + 1, $period);
        return true;
    }
    
    /**
     * Hash sensitive data
     */
    public static function hash_data($data) {
        return hash('sha256', $data . AUTH_SALT);
    }
    
    /**
     * Encrypt sensitive data
     */
    public static function encrypt($data) {
        if (function_exists('openssl_encrypt')) {
            $key = substr(AUTH_KEY, 0, 32);
            $iv = substr(SECURE_AUTH_KEY, 0, 16);
            return openssl_encrypt($data, 'AES-256-CBC', $key, 0, $iv);
        }
        return base64_encode($data);
    }
    
    /**
     * Decrypt sensitive data
     */
    public static function decrypt($data) {
        if (function_exists('openssl_decrypt')) {
            $key = substr(AUTH_KEY, 0, 32);
            $iv = substr(SECURE_AUTH_KEY, 0, 16);
            return openssl_decrypt($data, 'AES-256-CBC', $key, 0, $iv);
        }
        return base64_decode($data);
    }
    
    /**
     * Check if user is banned
     */
    public static function is_user_banned($user_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'png_user_profiles';
        
        $status = $wpdb->get_var($wpdb->prepare(
            "SELECT status FROM $table WHERE user_id = %d",
            $user_id
        ));
        
        return $status === 'banned';
    }
    
    /**
     * Ban user
     */
    public static function ban_user($user_id, $reason = '') {
        global $wpdb;
        $table = $wpdb->prefix . 'png_user_profiles';
        
        $wpdb->update(
            $table,
            array('status' => 'banned'),
            array('user_id' => $user_id),
            array('%s'),
            array('%d')
        );
        
        // Log ban
        update_user_meta($user_id, '_png_ban_reason', $reason);
        update_user_meta($user_id, '_png_ban_date', current_time('mysql'));
        
        // Deactivate all listings
        $wpdb->update(
            $wpdb->posts,
            array('post_status' => 'draft'),
            array('post_type' => 'listing', 'post_author' => $user_id),
            array('%s'),
            array('%s', '%d')
        );
        
        do_action('png_user_banned', $user_id, $reason);
    }
    
    /**
     * Validate email
     */
    public static function validate_email($email) {
        $email = sanitize_email($email);
        
        if (!is_email($email)) {
            return new WP_Error('invalid_email', __('Nieprawidłowy adres email.', 'png'));
        }
        
        // Check for disposable email domains
        $disposable_domains = array('tempmail.com', '10minutemail.com', 'guerrillamail.com');
        $domain = substr(strrchr($email, "@"), 1);
        
        if (in_array($domain, $disposable_domains)) {
            return new WP_Error('disposable_email', __('Adresy email jednorazowego użytku nie są dozwolone.', 'png'));
        }
        
        return true;
    }
    
    /**
     * Validate phone number
     */
    public static function validate_phone($phone) {
        $phone = preg_replace('/[^0-9+]/', '', $phone);
        
        if (strlen($phone) < 9 || strlen($phone) > 15) {
            return new WP_Error('invalid_phone', __('Nieprawidłowy numer telefonu.', 'png'));
        }
        
        return $phone;
    }
    
    /**
     * Log security event
     */
    public static function log_event($event_type, $details = array()) {
        $log_entry = array(
            'timestamp' => current_time('mysql'),
            'event_type' => $event_type,
            'user_id' => get_current_user_id(),
            'ip' => self::get_client_ip(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'details' => $details
        );
        
        // Store in options or custom table
        $logs = get_option('png_security_logs', array());
        $logs[] = $log_entry;
        
        // Keep only last 1000 entries
        if (count($logs) > 1000) {
            $logs = array_slice($logs, -1000);
        }
        
        update_option('png_security_logs', $logs);
        
        do_action('png_security_event', $log_entry);
    }
}