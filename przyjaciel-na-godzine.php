<?php
/**
 * Plugin Name: Przyjaciel na Godzinę
 * Description: Profesjonalna platforma do znajdowania towarzyszy do wspólnych aktywności
 * Version: 2.0.0
 * Author: Twój Nazwa
 * Text Domain: png
 * Requires at least: 5.8
 * Requires PHP: 7.4
 */

if (!defined('ABSPATH')) {
    exit;
}

// Definicje stałych
define('PNG_VERSION', '2.0.0');
define('PNG_PLUGIN_URL', plugin_dir_url(__FILE__));
define('PNG_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('PNG_PLUGIN_FILE', __FILE__);

final class PrzyjacielNaGodzine {
    
    private static $instance = null;
    private $db_version = '1.0';
    
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function __construct() {
        add_action('plugins_loaded', array($this, 'init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        register_uninstall_hook(__FILE__, array('PrzyjacielNaGodzine', 'uninstall'));
    }
    
    public function init() {
        // Sprawdź wymagania
        if (!$this->check_requirements()) {
            return;
        }
        
        $this->includes();
        $this->init_hooks();
        
        do_action('png_loaded');
    }
    
    private function check_requirements() {
        $php_version = '7.4';
        $wp_version = '5.8';
        
        if (version_compare(PHP_VERSION, $php_version, '<')) {
            add_action('admin_notices', function() use ($php_version) {
                echo '<div class="error"><p>Wtyczka Przyjaciel na Godzinę wymaga PHP w wersji ' . $php_version . ' lub nowszej. Obecna wersja: ' . PHP_VERSION . '</p></div>';
            });
            return false;
        }
        
        global $wp_version;
        if (version_compare($wp_version, $wp_version, '<')) {
            add_action('admin_notices', function() use ($wp_version) {
                echo '<div class="error"><p>Wtyczka Przyjaciel na Godzinę wymaga WordPress w wersji ' . $wp_version . ' lub nowszej.</p></div>';
            });
            return false;
        }
        
        return true;
    }
    
    private function includes() {
        // Główne klasy
        require_once PNG_PLUGIN_PATH . 'includes/class-png-install.php';
        require_once PNG_PLUGIN_PATH . 'includes/class-png-post-types.php';
        require_once PNG_PLUGIN_PATH . 'includes/class-png-shortcodes.php';
        require_once PNG_PLUGIN_PATH . 'includes/class-png-ajax.php';
        
        // Moduły funkcjonalne
        require_once PNG_PLUGIN_PATH . 'includes/class-png-users.php';
        require_once PNG_PLUGIN_PATH . 'includes/class-png-listings.php';
        require_once PNG_PLUGIN_PATH . 'includes/class-png-messages.php';
        require_once PNG_PLUGIN_PATH . 'includes/class-png-payments.php';
        require_once PNG_PLUGIN_PATH . 'includes/class-png-reviews.php';
        require_once PNG_PLUGIN_PATH . 'includes/class-png-images.php';
        
        // Admin
        if (is_admin()) {
            require_once PNG_PLUGIN_PATH . 'includes/admin/class-png-admin.php';
            require_once PNG_PLUGIN_PATH . 'includes/admin/class-png-settings.php';
            require_once PNG_PLUGIN_PATH . 'includes/admin/class-png-moderation.php';
        }
    }
    
    private function init_hooks() {
        add_action('init', array($this, 'load_textdomain'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
        
        // Inicjalizacja komponentów
        PNG_Post_Types::init();
        PNG_Shortcodes::init();
        PNG_Ajax::init();
        
        if (is_admin()) {
            PNG_Admin::init();
        }
    }
    
    public function load_textdomain() {
        load_plugin_textdomain('png', false, dirname(plugin_basename(__FILE__)) . '/languages/');
    }
    
    public function enqueue_scripts() {
        // Style
        wp_enqueue_style('png-frontend', PNG_PLUGIN_URL . 'assets/css/frontend.css', array(), PNG_VERSION);
        
        // Skrypty
        wp_enqueue_script('png-frontend', PNG_PLUGIN_URL . 'assets/js/frontend.js', array('jquery'), PNG_VERSION, true);
        wp_enqueue_script('png-image-upload', PNG_PLUGIN_URL . 'assets/js/image-upload.js', array('jquery', 'wp-media'), PNG_VERSION, true);
        
        // Lokalizacja
        wp_localize_script('png-frontend', 'png_ajax', array(
            'url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('png_nonce'),
            'loading' => __('Ładowanie...', 'png'),
            'error' => __('Wystąpił błąd', 'png')
        ));
    }
    
    public function admin_enqueue_scripts($hook) {
        if (strpos($hook, 'przyjaciel-na-godzine') !== false) {
            wp_enqueue_style('png-admin', PNG_PLUGIN_URL . 'assets/css/admin.css', array(), PNG_VERSION);
            wp_enqueue_script('png-admin', PNG_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), PNG_VERSION, true);
        }
    }
    
    public function activate() {
        PNG_Install::install();
        flush_rewrite_rules();
    }
    
    public function deactivate() {
        flush_rewrite_rules();
    }
    
    public static function uninstall() {
        PNG_Install::uninstall();
    }
}

function PNG() {
    return PrzyjacielNaGodzine::instance();
}

// Inicjalizacja
PNG();