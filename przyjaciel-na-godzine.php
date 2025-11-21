<?php
/**
 * Plugin Name: Przyjaciel na Godzinę PRO
 * Description: Profesjonalna platforma do znajdowania towarzyszy do wspólnych aktywności z funkcjami PRO
 * Version: 3.0.0
 * Author: Twoja Nazwa
 * Text Domain: png
 * Requires at least: 5.8
 * Requires PHP: 7.4
 */

if (!defined('ABSPATH')) {
    exit;
}

// Definicje stałych
define('PNG_VERSION', '3.0.0');
define('PNG_PLUGIN_URL', plugin_dir_url(__FILE__));
define('PNG_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('PNG_PLUGIN_FILE', __FILE__);

final class PrzyjacielNaGodzine {
    
    private static $instance = null;
    private $db_version = '3.0';
    
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('plugins_loaded', array($this, 'init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    public function init() {
        if (!$this->check_requirements()) {
            return;
        }
        
        $this->includes();
        $this->init_hooks();
        $this->init_components();
        
        do_action('png_loaded');
    }
    
    private function check_requirements() {
        $php_version = '7.4';
        $wp_version = '5.8';
        
        if (version_compare(PHP_VERSION, $php_version, '<')) {
            add_action('admin_notices', function() use ($php_version) {
                echo '<div class="error"><p>Wtyczka Przyjaciel na Godzinę wymaga PHP w wersji ' . esc_html($php_version) . ' lub nowszej. Obecna wersja: ' . esc_html(PHP_VERSION) . '</p></div>';
            });
            return false;
        }
        
        global $wp_version;
        if (version_compare($wp_version, $wp_version, '<')) {
            add_action('admin_notices', function() use ($wp_version) {
                echo '<div class="error"><p>Wtyczka Przyjaciel na Godzinę wymaga WordPress w wersji ' . esc_html($wp_version) . ' lub nowszej.</p></div>';
            });
            return false;
        }
        
        return true;
    }
    
    private function includes() {
        // Core classes
        require_once PNG_PLUGIN_PATH . 'includes/class-png-install.php';
        require_once PNG_PLUGIN_PATH . 'includes/class-png-post-types.php';
        require_once PNG_PLUGIN_PATH . 'includes/class-png-shortcodes.php';
        require_once PNG_PLUGIN_PATH . 'includes/class-png-ajax.php';
        require_once PNG_PLUGIN_PATH . 'includes/class-png-security.php';
        
        // Feature modules
        require_once PNG_PLUGIN_PATH . 'includes/class-png-users.php';
        require_once PNG_PLUGIN_PATH . 'includes/class-png-listings.php';
        require_once PNG_PLUGIN_PATH . 'includes/class-png-messages.php';
        require_once PNG_PLUGIN_PATH . 'includes/class-png-payments.php';
        require_once PNG_PLUGIN_PATH . 'includes/class-png-reviews.php';
        require_once PNG_PLUGIN_PATH . 'includes/class-png-images.php';
        require_once PNG_PLUGIN_PATH . 'includes/class-png-notifications.php';
        require_once PNG_PLUGIN_PATH . 'includes/class-png-statistics.php';
        require_once PNG_PLUGIN_PATH . 'includes/class-png-moderation.php';
        
        // PRO features
        require_once PNG_PLUGIN_PATH . 'includes/pro/class-png-subscriptions.php';
        require_once PNG_PLUGIN_PATH . 'includes/pro/class-png-verification.php';
        require_once PNG_PLUGIN_PATH . 'includes/pro/class-png-analytics.php';
        require_once PNG_PLUGIN_PATH . 'includes/pro/class-png-calendar.php';
        
        // Admin
        if (is_admin()) {
            require_once PNG_PLUGIN_PATH . 'includes/admin/class-png-admin.php';
            require_once PNG_PLUGIN_PATH . 'includes/admin/class-png-settings.php';
            require_once PNG_PLUGIN_PATH . 'includes/admin/class-png-reports.php';
        }
    }
    
    private function init_hooks() {
        add_action('init', array($this, 'load_textdomain'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
        
        // CRON jobs
        add_action('png_daily_maintenance', array($this, 'daily_maintenance'));
        add_action('png_hourly_stats', array($this, 'update_statistics'));
    }
    
    private function init_components() {
        PNG_Post_Types::init();
        PNG_Shortcodes::init();
        PNG_Ajax::init();
        PNG_Security::init();
        PNG_Notifications::init();
        
        // PRO components
        PNG_Subscriptions::init();
        PNG_Verification::init();
        PNG_Analytics::init();
        PNG_Calendar::init();
        
        if (is_admin()) {
            PNG_Admin::init();
            PNG_Settings::init();
        }
    }
    
    public function load_textdomain() {
        load_plugin_textdomain('png', false, dirname(plugin_basename(__FILE__)) . '/languages/');
    }
    
    public function enqueue_scripts() {
        // Styles
        wp_enqueue_style('png-frontend', PNG_PLUGIN_URL . 'assets/css/frontend.css', array(), PNG_VERSION);
        wp_enqueue_style('png-icons', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css', array(), '6.4.0');
        
        // Scripts
        wp_enqueue_script('png-frontend', PNG_PLUGIN_URL . 'assets/js/frontend.js', array('jquery'), PNG_VERSION, true);
        wp_enqueue_script('png-image-upload', PNG_PLUGIN_URL . 'assets/js/image-upload.js', array('jquery', 'wp-media'), PNG_VERSION, true);
        wp_enqueue_script('png-messages', PNG_PLUGIN_URL . 'assets/js/messages.js', array('jquery'), PNG_VERSION, true);
        
        // Localization
        wp_localize_script('png-frontend', 'pngData', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('png_nonce'),
            'currentUserId' => get_current_user_id(),
            'strings' => array(
                'loading' => __('Ładowanie...', 'png'),
                'error' => __('Wystąpił błąd', 'png'),
                'success' => __('Sukces!', 'png'),
                'confirm' => __('Czy na pewno?', 'png'),
                'delete' => __('Usuń', 'png'),
                'edit' => __('Edytuj', 'png'),
                'save' => __('Zapisz', 'png'),
                'cancel' => __('Anuluj', 'png')
            )
        ));
    }
    
    public function admin_enqueue_scripts($hook) {
        if (strpos($hook, 'przyjaciel-na-godzine') !== false) {
            wp_enqueue_style('png-admin', PNG_PLUGIN_URL . 'assets/css/admin.css', array(), PNG_VERSION);
            wp_enqueue_script('png-admin', PNG_PLUGIN_URL . 'assets/js/admin.js', array('jquery', 'jquery-ui-sortable'), PNG_VERSION, true);
            wp_enqueue_script('chart-js', 'https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js', array(), '3.9.1', true);
        }
    }
    
    public function activate() {
        PNG_Install::install();
        flush_rewrite_rules();
        
        // Schedule CRON jobs
        if (!wp_next_scheduled('png_daily_maintenance')) {
            wp_schedule_event(time(), 'daily', 'png_daily_maintenance');
        }
        if (!wp_next_scheduled('png_hourly_stats')) {
            wp_schedule_event(time(), 'hourly', 'png_hourly_stats');
        }
    }
    
    public function deactivate() {
        flush_rewrite_rules();
        wp_clear_scheduled_hook('png_daily_maintenance');
        wp_clear_scheduled_hook('png_hourly_stats');
    }
    
    public function daily_maintenance() {
        // Clean old messages
        PNG_Messages::cleanup_old_messages();
        
        // Update statistics
        PNG_Statistics::update_daily_stats();
        
        // Check expired subscriptions
        PNG_Subscriptions::check_expired_subscriptions();
        
        // Clean temporary files
        $this->cleanup_temp_files();
    }
    
    public function update_statistics() {
        PNG_Statistics::update_hourly_stats();
    }
    
    private function cleanup_temp_files() {
        $upload_dir = wp_upload_dir();
        $temp_dir = $upload_dir['basedir'] . '/png-temp/';
        
        if (is_dir($temp_dir)) {
            $files = glob($temp_dir . '*');
            $now = time();
            
            foreach ($files as $file) {
                if (is_file($file) && ($now - filemtime($file) >= 24 * 3600)) {
                    @unlink($file);
                }
            }
        }
    }
    
    public function get_db_version() {
        return $this->db_version;
    }
}

function PNG() {
    return PrzyjacielNaGodzine::instance();
}

// Initialize
PNG();