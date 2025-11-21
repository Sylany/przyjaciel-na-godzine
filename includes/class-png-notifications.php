<?php
/**
 * Lokalizacja: /includes/class-png-notifications.php
 */

if (!defined('ABSPATH')) {
    exit;
}

class PNG_Notifications {
    
    public static function init() {
        add_action('wp_ajax_png_get_notifications', array(__CLASS__, 'ajax_get_notifications'));
        add_action('wp_ajax_png_mark_notification_read', array(__CLASS__, 'ajax_mark_read'));
        add_action('wp_ajax_png_mark_all_read', array(__CLASS__, 'ajax_mark_all_read'));
    }
    
    /**
     * Create notification
     */
    public static function create($user_id, $type, $title, $content, $link = '') {
        global $wpdb;
        
        $data = array(
            'user_id' => $user_id,
            'type' => sanitize_text_field($type),
            'title' => sanitize_text_field($title),
            'content' => wp_kses_post($content),
            'link' => esc_url_raw($link),
            'is_read' => 0,
            'created_at' => current_time('mysql')
        );
        
        $result = $wpdb->insert(
            $wpdb->prefix . 'png_notifications',
            $data,
            array('%d', '%s', '%s', '%s', '%s', '%d', '%s')
        );
        
        if ($result) {
            // Send email if enabled
            self::maybe_send_email($user_id, $type, $title, $content, $link);
            
            do_action('png_notification_created', $wpdb->insert_id, $user_id, $type);
        }
        
        return $result ? $wpdb->insert_id : false;
    }
    
    /**
     * Get user notifications
     */
    public static function get($user_id, $limit = 20, $unread_only = false) {
        global $wpdb;
        
        $sql = $wpdb->prepare("
            SELECT * FROM {$wpdb->prefix}png_notifications
            WHERE user_id = %d
        ", $user_id);
        
        if ($unread_only) {
            $sql .= " AND is_read = 0";
        }
        
        $sql .= " ORDER BY created_at DESC LIMIT %d";
        
        return $wpdb->get_results($wpdb->prepare($sql, $limit));
    }
    
    /**
     * Get unread count
     */
    public static function get_unread_count($user_id) {
        global $wpdb;
        
        return (int) $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) FROM {$wpdb->prefix}png_notifications
            WHERE user_id = %d AND is_read = 0
        ", $user_id));
    }
    
    /**
     * Mark as read
     */
    public static function mark_read($notification_id) {
        global $wpdb;
        
        return $wpdb->update(
            $wpdb->prefix . 'png_notifications',
            array('is_read' => 1),
            array('id' => $notification_id),
            array('%d'),
            array('%d')
        );
    }
    
    /**
     * Mark all as read
     */
    public static function mark_all_read($user_id) {
        global $wpdb;
        
        return $wpdb->update(
            $wpdb->prefix . 'png_notifications',
            array('is_read' => 1),
            array('user_id' => $user_id, 'is_read' => 0),
            array('%d'),
            array('%d', '%d')
        );
    }
    
    /**
     * Delete notification
     */
    public static function delete($notification_id) {
        global $wpdb;
        
        return $wpdb->delete(
            $wpdb->prefix . 'png_notifications',
            array('id' => $notification_id),
            array('%d')
        );
    }
    
    /**
     * Delete all notifications
     */
    public static function delete_all($user_id) {
        global $wpdb;
        
        return $wpdb->delete(
            $wpdb->prefix . 'png_notifications',
            array('user_id' => $user_id),
            array('%d')
        );
    }
    
    /**
     * Maybe send email notification
     */
    private static function maybe_send_email($user_id, $type, $title, $content, $link) {
        $settings = get_option('png_settings');
        
        // Check if email notifications are enabled for this type
        $email_key = 'email_' . $type;
        if (!isset($settings['notifications'][$email_key]) || !$settings['notifications'][$email_key]) {
            return false;
        }
        
        $user = get_userdata($user_id);
        if (!$user) {
            return false;
        }
        
        $subject = $title;
        
        $message = sprintf(
            __('Cześć %s,

%s

%s

Możesz sprawdzić szczegóły tutaj: %s

Pozdrawiamy,
Zespół %s

---
Jeśli nie chcesz otrzymywać tego typu powiadomień, możesz je wyłączyć w ustawieniach konta.', 'png'),
            $user->display_name,
            $title,
            $content,
            $link ?: home_url(),
            get_bloginfo('name')
        );
        
        return wp_mail($user->user_email, $subject, $message);
    }
    
    /**
     * Get notification types
     */
    public static function get_types() {
        return array(
            'new_message' => __('Nowa wiadomość', 'png'),
            'new_review' => __('Nowa opinia', 'png'),
            'listing_approved' => __('Ogłoszenie zatwierdzone', 'png'),
            'listing_rejected' => __('Ogłoszenie odrzucone', 'png'),
            'payment_received' => __('Płatność otrzymana', 'png'),
            'subscription_expiring' => __('Wygasająca subskrypcja', 'png'),
            'verification_approved' => __('Weryfikacja zatwierdzona', 'png'),
            'level_up' => __('Awans poziomu', 'png'),
            'favorite_listing' => __('Ktoś dodał do ulubionych', 'png'),
            'system' => __('Powiadomienie systemowe', 'png')
        );
    }
    
    /**
     * AJAX: Get notifications
     */
    public static function ajax_get_notifications() {
        check_ajax_referer('png_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error();
        }
        
        $user_id = get_current_user_id();
        $notifications = self::get($user_id, 20);
        
        wp_send_json_success(array(
            'notifications' => $notifications,
            'unread_count' => self::get_unread_count($user_id)
        ));
    }
    
    /**
     * AJAX: Mark as read
     */
    public static function ajax_mark_read() {
        check_ajax_referer('png_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error();
        }
        
        $notification_id = intval($_POST['notification_id'] ?? 0);
        self::mark_read($notification_id);
        
        wp_send_json_success();
    }
    
    /**
     * AJAX: Mark all as read
     */
    public static function ajax_mark_all_read() {
        check_ajax_referer('png_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error();
        }
        
        $user_id = get_current_user_id();
        self::mark_all_read($user_id);
        
        wp_send_json_success();
    }
    
    /**
     * Cleanup old notifications
     */
    public static function cleanup($days = 30) {
        global $wpdb;
        
        $date = date('Y-m-d H:i:s', strtotime("-$days days"));
        
        return $wpdb->query($wpdb->prepare("
            DELETE FROM {$wpdb->prefix}png_notifications
            WHERE created_at < %s AND is_read = 1
        ", $date));
    }
    
    /**
     * Send bulk notification
     */
    public static function send_bulk($user_ids, $type, $title, $content, $link = '') {
        foreach ($user_ids as $user_id) {
            self::create($user_id, $type, $title, $content, $link);
        }
        
        return count($user_ids);
    }
    
    /**
     * Send notification to all users
     */
    public static function send_to_all($type, $title, $content, $link = '') {
        $users = get_users(array('fields' => 'ID'));
        return self::send_bulk($users, $type, $title, $content, $link);
    }
}