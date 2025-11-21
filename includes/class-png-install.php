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
        self::create_default_categories();
        
        update_option('png_db_version', PNG()->get_db_version());
        update_option('png_install_date', current_time('mysql'));
    }
    
    private static function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $tables = array();
        
        // User profiles table
        $tables[] = "CREATE TABLE {$wpdb->prefix}png_user_profiles (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            phone varchar(20),
            bio text,
            location varchar(100),
            birth_date date,
            gender varchar(20),
            interests text,
            languages varchar(200),
            avatar_url varchar(255),
            cover_url varchar(255),
            is_verified tinyint(1) DEFAULT 0,
            verification_date datetime,
            subscription_type varchar(50) DEFAULT 'free',
            subscription_expires datetime,
            total_earnings decimal(10,2) DEFAULT 0,
            avg_rating decimal(3,2) DEFAULT 0,
            total_reviews int(11) DEFAULT 0,
            level int(11) DEFAULT 1,
            points int(11) DEFAULT 0,
            status varchar(20) DEFAULT 'active',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY user_id (user_id),
            KEY is_verified (is_verified),
            KEY subscription_type (subscription_type),
            KEY status (status)
        ) $charset_collate;";
        
        // Listings table
        $tables[] = "CREATE TABLE {$wpdb->prefix}png_listings (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            title varchar(200) NOT NULL,
            description text NOT NULL,
            price decimal(10,2) DEFAULT 0,
            category varchar(50) NOT NULL,
            subcategory varchar(50),
            meeting_type varchar(50) NOT NULL,
            location varchar(100),
            availability text,
            age_min int(11),
            age_max int(11),
            gender_preference varchar(20),
            status varchar(20) DEFAULT 'pending',
            views int(11) DEFAULT 0,
            favorites int(11) DEFAULT 0,
            featured tinyint(1) DEFAULT 0,
            featured_until datetime,
            verified tinyint(1) DEFAULT 0,
            boost_level int(11) DEFAULT 0,
            boost_until datetime,
            expires_at datetime,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY status (status),
            KEY category (category),
            KEY featured (featured),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        // Messages table
        $tables[] = "CREATE TABLE {$wpdb->prefix}png_messages (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            conversation_id varchar(32) NOT NULL,
            sender_id bigint(20) NOT NULL,
            receiver_id bigint(20) NOT NULL,
            listing_id bigint(20),
            message text NOT NULL,
            attachment_url varchar(255),
            is_read tinyint(1) DEFAULT 0,
            read_at datetime,
            is_deleted_sender tinyint(1) DEFAULT 0,
            is_deleted_receiver tinyint(1) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY conversation_id (conversation_id),
            KEY sender_id (sender_id),
            KEY receiver_id (receiver_id),
            KEY is_read (is_read),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        // Reviews table
        $tables[] = "CREATE TABLE {$wpdb->prefix}png_reviews (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            listing_id bigint(20) NOT NULL,
            reviewer_id bigint(20) NOT NULL,
            reviewee_id bigint(20) NOT NULL,
            rating tinyint(1) NOT NULL,
            comment text,
            pros text,
            cons text,
            would_recommend tinyint(1) DEFAULT 1,
            helpful_count int(11) DEFAULT 0,
            status varchar(20) DEFAULT 'approved',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY listing_id (listing_id),
            KEY reviewer_id (reviewer_id),
            KEY reviewee_id (reviewee_id),
            KEY status (status),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        // Payments table
        $tables[] = "CREATE TABLE {$wpdb->prefix}png_payments (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            listing_id bigint(20),
            payment_type varchar(50) NOT NULL,
            amount decimal(10,2) NOT NULL,
            currency varchar(3) DEFAULT 'PLN',
            status varchar(20) DEFAULT 'pending',
            payment_method varchar(50),
            transaction_id varchar(255),
            gateway varchar(50),
            gateway_response text,
            refunded tinyint(1) DEFAULT 0,
            refund_amount decimal(10,2),
            metadata text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY listing_id (listing_id),
            KEY status (status),
            KEY transaction_id (transaction_id)
        ) $charset_collate;";
        
        // Reports/Moderation table
        $tables[] = "CREATE TABLE {$wpdb->prefix}png_reports (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            reporter_id bigint(20) NOT NULL,
            reported_type varchar(50) NOT NULL,
            reported_id bigint(20) NOT NULL,
            reason varchar(100) NOT NULL,
            description text,
            status varchar(20) DEFAULT 'pending',
            moderator_id bigint(20),
            moderator_note text,
            action_taken varchar(100),
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            resolved_at datetime,
            PRIMARY KEY (id),
            KEY reporter_id (reporter_id),
            KEY reported_id (reported_id),
            KEY status (status)
        ) $charset_collate;";
        
        // Notifications table
        $tables[] = "CREATE TABLE {$wpdb->prefix}png_notifications (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            type varchar(50) NOT NULL,
            title varchar(200) NOT NULL,
            content text,
            link varchar(255),
            is_read tinyint(1) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY is_read (is_read),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        // Favorites table
        $tables[] = "CREATE TABLE {$wpdb->prefix}png_favorites (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            listing_id bigint(20) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY user_listing (user_id, listing_id),
            KEY user_id (user_id),
            KEY listing_id (listing_id)
        ) $charset_collate;";
        
        // Statistics table
        $tables[] = "CREATE TABLE {$wpdb->prefix}png_statistics (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            stat_type varchar(50) NOT NULL,
            stat_key varchar(100) NOT NULL,
            stat_value text,
            period varchar(20),
            date date,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY stat_type (stat_type),
            KEY date (date)
        ) $charset_collate;";
        
        // Subscriptions table
        $tables[] = "CREATE TABLE {$wpdb->prefix}png_subscriptions (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            plan_type varchar(50) NOT NULL,
            status varchar(20) DEFAULT 'active',
            price decimal(10,2) NOT NULL,
            billing_cycle varchar(20),
            started_at datetime DEFAULT CURRENT_TIMESTAMP,
            expires_at datetime,
            cancelled_at datetime,
            payment_id bigint(20),
            auto_renew tinyint(1) DEFAULT 1,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY status (status),
            KEY expires_at (expires_at)
        ) $charset_collate;";
        
        // Verification requests table
        $tables[] = "CREATE TABLE {$wpdb->prefix}png_verifications (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            document_type varchar(50) NOT NULL,
            document_url varchar(255),
            selfie_url varchar(255),
            status varchar(20) DEFAULT 'pending',
            admin_note text,
            submitted_at datetime DEFAULT CURRENT_TIMESTAMP,
            reviewed_at datetime,
            reviewed_by bigint(20),
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY status (status)
        ) $charset_collate;";
        
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        
        foreach ($tables as $table) {
            dbDelta($table);
        }
    }
    
    private static function create_pages() {
        $pages = array(
            'moje-ogloszenia' => array(
                'title' => 'Moje Ogłoszenia',
                'content' => '[png_my_listings]'
            ),
            'dodaj-ogloszenie' => array(
                'title' => 'Dodaj Ogłoszenie',
                'content' => '[png_listing_form]'
            ),
            'edytuj-profil' => array(
                'title' => 'Edytuj Profil',
                'content' => '[png_profile_edit]'
            ),
            'moj-profil' => array(
                'title' => 'Mój Profil',
                'content' => '[png_user_profile]'
            ),
            'wiadomosci' => array(
                'title' => 'Wiadomości',
                'content' => '[png_messages]'
            ),
            'znajdz-towarzysza' => array(
                'title' => 'Znajdź Towarzysza',
                'content' => '[png_listings_archive]'
            ),
            'platnosc' => array(
                'title' => 'Płatność',
                'content' => '[png_payment_checkout]'
            ),
            'platnosc-sukces' => array(
                'title' => 'Płatność Zakończona',
                'content' => '[png_payment_success]'
            ),
            'platnosc-anulowana' => array(
                'title' => 'Płatność Anulowana',
                'content' => '[png_payment_cancelled]'
            ),
            'ulubione' => array(
                'title' => 'Ulubione',
                'content' => '[png_favorites]'
            ),
            'statystyki' => array(
                'title' => 'Statystyki',
                'content' => '[png_user_statistics]'
            ),
            'subskrypcje' => array(
                'title' => 'Subskrypcje',
                'content' => '[png_subscriptions]'
            ),
            'weryfikacja' => array(
                'title' => 'Weryfikacja Konta',
                'content' => '[png_verification]'
            )
        );
        
        foreach ($pages as $slug => $page) {
            $existing = get_page_by_path($slug);
            
            if (!$existing) {
                $page_id = wp_insert_post(array(
                    'post_title' => $page['title'],
                    'post_name' => $slug,
                    'post_content' => $page['content'],
                    'post_status' => 'publish',
                    'post_type' => 'page',
                    'post_author' => 1
                ));
                
                update_option('png_page_' . str_replace('-', '_', $slug), $page_id);
            }
        }
    }
    
    private static function create_roles() {
        add_role('png_verified', __('Zweryfikowany Użytkownik', 'png'), array(
            'read' => true,
            'upload_files' => true,
            'edit_posts' => false
        ));
        
        add_role('png_premium', __('Użytkownik Premium', 'png'), array(
            'read' => true,
            'upload_files' => true,
            'edit_posts' => false
        ));
    }
    
    private static function set_default_options() {
        $default_options = array(
            'general' => array(
                'require_verification' => 0,
                'auto_approve_listings' => 0,
                'max_listings_per_user' => 5,
                'max_images_per_listing' => 5,
                'listing_expiry_days' => 30,
                'enable_favorites' => 1,
                'enable_messages' => 1,
                'enable_reviews' => 1
            ),
            'payments' => array(
                'currency' => 'PLN',
                'commission_rate' => 10,
                'featured_price' => 29,
                'verify_price' => 49,
                'boost_price' => 19,
                'premium_monthly' => 99,
                'premium_yearly' => 999,
                'paypal_enabled' => 0,
                'paypal_client_id' => '',
                'paypal_secret' => '',
                'paypal_mode' => 'sandbox',
                'stripe_enabled' => 0,
                'stripe_public_key' => '',
                'stripe_secret_key' => ''
            ),
            'moderation' => array(
                'banned_words' => "seks,erotyka,prostytucja,masaż erotyczny,usługi towarzyskie\nsex,erotic,massage,escort",
                'auto_moderation' => 1,
                'require_approval' => 1,
                'min_age' => 18,
                'max_reports_before_ban' => 3
            ),
            'notifications' => array(
                'email_new_message' => 1,
                'email_new_review' => 1,
                'email_listing_approved' => 1,
                'email_payment_received' => 1,
                'push_enabled' => 0
            ),
            'seo' => array(
                'meta_title' => 'Znajdź Towarzysza - {site_name}',
                'meta_description' => 'Platforma do znajdowania towarzyszy do wspólnych aktywności',
                'og_image' => ''
            )
        );
        
        update_option('png_settings', $default_options);
    }
    
    private static function create_default_categories() {
        $categories = array(
            'sport' => 'Sport i Aktywność Fizyczna',
            'kultura' => 'Kultura i Rozrywka',
            'podroze' => 'Podróże',
            'nauka' => 'Nauka i Rozwój',
            'jedzenie' => 'Jedzenie i Gotowanie',
            'gry' => 'Gry i Rozrywka',
            'zwierzeta' => 'Zwierzęta',
            'inne' => 'Inne'
        );
        
        foreach ($categories as $slug => $name) {
            if (!term_exists($slug, 'listing_category')) {
                wp_insert_term($name, 'listing_category', array('slug' => $slug));
            }
        }
    }
    
    public static function uninstall() {
        global $wpdb;
        
        // Delete options
        delete_option('png_settings');
        delete_option('png_db_version');
        delete_option('png_install_date');
        
        // Delete page options
        $pages = array('moje_ogloszenia', 'dodaj_ogloszenie', 'edytuj_profil', 'moj_profil', 
                      'wiadomosci', 'znajdz_towarzysza', 'platnosc', 'platnosc_sukces', 
                      'platnosc_anulowana', 'ulubione', 'statystyki', 'subskrypcje', 'weryfikacja');
        
        foreach ($pages as $page) {
            delete_option('png_page_' . $page);
        }
        
        // Remove roles
        remove_role('png_verified');
        remove_role('png_premium');
        
        // Optionally drop tables (uncomment to enable)
        /*
        $tables = array(
            'png_user_profiles', 'png_listings', 'png_messages', 'png_reviews',
            'png_payments', 'png_reports', 'png_notifications', 'png_favorites',
            'png_statistics', 'png_subscriptions', 'png_verifications'
        );
        
        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}{$table}");
        }
        */
    }
}