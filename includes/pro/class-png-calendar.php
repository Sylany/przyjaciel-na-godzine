<?php
/**
 * Lokalizacja: /includes/pro/class-png-calendar.php
 */

if (!defined('ABSPATH')) {
    exit;
}

class PNG_Calendar {
    
    public static function init() {
        add_action('wp_ajax_png_get_availability', array(__CLASS__, 'ajax_get_availability'));
        add_action('wp_ajax_png_save_availability', array(__CLASS__, 'ajax_save_availability'));
        add_action('wp_ajax_png_book_slot', array(__CLASS__, 'ajax_book_slot'));
    }
    
    /**
     * Get user availability
     */
    public static function get_availability($user_id, $year = null, $month = null) {
        if (!$year) $year = date('Y');
        if (!$month) $month = date('m');
        
        $availability = get_user_meta($user_id, '_png_availability', true);
        
        if (!is_array($availability)) {
            $availability = self::get_default_availability();
        }
        
        return $availability;
    }
    
    /**
     * Save availability
     */
    public static function save_availability($user_id, $availability_data) {
        $availability = array(
            'weekly_schedule' => self::sanitize_weekly_schedule($availability_data['weekly_schedule'] ?? array()),
            'blocked_dates' => self::sanitize_dates($availability_data['blocked_dates'] ?? array()),
            'special_dates' => self::sanitize_special_dates($availability_data['special_dates'] ?? array()),
            'timezone' => sanitize_text_field($availability_data['timezone'] ?? 'Europe/Warsaw'),
            'booking_buffer' => intval($availability_data['booking_buffer'] ?? 60),
            'max_advance_days' => intval($availability_data['max_advance_days'] ?? 30)
        );
        
        return update_user_meta($user_id, '_png_availability', $availability);
    }
    
    /**
     * Get available time slots for a date
     */
    public static function get_available_slots($user_id, $date, $duration = 60) {
        $availability = self::get_availability($user_id);
        
        // Check if date is blocked
        if (in_array($date, $availability['blocked_dates'])) {
            return array();
        }
        
        $day_of_week = date('l', strtotime($date));
        $day_schedule = $availability['weekly_schedule'][$day_of_week] ?? null;
        
        if (!$day_schedule || !$day_schedule['available']) {
            return array();
        }
        
        // Get existing bookings
        $bookings = self::get_bookings_for_date($user_id, $date);
        
        // Generate time slots
        $slots = array();
        $start_time = strtotime($date . ' ' . $day_schedule['start']);
        $end_time = strtotime($date . ' ' . $day_schedule['end']);
        
        while ($start_time < $end_time) {
            $slot_end = $start_time + ($duration * 60);
            
            // Check if slot is available
            if (!self::is_slot_booked($start_time, $slot_end, $bookings)) {
                $slots[] = array(
                    'start' => date('H:i', $start_time),
                    'end' => date('H:i', $slot_end),
                    'available' => true
                );
            }
            
            $start_time = $slot_end;
        }
        
        return $slots;
    }
    
    /**
     * Book time slot
     */
    public static function book_slot($user_id, $customer_id, $listing_id, $date, $start_time, $end_time, $notes = '') {
        // Validate slot availability
        $slots = self::get_available_slots($user_id, $date);
        $is_available = false;
        
        foreach ($slots as $slot) {
            if ($slot['start'] === $start_time && $slot['available']) {
                $is_available = true;
                break;
            }
        }
        
        if (!$is_available) {
            return new WP_Error('slot_unavailable', __('Ten termin jest już zajęty.', 'png'));
        }
        
        // Create booking
        global $wpdb;
        
        $result = $wpdb->insert(
            $wpdb->prefix . 'png_bookings',
            array(
                'user_id' => $user_id,
                'customer_id' => $customer_id,
                'listing_id' => $listing_id,
                'booking_date' => $date,
                'start_time' => $start_time,
                'end_time' => $end_time,
                'notes' => wp_kses_post($notes),
                'status' => 'pending',
                'created_at' => current_time('mysql')
            ),
            array('%d', '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s')
        );
        
        if ($result) {
            $booking_id = $wpdb->insert_id;
            
            // Notify service provider
            PNG_Notifications::create(
                $user_id,
                'new_booking',
                __('Nowa rezerwacja', 'png'),
                sprintf(__('Masz nową rezerwację na %s o %s', 'png'), $date, $start_time),
                home_url('/kalendarz?booking=' . $booking_id)
            );
            
            // Notify customer
            PNG_Notifications::create(
                $customer_id,
                'booking_created',
                __('Rezerwacja utworzona', 'png'),
                sprintf(__('Twoja rezerwacja na %s o %s została utworzona', 'png'), $date, $start_time)
            );
            
            do_action('png_booking_created', $booking_id, $user_id, $customer_id);
            
            return $booking_id;
        }
        
        return false;
    }
    
