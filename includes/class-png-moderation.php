<?php
/**
 * Lokalizacja: /includes/class-png-moderation.php
 */

if (!defined('ABSPATH')) {
    exit;
}

class PNG_Moderation {
    
    /**
     * Check if content contains banned words
     */
    public static function contains_banned_words($content) {
        $settings = get_option('png_settings');
        $banned_words = $settings['moderation']['banned_words'] ?? '';
        
        if (empty($banned_words)) {
            return false;
        }
        
        $words = array_map('trim', explode("\n", strtolower($banned_words)));
        $content_lower = strtolower($content);
        
        foreach ($words as $word) {
            if (empty($word)) {
                continue;
            }
            
            if (strpos($content_lower, $word) !== false) {
                self::log_violation('banned_word', $content, $word);
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Auto-moderate content
     */
    public static function auto_moderate($content, $user_id) {
        $settings = get_option('png_settings');
        
        if (!isset($settings['moderation']['auto_moderation']) || !$settings['moderation']['auto_moderation']) {
            return true;
        }
        
        // Check banned words
        if (self::contains_banned_words($content)) {
            return new WP_Error('banned_content', __('Treść zawiera niedozwolone słowa.', 'png'));
        }
        
        // Check spam patterns
        if (self::is_spam($content)) {
            return new WP_Error('spam_detected', __('Wykryto spam.', 'png'));
        }
        
        // Check suspicious links
        if (self::has_suspicious_links($content)) {
            return new WP_Error('suspicious_links', __('Treść zawiera podejrzane linki.', 'png'));
        }
        
        // Check user history
        if (self::is_problematic_user($user_id)) {
            return new WP_Error('problematic_user', __('Twoje konto wymaga dodatkowej weryfikacji.', 'png'));
        }
        
        return true;
    }
    
    /**
     * Check if content is spam
     */
    public static function is_spam($content) {
        $spam_indicators = array(
            // Excessive caps
            'caps_ratio' => 0.5,
            // Excessive links
            'max_links' => 5,
            // Repeated characters
            'repeated_chars' => 5,
            // Short content with link
            'min_length_with_link' => 50
        );
        
        // Check caps ratio
        $caps_count = preg_match_all('/[A-Z]/', $content);
        $total_letters = preg_match_all('/[a-zA-Z]/', $content);
        
        if ($total_letters > 10 && ($caps_count / $total_letters) > $spam_indicators['caps_ratio']) {
            return true;
        }
        
        // Check excessive links
        $link_count = preg_match_all('/(http|https):\/\//', $content);
        if ($link_count > $spam_indicators['max_links']) {
            return true;
        }
        
        // Check repeated characters
        if (preg_match('/(.)\1{' . $spam_indicators['repeated_chars'] . ',}/', $content)) {
            return true;
        }
        
        // Check short content with link
        if ($link_count > 0 && strlen($content) < $spam_indicators['min_length_with_link']) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Check for suspicious links
     */
    private static function has_suspicious_links($content) {
        // Suspicious TLDs
        $suspicious_tlds = array('.ru', '.cn', '.tk', '.ml', '.ga', '.cf');
        
        preg_match_all('/https?:\/\/[^\s]+/', $content, $matches);
        
        foreach ($matches[0] as $url) {
            foreach ($suspicious_tlds as $tld) {
                if (strpos($url, $tld) !== false) {
                    return true;
                }
            }
        }
        
        return false;
    }
    
    /**
     * Check if user is problematic
     */
    private static function is_problematic_user($user_id) {
        global $wpdb;
        
        // Check report count
        $report_count = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) FROM {$wpdb->prefix}png_reports
            WHERE reported_id = %d 
            AND reported_type = 'user'
            AND status = 'confirmed'
        ", $user_id));
        
        $settings = get_option('png_settings');
        $max_reports = $settings['moderation']['max_reports_before_ban'] ?? 3;
        
        if ($report_count >= $max_reports) {
            return true;
        }
        
        // Check if user is banned
        if (PNG_Security::is_user_banned($user_id)) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Report content
     */
    public static function report($reporter_id, $reported_type, $reported_id, $reason, $description = '') {
        global $wpdb;
        
        // Check if already reported
        $existing = $wpdb->get_var($wpdb->prepare("
            SELECT id FROM {$wpdb->prefix}png_reports
            WHERE reporter_id = %d 
            AND reported_type = %s
            AND reported_id = %d
            AND created_at > DATE_SUB(NOW(), INTERVAL 30 DAY)
        ", $reporter_id, $reported_type, $reported_id));
        
        if ($existing) {
            return new WP_Error('already_reported', __('Już zgłosiłeś tę treść.', 'png'));
        }
        
        $result = $wpdb->insert(
            $wpdb->prefix . 'png_reports',
            array(
                'reporter_id' => $reporter_id,
                'reported_type' => $reported_type,
                'reported_id' => $reported_id,
                'reason' => sanitize_text_field($reason),
                'description' => wp_kses_post($description),
                'status' => 'pending',
                'created_at' => current_time('mysql')
            ),
            array('%d', '%s', '%d', '%s', '%s', '%s', '%s')
        );
        
        if ($result) {
            // Notify admins
            self::notify_admins_about_report($wpdb->insert_id);
            
            do_action('png_content_reported', $wpdb->insert_id, $reported_type, $reported_id);
        }
        
        return $result ? $wpdb->insert_id : false;
    }
    
    /**
     * Get reports
     */
    public static function get_reports($status = 'all', $limit = 50) {
        global $wpdb;
        
        $sql = "SELECT r.*, 
                reporter.display_name as reporter_name,
                reported.display_name as reported_name
                FROM {$wpdb->prefix}png_reports r
                LEFT JOIN {$wpdb->users} reporter ON r.reporter_id = reporter.ID
                LEFT JOIN {$wpdb->users} reported ON r.reported_id = reported.ID
                WHERE 1=1";
        
        if ($status !== 'all') {
            $sql .= $wpdb->prepare(" AND r.status = %s", $status);
        }
        
        $sql .= " ORDER BY r.created_at DESC LIMIT %d";
        
        return $wpdb->get_results($wpdb->prepare($sql, $limit));
    }
    
    /**
     * Process report
     */
    public static function process_report($report_id, $action, $moderator_note = '') {
        global $wpdb;
        
        $report = $wpdb->get_row($wpdb->prepare("
            SELECT * FROM {$wpdb->prefix}png_reports WHERE id = %d
        ", $report_id));
        
        if (!$report) {
            return false;
        }
        
        $moderator_id = get_current_user_id();
        
        // Update report
        $wpdb->update(
            $wpdb->prefix . 'png_reports',
            array(
                'status' => $action === 'ban' ? 'confirmed' : 'rejected',
                'moderator_id' => $moderator_id,
                'moderator_note' => sanitize_textarea_field($moderator_note),
                'action_taken' => $action,
                'resolved_at' => current_time('mysql')
            ),
            array('id' => $report_id)
        );
        
        // Take action
        switch ($action) {
            case 'ban':
                PNG_Security::ban_user($report->reported_id, $moderator_note);
                break;
                
            case 'delete_listing':
                if ($report->reported_type === 'listing') {
                    wp_delete_post($report->reported_id, true);
                }
                break;
                
            case 'suspend':
                self::suspend_user($report->reported_id, 7);
                break;
                
            case 'warning':
                self::warn_user($report->reported_id, $moderator_note);
                break;
                
            case 'dismiss':
                // Just mark as resolved
                break;
        }
        
        do_action('png_report_processed', $report_id, $action, $report);
        
        return true;
    }
    
    /**
     * Suspend user temporarily
     */
    private static function suspend_user($user_id, $days = 7) {
        update_user_meta($user_id, '_png_suspended_until', date('Y-m-d H:i:s', strtotime("+$days days")));
        
        PNG_Notifications::create(
            $user_id,
            'system',
            __('Konto zawieszone', 'png'),
            sprintf(__('Twoje konto zostało zawieszone na %d dni.', 'png'), $days)
        );
    }
    
    /**
     * Check if user is suspended
     */
    public static function is_user_suspended($user_id) {
        $suspended_until = get_user_meta($user_id, '_png_suspended_until', true);
        
        if (!$suspended_until) {
            return false;
        }
        
        if (strtotime($suspended_until) > time()) {
            return true;
        }
        
        // Suspension expired, clean up
        delete_user_meta($user_id, '_png_suspended_until');
        return false;
    }
    
    /**
     * Warn user
     */
    private static function warn_user($user_id, $reason) {
        $warnings = get_user_meta($user_id, '_png_warnings', true);
        
        if (!is_array($warnings)) {
            $warnings = array();
        }
        
        $warnings[] = array(
            'reason' => $reason,
            'date' => current_time('mysql')
        );
        
        update_user_meta($user_id, '_png_warnings', $warnings);
        
        PNG_Notifications::create(
            $user_id,
            'system',
            __('Ostrzeżenie', 'png'),
            __('Otrzymałeś ostrzeżenie: ', 'png') . $reason
        );
        
        // Auto-suspend after 3 warnings
        if (count($warnings) >= 3) {
            self::suspend_user($user_id, 7);
        }
    }
    
    /**
     * Log violation
     */
    private static function log_violation($type, $content, $details = '') {
        global $wpdb;
        
        $wpdb->insert(
            $wpdb->prefix . 'png_statistics',
            array(
                'stat_type' => 'moderation_violation',
                'stat_key' => $type,
                'stat_value' => json_encode(array(
                    'content_preview' => substr($content, 0, 100),
                    'details' => $details,
                    'user_id' => get_current_user_id()
                )),
                'created_at' => current_time('mysql')
            )
        );
    }
    
    /**
     * Notify admins about report
     */
    private static function notify_admins_about_report($report_id) {
        $admins = get_users(array('role' => 'administrator'));
        
        foreach ($admins as $admin) {
            PNG_Notifications::create(
                $admin->ID,
                'system',
                __('Nowe zgłoszenie', 'png'),
                __('Nowe zgłoszenie wymaga Twojej uwagi.', 'png'),
                admin_url('admin.php?page=png-reports&report_id=' . $report_id)
            );
        }
    }
    
    /**
     * Get moderation statistics
     */
    public static function get_stats() {
        global $wpdb;
        
        return array(
            'pending_reports' => $wpdb->get_var("
                SELECT COUNT(*) FROM {$wpdb->prefix}png_reports 
                WHERE status = 'pending'
            "),
            'confirmed_reports' => $wpdb->get_var("
                SELECT COUNT(*) FROM {$wpdb->prefix}png_reports 
                WHERE status = 'confirmed'
            "),
            'total_bans' => $wpdb->get_var("
                SELECT COUNT(*) FROM {$wpdb->prefix}png_user_profiles 
                WHERE status = 'banned'
            "),
            'violations_today' => $wpdb->get_var($wpdb->prepare("
                SELECT COUNT(*) FROM {$wpdb->prefix}png_statistics 
                WHERE stat_type = 'moderation_violation'
                AND DATE(created_at) = %s
            ", current_time('Y-m-d')))
        );
    }
    
    /**
     * Clean old reports
     */
    public static function cleanup_old_reports($days = 90) {
        global $wpdb;
        
        $date = date('Y-m-d H:i:s', strtotime("-$days days"));
        
        return $wpdb->query($wpdb->prepare("
            DELETE FROM {$wpdb->prefix}png_reports
            WHERE created_at < %s 
            AND status IN ('resolved', 'rejected')
        ", $date));
    }
}