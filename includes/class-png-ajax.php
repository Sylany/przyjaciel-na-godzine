<?php
if (!defined('ABSPATH')) {
    exit;
}

class PNG_Ajax {
    
    public static function init() {
        // Public AJAX actions
        $public_actions = array(
            'png_load_listings',
            'png_search_listings',
            'png_view_listing'
        );
        
        foreach ($public_actions as $action) {
            add_action('wp_ajax_' . $action, array(__CLASS__, str_replace('png_', '', $action)));
            add_action('wp_ajax_nopriv_' . $action, array(__CLASS__, str_replace('png_', '', $action)));
        }
        
        // Logged-in only actions
        $private_actions = array(
            'png_save_listing',
            'png_delete_listing',
            'png_toggle_favorite',
            'png_send_message',
            'png_load_messages',
            'png_mark_read',
            'png_update_profile',
            'png_upload_image',
            'png_delete_image',
            'png_submit_review',
            'png_report_content',
            'png_load_notifications',
            'png_mark_notification_read',
            'png_boost_listing',
            'png_feature_listing',
            'png_verify_account',
            'png_check_username'
        );
        
        foreach ($private_actions as $action) {
            add_action('wp_ajax_' . $action, array(__CLASS__, str_replace('png_', '', $action)));
        }
    }
    