    /**
     * Get bookings for date
     */
    private static function get_bookings_for_date($user_id, $date) {
        global $wpdb;
        
        return $wpdb->get_results($wpdb->prepare("
            SELECT * FROM {$wpdb->prefix}png_bookings
            WHERE user_id = %d
            AND booking_date = %s
            AND status NOT IN ('cancelled', 'rejected')
        ", $user_id, $date));
    }
    
    /**
     * Check if slot is booked
     */
    private static function is_slot_booked($start, $end, $bookings) {
        foreach ($bookings as $booking) {
            $booking_start = strtotime($booking->booking_date . ' ' . $booking->start_time);
            $booking_end = strtotime($booking->booking_date . ' ' . $booking->end_time);
            
            // Check overlap
            if (($start >= $booking_start && $start < $booking_end) ||
                ($end > $booking_start && $end <= $booking_end) ||
                ($start <= $booking_start && $end >= $booking_end)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Get default availability
     */
    private static function get_default_availability() {
        return array(
            'weekly_schedule' => array(
                'Monday' => array('available' => true, 'start' => '09:00', 'end' => '17:00'),
                'Tuesday' => array('available' => true, 'start' => '09:00', 'end' => '17:00'),
                'Wednesday' => array('available' => true, 'start' => '09:00', 'end' => '17:00'),
                'Thursday' => array('available' => true, 'start' => '09:00', 'end' => '17:00'),
                'Friday' => array('available' => true, 'start' => '09:00', 'end' => '17:00'),
                'Saturday' => array('available' => false, 'start' => '09:00', 'end' => '17:00'),
                'Sunday' => array('available' => false, 'start' => '09:00', 'end' => '17:00')
            ),
            'blocked_dates' => array(),
            'special_dates' => array(),
            'timezone' => 'Europe/Warsaw',
            'booking_buffer' => 60,
            'max_advance_days' => 30
        );
    }
    
    /**
     * Sanitize weekly schedule
     */
    private static function sanitize_weekly_schedule($schedule) {
        $days = array('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday');
        $sanitized = array();
        
        foreach ($days as $day) {
            if (isset($schedule[$day])) {
                $sanitized[$day] = array(
                    'available' => (bool) ($schedule[$day]['available'] ?? false),
                    'start' => sanitize_text_field($schedule[$day]['start'] ?? '09:00'),
                    'end' => sanitize_text_field($schedule[$day]['end'] ?? '17:00')
                );
            }
        }
        
        return $sanitized;
    }
    
    /**
     * Sanitize dates array
     */
    private static function sanitize_dates($dates) {
        if (!is_array($dates)) {
            return array();
        }
        
        return array_map(function($date) {
            return sanitize_text_field($date);
        }, $dates);
    }
    
    /**
     * Sanitize special dates
     */
    private static function sanitize_special_dates($dates) {
        if (!is_array($dates)) {
            return array();
        }
        
        $sanitized = array();
        
        foreach ($dates as $date => $schedule) {
            $sanitized[sanitize_text_field($date)] = array(
                'start' => sanitize_text_field($schedule['start'] ?? '09:00'),
                'end' => sanitize_text_field($schedule['end'] ?? '17:00')
            );
        }
        
        return $sanitized;
    }
    
    /**
     * Get user bookings
     */
    public static function get_user_bookings($user_id, $status = 'all') {
        global $wpdb;
        
        $sql = $wpdb->prepare("
            SELECT b.*, 
                   u.display_name as customer_name,
                   l.title as listing_title
            FROM {$wpdb->prefix}png_bookings b
            LEFT JOIN {$wpdb->users} u ON b.customer_id = u.ID
            LEFT JOIN {$wpdb->prefix}png_listings l ON b.listing_id = l.id
            WHERE b.user_id = %d
        ", $user_id);
        
        if ($status !== 'all') {
            $sql .= $wpdb->prepare(" AND b.status = %s", $status);
        }
        
        $sql .= " ORDER BY b.booking_date DESC, b.start_time DESC";
        
        return $wpdb->get_results($sql);
    }
    
    /**
     * Update booking status
     */
    public static function update_booking_status($booking_id, $status) {
        global $wpdb;
        
        $result = $wpdb->update(
            $wpdb->prefix . 'png_bookings',
            array('status' => $status),
            array('id' => $booking_id),
            array('%s'),
            array('%d')
        );
        
        if ($result) {
            $booking = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}png_bookings WHERE id = %d",
                $booking_id
            ));
            
            PNG_Notifications::create(
                $booking->customer_id,
                'booking_' . $status,
                sprintf(__('Rezerwacja %s', 'png'), $status),
                sprintf(__('Status Twojej rezerwacji został zmieniony na: %s', 'png'), $status)
            );
            
            do_action('png_booking_status_changed', $booking_id, $status);
        }
        
        return $result;
    }
    
    /**
     * AJAX handlers
     */
    public static function ajax_get_availability() {
        check_ajax_referer('png_nonce', 'nonce');
        
        $user_id = intval($_POST['user_id'] ?? 0);
        $date = sanitize_text_field($_POST['date'] ?? date('Y-m-d'));
        
        $slots = self::get_available_slots($user_id, $date);
        
        wp_send_json_success(array('slots' => $slots));
    }
    
    public static function ajax_save_availability() {
        check_ajax_referer('png_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error();
        }
        
        $user_id = get_current_user_id();
        $availability = $_POST['availability'] ?? array();
        
        self::save_availability($user_id, $availability);
        
        wp_send_json_success(array('message' => __('Dostępność zapisana!', 'png')));
    }
    
    public static function ajax_book_slot() {
        check_ajax_referer('png_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('Musisz być zalogowany.', 'png')));
        }
        
        $user_id = intval($_POST['user_id'] ?? 0);
        $customer_id = get_current_user_id();
        $listing_id = intval($_POST['listing_id'] ?? 0);
        $date = sanitize_text_field($_POST['date'] ?? '');
        $start_time = sanitize_text_field($_POST['start_time'] ?? '');
        $end_time = sanitize_text_field($_POST['end_time'] ?? '');
        $notes = sanitize_textarea_field($_POST['notes'] ?? '');
        
        $result = self::book_slot($user_id, $customer_id, $listing_id, $date, $start_time, $end_time, $notes);
        
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }
        
        wp_send_json_success(array(
            'message' => __('Rezerwacja utworzona!', 'png'),
            'booking_id' => $result
        ));
    }
}