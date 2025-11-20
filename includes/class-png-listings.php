<?php
if (!defined('ABSPATH')) {
    exit;
}

class PNG_Install {
    
    public static function install() {
        self::create_tables();
        self::create_pages();
        self::create_roles();
        self::set_default_options();
        self::schedule_events();
    }
    
    private static function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $tables = array(
            "CREATE TABLE {$wpdb->prefix}png_listings (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                user_id bigint(20) NOT NULL,
                title varchar(200) NOT NULL,
                description text NOT NULL,
                price decimal(10,2) DEFAULT 0,
                category varchar(50) NOT NULL,
                meeting_type varchar(50) NOT NULL,
                availability varchar(50) NOT NULL,
                status varchar(20) DEFAULT 'pending',
                views int(11) DEFAULT 0,
                featured tinyint(1) DEFAULT 0,
                verified tinyint(1) DEFAULT 0,
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY user_id (user_id),
                KEY status (status),
                KEY category (category)
            ) $charset_collate;",
            
            "CREATE TABLE {$wpdb->prefix}png_messages (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                conversation_id varchar(32) NOT NULL,
                sender_id bigint(20) NOT NULL,
                receiver_id bigint(20) NOT NULL,
                listing_id bigint(20),
                message text NOT NULL,
                is_read tinyint(1) DEFAULT 0,
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY conversation_id (conversation_id),
                KEY sender_id (sender_id),
                KEY receiver_id (receiver_id),
                KEY is_read (is_read)
            ) $charset_collate;",
            
            "CREATE TABLE {$wpdb->prefix}png_reviews (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                listing_id bigint(20) NOT NULL,
                reviewer_id bigint(20) NOT NULL,
                reviewee_id bigint(20) NOT NULL,
                rating tinyint(1) NOT NULL,
                comment text,
                status varchar(20) DEFAULT 'approved',
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY listing_id (listing_id),
                KEY reviewer_id (reviewer_id),
                KEY reviewee_id (reviewee_id),
                KEY status (status)
            ) $charset_collate;",
            
            "CREATE TABLE {$wpdb->prefix}png_payments (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                user_id bigint(20) NOT NULL,
                listing_id bigint(20),
                payment_type varchar(50) NOT NULL,
                amount decimal(10,2) NOT NULL,
                currency varchar(3) DEFAULT 'PLN',
                status varchar(20) DEFAULT 'pending',
                payment_method varchar(50),
                transaction_id varchar(100),
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY user_id (user_id),
                KEY listing_id (listing_id),
                KEY status (status)
            ) $charset_collate;",
            
            "CREATE TABLE {$wpdb->prefix}png_reports (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                reporter_id bigint(20) NOT NULL,
                reported_id bigint(20) NOT NULL,
                report_type varchar(50) NOT NULL,
                reason text NOT NULL,
                status varchar(20) DEFAULT 'pending',
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY reporter_id (reporter_id),
                KEY reported_id (reported_id),
                KEY status (status)
            ) $charset_collate;",
            
            "CREATE TABLE {$wpdb->prefix}png_user_meta (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                user_id bigint(20) NOT NULL,
                meta_key varchar(255) NOT NULL,
                meta_value longtext,
                PRIMARY KEY (id),
                KEY user_id (user_id),
                KEY meta_key (meta_key)
            ) $charset_collate;"
        );
        
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        
        foreach ($tables as $table) {
            dbDelta($table);
        }
        
        update_option('png_db_version', PNG()->db_version);
    }
    
    private static function create_pages() {
        $pages = array(
            'moje-ogloszenia' => array(
                'title' => 'Moje Ogłoszenia',
                'content' => '[png_my_listings]',
                'template' => ''
            ),
            'dodaj-ogloszenie' => array(
                'title' => 'Dodaj Ogłoszenie',
                'content' => '[png_listing_form]',
                'template' => ''
            ),
            'edytuj-profil' => array(
                'title' => 'Edytuj Profil',
                'content' => '[png_profile_edit]',
                'template' => ''
            ),
            'moj-profil' => array(
                'title' => 'Mój Profil',
                'content' => '[png_user_profile]',
                'template' => ''
            ),
            'wiadomosci' => array(
                'title' => 'Wiadomości',
                'content' => '[png_messages]',
                'template' => ''
            ),
            'znajdz-towarzysza' => array(
                'title' => 'Znajdź Towarzysza',
                'content' => '[png_listings_archive]',
                'template' => ''
            )
        );
        
        foreach ($pages as $slug => $page) {
            $existing = get_page_by_path($slug);
            
            if (!$existing) {
                wp_insert_post(array(
                    'post_title' => $page['title'],
                    'post_name' => $slug,
                    'post_content' => $page['content'],
                    'post_status' => 'publish',
                    'post_type' => 'page',
                    'post_author' => 1,
                    'page_template' => $page['template']
                ));
            }
        }
    }
    
    private static function create_roles() {
        add_role('png_verified', __('Zweryfikowany użytkownik', 'png'), array(
            'read' => true,
            'upload_files' => true
        ));
    }
    
    private static function set_default_options() {
        $default_options = array(
            'general' => array(
                'require_verification' => 0,
                'auto_approve_listings' => 0,
                'max_listings_per_user' => 5,
                'max_images_per_listing' => 5
            ),
            'payments' => array(
                'currency' => 'PLN',
                'commission_rate' => 10,
                'featured_price' => 29,
                'verify_price' => 49
            ),
            'moderation' => array(
                'banned_words' => "seks,erotyka,prostytucja,masaż erotyczny,usługi towarzyskie\nsex,erotic,massage,escort",
                'auto_moderation' => 1
            )
        );
        
        update_option('png_settings', $default_options);
    }
    
    private static function schedule_events() {
        if (!wp_next_scheduled('png_daily_maintenance')) {
            wp_schedule_event(time(), 'daily', 'png_daily_maintenance');
        }
    }
    
    public static function uninstall() {
        // Usuwanie opcji
        delete_option('png_settings');
        delete_option('png_db_version');
        
        // Usuwanie scheduled events
        wp_clear_scheduled_hook('png_daily_maintenance');
        
        // Usuwanie ról
        remove_role('png_verified');
        
        // Usuwanie tabel (opcjonalnie - zakomentuj jeśli chcesz zachować dane)
        /*
        global $wpdb;
        $tables = array(
            'png_listings',
            'png_messages',
            'png_reviews',
            'png_payments',
            'png_reports',
            'png_user_meta'
        );
        
        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}{$table}");
        }
        */
    }
}