    // Load listings with pagination and filters
    public static function load_listings() {
        $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
        $per_page = isset($_POST['per_page']) ? intval($_POST['per_page']) : 12;
        $category = isset($_POST['category']) ? sanitize_text_field($_POST['category']) : '';
        $search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
        
        $args = array(
            'post_type' => 'listing',
            'posts_per_page' => $per_page,
            'paged' => $page,
            'post_status' => 'publish',
            'orderby' => 'date',
            'order' => 'DESC'
        );
        
        if (!empty($category)) {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'listing_category',
                    'field' => 'slug',
                    'terms' => $category
                )
            );
        }
        
        if (!empty($search)) {
            $args['s'] = $search;
        }
        
        $query = new WP_Query($args);
        
        $listings = array();
        
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $listing_id = get_the_ID();
                
                $listings[] = array(
                    'id' => $listing_id,
                    'title' => get_the_title(),
                    'excerpt' => get_the_excerpt(),
                    'price' => get_post_meta($listing_id, '_price', true),
                    'location' => get_post_meta($listing_id, '_location', true),
                    'featured' => get_post_meta($listing_id, '_featured', true),
                    'verified' => get_post_meta($listing_id, '_verified', true),
                    'thumbnail' => get_the_post_thumbnail_url($listing_id, 'medium'),
                    'permalink' => get_permalink($listing_id),
                    'author' => array(
                        'name' => get_the_author(),
                        'avatar' => get_avatar_url(get_the_author_meta('ID'))
                    )
                );
            }
        }
        
        wp_reset_postdata();
        
        wp_send_json_success(array(
            'listings' => $listings,
            'total' => $query->found_posts,
            'pages' => $query->max_num_pages
        ));
    }
    
    // Search listings
    public static function search_listings() {
        check_ajax_referer('png_nonce', 'nonce');
        
        $search = sanitize_text_field($_POST['search'] ?? '');
        $category = sanitize_text_field($_POST['category'] ?? '');
        $location = sanitize_text_field($_POST['location'] ?? '');
        $price_min = floatval($_POST['price_min'] ?? 0);
        $price_max = floatval($_POST['price_max'] ?? 999999);
        
        global $wpdb;
        
        $sql = "SELECT p.ID, p.post_title, p.post_excerpt 
                FROM {$wpdb->posts} p
                INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
                WHERE p.post_type = 'listing' 
                AND p.post_status = 'publish'";
        
        if (!empty($search)) {
            $sql .= $wpdb->prepare(" AND (p.post_title LIKE %s OR p.post_content LIKE %s)", 
                '%' . $wpdb->esc_like($search) . '%', 
                '%' . $wpdb->esc_like($search) . '%');
        }
        
        $sql .= " GROUP BY p.ID LIMIT 20";
        
        $results = $wpdb->get_results($sql);
        
        wp_send_json_success($results);
    }
    
    // View listing (increment view count)
    public static function view_listing() {
        $listing_id = intval($_POST['listing_id'] ?? 0);
        
        if ($listing_id) {
            PNG_Statistics::increment_view($listing_id);
            wp_send_json_success();
        }
        
        wp_send_json_error();
    }
    
    // Save/Update listing
    public static function save_listing() {
        check_ajax_referer('png_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('Musisz być zalogowany.', 'png')));
        }
        
        $listing_id = intval($_POST['listing_id'] ?? 0);
        $user_id = get_current_user_id();
        
        // Verify ownership for updates
        if ($listing_id && get_post_field('post_author', $listing_id) != $user_id) {
            wp_send_json_error(array('message' => __('Brak uprawnień.', 'png')));
        }
        
        // Validate data
        $title = sanitize_text_field($_POST['title'] ?? '');
        $description = wp_kses_post($_POST['description'] ?? '');
        $category = sanitize_text_field($_POST['category'] ?? '');
        $price = floatval($_POST['price'] ?? 0);
        $location = sanitize_text_field($_POST['location'] ?? '');
        $meeting_type = sanitize_text_field($_POST['meeting_type'] ?? '');
        
        if (empty($title) || empty($description) || empty($category)) {
            wp_send_json_error(array('message' => __('Wypełnij wszystkie wymagane pola.', 'png')));
        }
        
        // Check moderation
        if (PNG_Moderation::contains_banned_words($title . ' ' . $description)) {
            wp_send_json_error(array('message' => __('Treść zawiera niedozwolone słowa.', 'png')));
        }
        
        $post_data = array(
            'post_title' => $title,
            'post_content' => $description,
            'post_type' => 'listing',
            'post_status' => 'pending',
            'post_author' => $user_id
        );
        
        if ($listing_id) {
            $post_data['ID'] = $listing_id;
            $result = wp_update_post($post_data);
        } else {
            $result = wp_insert_post($post_data);
        }
        
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }
        
        // Save meta data
        update_post_meta($result, '_price', $price);
        update_post_meta($result, '_location', $location);
        update_post_meta($result, '_meeting_type', $meeting_type);
        update_post_meta($result, '_category', $category);
        
        // Set category taxonomy
        wp_set_object_terms($result, $category, 'listing_category');
        
        PNG_Notifications::create(
            $user_id,
            'listing_created',
            __('Ogłoszenie utworzone', 'png'),
            __('Twoje ogłoszenie oczekuje na moderację.', 'png'),
            get_permalink($result)
        );
        
        wp_send_json_success(array(
            'message' => __('Ogłoszenie zapisane!', 'png'),
            'listing_id' => $result,
            'redirect' => get_permalink($result)
        ));
    }
    
    // Delete listing
    public static function delete_listing() {
        check_ajax_referer('png_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('Musisz być zalogowany.', 'png')));
        }
        
        $listing_id = intval($_POST['listing_id'] ?? 0);
        $user_id = get_current_user_id();
        
        if (!$listing_id) {
            wp_send_json_error(array('message' => __('Nieprawidłowe ID.', 'png')));
        }
        
        // Verify ownership
        if (get_post_field('post_author', $listing_id) != $user_id && !current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Brak uprawnień.', 'png')));
        }
        
        wp_delete_post($listing_id, true);
        
        wp_send_json_success(array('message' => __('Ogłoszenie usunięte.', 'png')));
    }
    
    // Toggle favorite
    public static function toggle_favorite() {
        check_ajax_referer('png_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('Musisz być zalogowany.', 'png')));
        }
        
        $listing_id = intval($_POST['listing_id'] ?? 0);
        $user_id = get_current_user_id();
        
        global $wpdb;
        $table = $wpdb->prefix . 'png_favorites';
        
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table WHERE user_id = %d AND listing_id = %d",
            $user_id, $listing_id
        ));
        
        if ($exists) {
            $wpdb->delete($table, array('user_id' => $user_id, 'listing_id' => $listing_id));
            $is_favorite = false;
        } else {
            $wpdb->insert($table, array(
                'user_id' => $user_id,
                'listing_id' => $listing_id,
                'created_at' => current_time('mysql')
            ));
            $is_favorite = true;
        }
        
        wp_send_json_success(array('is_favorite' => $is_favorite));
    }
    
    // Send message
    public static function send_message() {
        check_ajax_referer('png_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('Musisz być zalogowany.', 'png')));
        }
        
        $sender_id = get_current_user_id();
        $receiver_id = intval($_POST['receiver_id'] ?? 0);
        $message = sanitize_textarea_field($_POST['message'] ?? '');
        $listing_id = intval($_POST['listing_id'] ?? 0);
        
        if (empty($message) || !$receiver_id) {
            wp_send_json_error(array('message' => __('Nieprawidłowe dane.', 'png')));
        }
        
        $result = PNG_Messages::send($sender_id, $receiver_id, $message, $listing_id);
        
        if ($result) {
            PNG_Notifications::create(
                $receiver_id,
                'new_message',
                __('Nowa wiadomość', 'png'),
                __('Masz nową wiadomość.', 'png'),
                home_url('/wiadomosci')
            );
            
            wp_send_json_success(array('message' => __('Wiadomość wysłana.', 'png')));
        }
        
        wp_send_json_error(array('message' => __('Błąd wysyłania.', 'png')));
    }
    
    // Load messages
    public static function load_messages() {
        check_ajax_referer('png_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error();
        }
        
        $user_id = get_current_user_id();
        $conversation_id = sanitize_text_field($_POST['conversation_id'] ?? '');
        
        $messages = PNG_Messages::get_conversation($conversation_id, $user_id);
        
        wp_send_json_success($messages);
    }
    
    // Mark message as read
    public static function mark_read() {
        check_ajax_referer('png_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error();
        }
        
        $message_id = intval($_POST['message_id'] ?? 0);
        PNG_Messages::mark_read($message_id);
        
        wp_send_json_success();
    }
    
    // Update profile
    public static function update_profile() {
        check_ajax_referer('png_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('Musisz być zalogowany.', 'png')));
        }
        
        $user_id = get_current_user_id();
        
        $display_name = sanitize_text_field($_POST['display_name'] ?? '');
        $bio = sanitize_textarea_field($_POST['bio'] ?? '');
        $location = sanitize_text_field($_POST['location'] ?? '');
        $phone = sanitize_text_field($_POST['phone'] ?? '');
        
        if (empty($display_name)) {
            wp_send_json_error(array('message' => __('Imię jest wymagane.', 'png')));
        }
        
        wp_update_user(array(
            'ID' => $user_id,
            'display_name' => $display_name
        ));
        
        global $wpdb;
        $table = $wpdb->prefix . 'png_user_profiles';
        
        $wpdb->replace($table, array(
            'user_id' => $user_id,
            'bio' => $bio,
            'location' => $location,
            'phone' => $phone,
            'updated_at' => current_time('mysql')
        ), array('%d', '%s', '%s', '%s', '%s'));
        
        wp_send_json_success(array('message' => __('Profil zaktualizowany!', 'png')));
    }
    
    // Upload image
    public static function upload_image() {
        check_ajax_referer('png_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('Musisz być zalogowany.', 'png')));
        }
        
        if (empty($_FILES['image'])) {
            wp_send_json_error(array('message' => __('Nie wybrano pliku.', 'png')));
        }
        
        $file = $_FILES['image'];
        $upload = PNG_Images::upload($file);
        
        if (is_wp_error($upload)) {
            wp_send_json_error(array('message' => $upload->get_error_message()));
        }
        
        wp_send_json_success($upload);
    }
    
    // Submit review
    public static function submit_review() {
        check_ajax_referer('png_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('Musisz być zalogowany.', 'png')));
        }
        
        $listing_id = intval($_POST['listing_id'] ?? 0);
        $rating = intval($_POST['rating'] ?? 0);
        $comment = sanitize_textarea_field($_POST['comment'] ?? '');
        
        if (!$listing_id || $rating < 1 || $rating > 5) {
            wp_send_json_error(array('message' => __('Nieprawidłowe dane.', 'png')));
        }
        
        $result = PNG_Reviews::add_review($listing_id, get_current_user_id(), $rating, $comment);
        
        if ($result) {
            wp_send_json_success(array('message' => __('Opinia dodana!', 'png')));
        }
        
        wp_send_json_error(array('message' => __('Już wystawiłeś opinię.', 'png')));
    }
    
    // Check username availability
    public static function check_username() {
        $username = sanitize_user($_POST['username'] ?? '');
        
        if (username_exists($username)) {
            wp_send_json_error(array('message' => __('Nazwa zajęta.', 'png')));
        }
        
        wp_send_json_success(array('message' => __('Nazwa dostępna!', 'png')));
    }
}