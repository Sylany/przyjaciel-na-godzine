<?php
/**
 * Lokalizacja: /includes/pro/class-png-verification.php
 */

if (!defined('ABSPATH')) {
    exit;
}

class PNG_Verification {
    
    public static function init() {
        add_action('wp_ajax_png_submit_verification', array(__CLASS__, 'ajax_submit'));
    }
    
    /**
     * Submit verification request
     */
    public static function submit($user_id, $document_type, $document_file, $selfie_file) {
        global $wpdb;
        
        // Check if already verified
        $profile = $wpdb->get_row($wpdb->prepare("
            SELECT is_verified FROM {$wpdb->prefix}png_user_profiles WHERE user_id = %d
        ", $user_id));
        
        if ($profile && $profile->is_verified) {
            return new WP_Error('already_verified', __('Jesteś już zweryfikowany.', 'png'));
        }
        
        // Check if pending request exists
        $pending = $wpdb->get_var($wpdb->prepare("
            SELECT id FROM {$wpdb->prefix}png_verifications
            WHERE user_id = %d AND status = 'pending'
        ", $user_id));
        
        if ($pending) {
            return new WP_Error('pending_request', __('Masz już oczekujący wniosek o weryfikację.', 'png'));
        }
        
        // Upload documents
        $document_upload = PNG_Images::upload($document_file, 'verifications');
        if (is_wp_error($document_upload)) {
            return $document_upload;
        }
        
        $selfie_upload = PNG_Images::upload($selfie_file, 'verifications');
        if (is_wp_error($selfie_upload)) {
            return $selfie_upload;
        }
        
        // Create verification request
        $result = $wpdb->insert(
            $wpdb->prefix . 'png_verifications',
            array(
                'user_id' => $user_id,
                'document_type' => sanitize_text_field($document_type),
                'document_url' => $document_upload['url'],
                'selfie_url' => $selfie_upload['url'],
                'status' => 'pending',
                'submitted_at' => current_time('mysql')
            ),
            array('%d', '%s', '%s', '%s', '%s', '%s')
        );
        
        if ($result) {
            // Notify admins
            self::notify_admins($wpdb->insert_id);
            
            // Notify user
            PNG_Notifications::create(
                $user_id,
                'verification_submitted',
                __('Wniosek o weryfikację wysłany', 'png'),
                __('Twój wniosek o weryfikację jest rozpatrywany. Otrzymasz powiadomienie w ciągu 24-48h.', 'png')
            );
            
            do_action('png_verification_submitted', $wpdb->insert_id, $user_id);
        }
        
        return $result ? $wpdb->insert_id : false;
    }
    
    /**
     * Get verification request
     */
    public static function get($verification_id) {
        global $wpdb;
        
        return $wpdb->get_row($wpdb->prepare("
            SELECT v.*, u.display_name as user_name
            FROM {$wpdb->prefix}png_verifications v
            LEFT JOIN {$wpdb->users} u ON v.user_id = u.ID
            WHERE v.id = %d
        ", $verification_id));
    }
    
    /**
     * Get user verification status
     */
    public static function get_user_status($user_id) {
        global $wpdb;
        
        return $wpdb->get_row($wpdb->prepare("
            SELECT * FROM {$wpdb->prefix}png_verifications
            WHERE user_id = %d
            ORDER BY submitted_at DESC
            LIMIT 1
        ", $user_id));
    }
    
    /**
     * Get pending verifications
     */
    public static function get_pending($limit = 50) {
        global $wpdb;
        
        return $wpdb->get_results($wpdb->prepare("
            SELECT v.*, u.display_name as user_name, u.user_email
            FROM {$wpdb->prefix}png_verifications v
            LEFT JOIN {$wpdb->users} u ON v.user_id = u.ID
            WHERE v.status = 'pending'
            ORDER BY v.submitted_at ASC
            LIMIT %d
        ", $limit));
    }
    
    /**
     * Approve verification
     */
    public static function approve($verification_id, $admin_note = '') {
        global $wpdb;
        
        $verification = self::get($verification_id);
        
        if (!$verification) {
            return false;
        }
        
        // Update verification request
        $wpdb->update(
            $wpdb->prefix . 'png_verifications',
            array(
                'status' => 'approved',
                'admin_note' => sanitize_textarea_field($admin_note),
                'reviewed_at' => current_time('mysql'),
                'reviewed_by' => get_current_user_id()
            ),
            array('id' => $verification_id)
        );
        
        // Update user profile
        $wpdb->update(
            $wpdb->prefix . 'png_user_profiles',
            array(
                'is_verified' => 1,
                'verification_date' => current_time('mysql')
            ),
            array('user_id' => $verification->user_id)
        );
        
        // Give bonus points
        PNG_Users::add_points($verification->user_id, 50, 'verification_approved');
        
        // Notify user
        PNG_Notifications::create(
            $verification->user_id,
            'verification_approved',
            __('Weryfikacja zatwierdzona!', 'png'),
            __('Gratulacje! Twoje konto zostało zweryfikowane. Otrzymujesz badge weryfikacji i 50 punktów!', 'png')
        );
        
        // Send email
        self::send_approval_email($verification->user_id);
        
        do_action('png_verification_approved', $verification_id, $verification->user_id);
        
        return true;
    }
    
    /**
     * Reject verification
     */
    public static function reject($verification_id, $reason = '') {
        global $wpdb;
        
        $verification = self::get($verification_id);
        
        if (!$verification) {
            return false;
        }
        
        $wpdb->update(
            $wpdb->prefix . 'png_verifications',
            array(
                'status' => 'rejected',
                'admin_note' => sanitize_textarea_field($reason),
                'reviewed_at' => current_time('mysql'),
                'reviewed_by' => get_current_user_id()
            ),
            array('id' => $verification_id)
        );
        
        PNG_Notifications::create(
            $verification->user_id,
            'verification_rejected',
            __('Weryfikacja odrzucona', 'png'),
            sprintf(__('Niestety, Twój wniosek o weryfikację został odrzucony. Powód: %s', 'png'), $reason)
        );
        
        do_action('png_verification_rejected', $verification_id, $verification->user_id, $reason);
        
        return true;
    }
    
    /**
     * Request additional documents
     */
    public static function request_more_info($verification_id, $message) {
        global $wpdb;
        
        $verification = self::get($verification_id);
        
        if (!$verification) {
            return false;
        }
        
        $wpdb->update(
            $wpdb->prefix . 'png_verifications',
            array(
                'status' => 'needs_info',
                'admin_note' => sanitize_textarea_field($message)
            ),
            array('id' => $verification_id)
        );
        
        PNG_Notifications::create(
            $verification->user_id,
            'verification_info_needed',
            __('Potrzebujemy dodatkowych informacji', 'png'),
            $message,
            home_url('/weryfikacja')
        );
        
        return true;
    }
    
    /**
     * Get verification types
     */
    public static function get_document_types() {
        return array(
            'id_card' => __('Dowód osobisty', 'png'),
            'passport' => __('Paszport', 'png'),
            'drivers_license' => __('Prawo jazdy', 'png'),
            'residence_permit' => __('Karta pobytu', 'png')
        );
    }
    
    /**
     * Notify admins about new verification request
     */
    private static function notify_admins($verification_id) {
        $admins = get_users(array('role' => 'administrator'));
        
        foreach ($admins as $admin) {
            PNG_Notifications::create(
                $admin->ID,
                'system',
                __('Nowy wniosek o weryfikację', 'png'),
                __('Nowy użytkownik wysłał wniosek o weryfikację konta.', 'png'),
                admin_url('admin.php?page=png-verifications&id=' . $verification_id)
            );
        }
    }
    
    /**
     * Send approval email
     */
    private static function send_approval_email($user_id) {
        $user = get_userdata($user_id);
        
        $subject = __('Weryfikacja konta zatwierdzona!', 'png');
        
        $message = sprintf(
            __('Cześć %s,

Świetne wiadomości! Twoje konto zostało zweryfikowane.

Korzyści weryfikacji:
✓ Badge weryfikacji przy Twoim profilu
✓ Większe zaufanie innych użytkowników
✓ Wyższa pozycja w wynikach wyszukiwania
✓ 50 punktów bonusowych

Dziękujemy za weryfikację!

Zespół %s', 'png'),
            $user->display_name,
            get_bloginfo('name')
        );
        
        wp_mail($user->user_email, $subject, $message);
    }
    
    /**
     * Get verification statistics
     */
    public static function get_stats() {
        global $wpdb;
        
        return array(
            'pending' => $wpdb->get_var("
                SELECT COUNT(*) FROM {$wpdb->prefix}png_verifications 
                WHERE status = 'pending'
            "),
            'approved' => $wpdb->get_var("
                SELECT COUNT(*) FROM {$wpdb->prefix}png_verifications 
                WHERE status = 'approved'
            "),
            'rejected' => $wpdb->get_var("
                SELECT COUNT(*) FROM {$wpdb->prefix}png_verifications 
                WHERE status = 'rejected'
            "),
            'total_verified_users' => $wpdb->get_var("
                SELECT COUNT(*) FROM {$wpdb->prefix}png_user_profiles 
                WHERE is_verified = 1
            ")
        );
    }
    
    /**
     * AJAX: Submit verification
     */
    public static function ajax_submit() {
        check_ajax_referer('png_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('Musisz być zalogowany.', 'png')));
        }
        
        $user_id = get_current_user_id();
        $document_type = sanitize_text_field($_POST['document_type'] ?? '');
        
        if (empty($_FILES['document']) || empty($_FILES['selfie'])) {
            wp_send_json_error(array('message' => __('Brak wymaganych plików.', 'png')));
        }
        
        $result = self::submit($user_id, $document_type, $_FILES['document'], $_FILES['selfie']);
        
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }
        
        wp_send_json_success(array('message' => __('Wniosek o weryfikację został wysłany!', 'png')));
    }
    
    /**
     * Revoke verification
     */
    public static function revoke($user_id, $reason = '') {
        global $wpdb;
        
        $wpdb->update(
            $wpdb->prefix . 'png_user_profiles',
            array('is_verified' => 0),
            array('user_id' => $user_id)
        );
        
        PNG_Notifications::create(
            $user_id,
            'verification_revoked',
            __('Weryfikacja cofnięta', 'png'),
            sprintf(__('Twoja weryfikacja została cofnięta. Powód: %s', 'png'), $reason)
        );
        
        do_action('png_verification_revoked', $user_id, $reason);
    }
}