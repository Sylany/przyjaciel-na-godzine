<?php
/**
 * Lokalizacja: /includes/admin/class-png-settings.php
 */

if (!defined('ABSPATH')) {
    exit;
}

class PNG_Settings {
    
    public static function init() {
        add_action('admin_menu', array(__CLASS__, 'add_settings_page'), 20);
        add_action('admin_init', array(__CLASS__, 'register_settings'));
    }
    
    /**
     * Add settings page
     */
    public static function add_settings_page() {
        add_submenu_page(
            'przyjaciel-na-godzine',
            __('Ustawienia', 'png'),
            __('Ustawienia', 'png'),
            'manage_options',
            'png-settings',
            array(__CLASS__, 'render_settings_page')
        );
    }
    
    /**
     * Register settings
     */
    public static function register_settings() {
        register_setting('png_settings_group', 'png_settings', array(__CLASS__, 'sanitize_settings'));
    }
    
    /**
     * Render settings page
     */
    public static function render_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        $settings = get_option('png_settings', self::get_default_settings());
        $active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'general';
        ?>
        
        <div class="wrap">
            <h1><?php _e('Ustawienia Przyjaciel na Godzinę', 'png'); ?></h1>
            
            <?php if (isset($_GET['settings-updated'])): ?>
            <div class="notice notice-success is-dismissible">
                <p><?php _e('Ustawienia zapisane!', 'png'); ?></p>
            </div>
            <?php endif; ?>
            
            <h2 class="nav-tab-wrapper">
                <a href="?page=png-settings&tab=general" class="nav-tab <?php echo $active_tab === 'general' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('Ogólne', 'png'); ?>
                </a>
                <a href="?page=png-settings&tab=payments" class="nav-tab <?php echo $active_tab === 'payments' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('Płatności', 'png'); ?>
                </a>
                <a href="?page=png-settings&tab=moderation" class="nav-tab <?php echo $active_tab === 'moderation' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('Moderacja', 'png'); ?>
                </a>
                <a href="?page=png-settings&tab=notifications" class="nav-tab <?php echo $active_tab === 'notifications' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('Powiadomienia', 'png'); ?>
                </a>
                <a href="?page=png-settings&tab=seo" class="nav-tab <?php echo $active_tab === 'seo' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('SEO', 'png'); ?>
                </a>
            </h2>
            
            <form method="post" action="options.php">
                <?php settings_fields('png_settings_group'); ?>
                
                <?php if ($active_tab === 'general'): ?>
                    <?php self::render_general_settings($settings); ?>
                    
                <?php elseif ($active_tab === 'payments'): ?>
                    <?php self::render_payment_settings($settings); ?>
                    
                <?php elseif ($active_tab === 'moderation'): ?>
                    <?php self::render_moderation_settings($settings); ?>
                    
                <?php elseif ($active_tab === 'notifications'): ?>
                    <?php self::render_notification_settings($settings); ?>
                    
                <?php elseif ($active_tab === 'seo'): ?>
                    <?php self::render_seo_settings($settings); ?>
                <?php endif; ?>
                
                <?php submit_button(); ?>
            </form>
        </div>
        
        <?php
    }
    
    /**
     * Render general settings
     */
    private static function render_general_settings($settings) {
        ?>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label><?php _e('Automatyczne zatwierdzanie ogłoszeń', 'png'); ?></label>
                </th>
                <td>
                    <label>
                        <input type="checkbox" 
                               name="png_settings[general][auto_approve_listings]" 
                               value="1" 
                               <?php checked(1, $settings['general']['auto_approve_listings'] ?? 0); ?>>
                        <?php _e('Publikuj ogłoszenia bez moderacji', 'png'); ?>
                    </label>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label><?php _e('Wymagana weryfikacja email', 'png'); ?></label>
                </th>
                <td>
                    <label>
                        <input type="checkbox" 
                               name="png_settings[general][require_verification]" 
                               value="1" 
                               <?php checked(1, $settings['general']['require_verification'] ?? 0); ?>>
                        <?php _e('Wymagaj weryfikacji email przed publikacją', 'png'); ?>
                    </label>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="max_listings_per_user"><?php _e('Maksymalna liczba ogłoszeń', 'png'); ?></label>
                </th>
                <td>
                    <input type="number" 
                           id="max_listings_per_user" 
                           name="png_settings[general][max_listings_per_user]" 
                           value="<?php echo esc_attr($settings['general']['max_listings_per_user'] ?? 5); ?>" 
                           min="1" 
                           max="100">
                    <p class="description"><?php _e('Liczba ogłoszeń na jednego użytkownika (darmowe konto)', 'png'); ?></p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="max_images_per_listing"><?php _e('Maksymalna liczba zdjęć', 'png'); ?></label>
                </th>
                <td>
                    <input type="number" 
                           id="max_images_per_listing" 
                           name="png_settings[general][max_images_per_listing]" 
                           value="<?php echo esc_attr($settings['general']['max_images_per_listing'] ?? 5); ?>" 
                           min="1" 
                           max="20">
                    <p class="description"><?php _e('Liczba zdjęć na jedno ogłoszenie', 'png'); ?></p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="listing_expiry_days"><?php _e('Wygaśnięcie ogłoszeń (dni)', 'png'); ?></label>
                </th>
                <td>
                    <input type="number" 
                           id="listing_expiry_days" 
                           name="png_settings[general][listing_expiry_days]" 
                           value="<?php echo esc_attr($settings['general']['listing_expiry_days'] ?? 30); ?>" 
                           min="1" 
                           max="365">
                    <p class="description"><?php _e('Po ilu dniach ogłoszenie automatycznie wygasa', 'png'); ?></p>
                </td>
            </tr>
        </table>
        <?php
    }
    
    /**
     * Render payment settings
     */
    private static function render_payment_settings($settings) {
        ?>
        <h2><?php _e('Ceny', 'png'); ?></h2>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="currency"><?php _e('Waluta', 'png'); ?></label>
                </th>
                <td>
                    <select id="currency" name="png_settings[payments][currency]">
                        <option value="PLN" <?php selected('PLN', $settings['payments']['currency'] ?? 'PLN'); ?>>PLN</option>
                        <option value="EUR" <?php selected('EUR', $settings['payments']['currency'] ?? 'PLN'); ?>>EUR</option>
                        <option value="USD" <?php selected('USD', $settings['payments']['currency'] ?? 'PLN'); ?>>USD</option>
                    </select>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="featured_price"><?php _e('Cena wyróżnienia', 'png'); ?></label>
                </th>
                <td>
                    <input type="number" 
                           id="featured_price" 
                           name="png_settings[payments][featured_price]" 
                           value="<?php echo esc_attr($settings['payments']['featured_price'] ?? 29); ?>" 
                           step="0.01" 
                           min="0">
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="boost_price"><?php _e('Cena boost', 'png'); ?></label>
                </th>
                <td>
                    <input type="number" 
                           id="boost_price" 
                           name="png_settings[payments][boost_price]" 
                           value="<?php echo esc_attr($settings['payments']['boost_price'] ?? 19); ?>" 
                           step="0.01" 
                           min="0">
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="verify_price"><?php _e('Cena weryfikacji', 'png'); ?></label>
                </th>
                <td>
                    <input type="number" 
                           id="verify_price" 
                           name="png_settings[payments][verify_price]" 
                           value="<?php echo esc_attr($settings['payments']['verify_price'] ?? 49); ?>" 
                           step="0.01" 
                           min="0">
                </td>
            </tr>
        </table>
        
        <h2><?php _e('PayPal', 'png'); ?></h2>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label><?php _e('PayPal aktywny', 'png'); ?></label>
                </th>
                <td>
                    <label>
                        <input type="checkbox" 
                               name="png_settings[payments][paypal_enabled]" 
                               value="1" 
                               <?php checked(1, $settings['payments']['paypal_enabled'] ?? 0); ?>>
                        <?php _e('Włącz płatności PayPal', 'png'); ?>
                    </label>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="paypal_client_id"><?php _e('PayPal Client ID', 'png'); ?></label>
                </th>
                <td>
                    <input type="text" 
                           id="paypal_client_id" 
                           name="png_settings[payments][paypal_client_id]" 
                           value="<?php echo esc_attr($settings['payments']['paypal_client_id'] ?? ''); ?>" 
                           class="regular-text">
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="paypal_secret"><?php _e('PayPal Secret', 'png'); ?></label>
                </th>
                <td>
                    <input type="password" 
                           id="paypal_secret" 
                           name="png_settings[payments][paypal_secret]" 
                           value="<?php echo esc_attr($settings['payments']['paypal_secret'] ?? ''); ?>" 
                           class="regular-text">
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="paypal_mode"><?php _e('Tryb PayPal', 'png'); ?></label>
                </th>
                <td>
                    <select id="paypal_mode" name="png_settings[payments][paypal_mode]">
                        <option value="sandbox" <?php selected('sandbox', $settings['payments']['paypal_mode'] ?? 'sandbox'); ?>>
                            <?php _e('Sandbox (testowy)', 'png'); ?>
                        </option>
                        <option value="live" <?php selected('live', $settings['payments']['paypal_mode'] ?? 'sandbox'); ?>>
                            <?php _e('Live (produkcyjny)', 'png'); ?>
                        </option>
                    </select>
                </td>
            </tr>
        </table>
        
        <h2><?php _e('Stripe', 'png'); ?></h2>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label><?php _e('Stripe aktywny', 'png'); ?></label>
                </th>
                <td>
                    <label>
                        <input type="checkbox" 
                               name="png_settings[payments][stripe_enabled]" 
                               value="1" 
                               <?php checked(1, $settings['payments']['stripe_enabled'] ?? 0); ?>>
                        <?php _e('Włącz płatności Stripe', 'png'); ?>
                    </label>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="stripe_public_key"><?php _e('Stripe Publishable Key', 'png'); ?></label>
                </th>
                <td>
                    <input type="text" 
                           id="stripe_public_key" 
                           name="png_settings[payments][stripe_public_key]" 
                           value="<?php echo esc_attr($settings['payments']['stripe_public_key'] ?? ''); ?>" 
                           class="regular-text">
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="stripe_secret_key"><?php _e('Stripe Secret Key', 'png'); ?></label>
                </th>
                <td>
                    <input type="password" 
                           id="stripe_secret_key" 
                           name="png_settings[payments][stripe_secret_key]" 
                           value="<?php echo esc_attr($settings['payments']['stripe_secret_key'] ?? ''); ?>" 
                           class="regular-text">
                </td>
            </tr>
        </table>
        <?php
    }
    
    /**
     * Render moderation settings
     */
    private static function render_moderation_settings($settings) {
        ?>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label><?php _e('Automatyczna moderacja', 'png'); ?></label>
                </th>
                <td>
                    <label>
                        <input type="checkbox" 
                               name="png_settings[moderation][auto_moderation]" 
                               value="1" 
                               <?php checked(1, $settings['moderation']['auto_moderation'] ?? 1); ?>>
                        <?php _e('Automatycznie blokuj treści z zakazanymi słowami', 'png'); ?>
                    </label>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="banned_words"><?php _e('Zakazane słowa', 'png'); ?></label>
                </th>
                <td>
                    <textarea id="banned_words" 
                              name="png_settings[moderation][banned_words]" 
                              rows="10" 
                              class="large-text code"><?php echo esc_textarea($settings['moderation']['banned_words'] ?? ''); ?></textarea>
                    <p class="description"><?php _e('Jedno słowo/fraza na linię', 'png'); ?></p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="max_reports_before_ban"><?php _e('Maksymalna liczba zgłoszeń', 'png'); ?></label>
                </th>
                <td>
                    <input type="number" 
                           id="max_reports_before_ban" 
                           name="png_settings[moderation][max_reports_before_ban]" 
                           value="<?php echo esc_attr($settings['moderation']['max_reports_before_ban'] ?? 3); ?>" 
                           min="1" 
                           max="10">
                    <p class="description"><?php _e('Po ilu potwierdzonych zgłoszeniach użytkownik zostanie zablokowany', 'png'); ?></p>
                </td>
            </tr>
        </table>
        <?php
    }
    
    /**
     * Render notification settings
     */
    private static function render_notification_settings($settings) {
        ?>
        <table class="form-table">
            <tr>
                <th scope="row"><?php _e('Powiadomienia email', 'png'); ?></th>
                <td>
                    <fieldset>
                        <label>
                            <input type="checkbox" 
                                   name="png_settings[notifications][email_new_message]" 
                                   value="1" 
                                   <?php checked(1, $settings['notifications']['email_new_message'] ?? 1); ?>>
                            <?php _e('Nowa wiadomość', 'png'); ?>
                        </label><br>
                        
                        <label>
                            <input type="checkbox" 
                                   name="png_settings[notifications][email_new_review]" 
                                   value="1" 
                                   <?php checked(1, $settings['notifications']['email_new_review'] ?? 1); ?>>
                            <?php _e('Nowa opinia', 'png'); ?>
                        </label><br>
                        
                        <label>
                            <input type="checkbox" 
                                   name="png_settings[notifications][email_listing_approved]" 
                                   value="1" 
                                   <?php checked(1, $settings['notifications']['email_listing_approved'] ?? 1); ?>>
                            <?php _e('Ogłoszenie zatwierdzone', 'png'); ?>
                        </label><br>
                        
                        <label>
                            <input type="checkbox" 
                                   name="png_settings[notifications][email_payment_received]" 
                                   value="1" 
                                   <?php checked(1, $settings['notifications']['email_payment_received'] ?? 1); ?>>
                            <?php _e('Płatność otrzymana', 'png'); ?>
                        </label>
                    </fieldset>
                </td>
            </tr>
        </table>
        <?php
    }
    
    /**
     * Render SEO settings
     */
    private static function render_seo_settings($settings) {
        ?>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="meta_title"><?php _e('Meta Title', 'png'); ?></label>
                </th>
                <td>
                    <input type="text" 
                           id="meta_title" 
                           name="png_settings[seo][meta_title]" 
                           value="<?php echo esc_attr($settings['seo']['meta_title'] ?? ''); ?>" 
                           class="regular-text">
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="meta_description"><?php _e('Meta Description', 'png'); ?></label>
                </th>
                <td>
                    <textarea id="meta_description" 
                              name="png_settings[seo][meta_description]" 
                              rows="3" 
                              class="large-text"><?php echo esc_textarea($settings['seo']['meta_description'] ?? ''); ?></textarea>
                </td>
            </tr>
        </table>
        <?php
    }
    
    /**
     * Sanitize settings
     */
    public static function sanitize_settings($input) {
        $sanitized = array();
        
        // Sanitize each section
        if (isset($input['general'])) {
            $sanitized['general'] = self::sanitize_general_settings($input['general']);
        }
        
        if (isset($input['payments'])) {
            $sanitized['payments'] = self::sanitize_payment_settings($input['payments']);
        }
        
        if (isset($input['moderation'])) {
            $sanitized['moderation'] = self::sanitize_moderation_settings($input['moderation']);
        }
        
        if (isset($input['notifications'])) {
            $sanitized['notifications'] = self::sanitize_notification_settings($input['notifications']);
        }
        
        if (isset($input['seo'])) {
            $sanitized['seo'] = self::sanitize_seo_settings($input['seo']);
        }
        
        return array_merge(self::get_default_settings(), $sanitized);
    }
    
    private static function sanitize_general_settings($input) {
        return array(
            'auto_approve_listings' => isset($input['auto_approve_listings']) ? 1 : 0,
            'require_verification' => isset($input['require_verification']) ? 1 : 0,
            'max_listings_per_user' => intval($input['max_listings_per_user'] ?? 5),
            'max_images_per_listing' => intval($input['max_images_per_listing'] ?? 5),
            'listing_expiry_days' => intval($input['listing_expiry_days'] ?? 30)
        );
    }
    
    private static function sanitize_payment_settings($input) {
        return array(
            'currency' => sanitize_text_field($input['currency'] ?? 'PLN'),
            'featured_price' => floatval($input['featured_price'] ?? 29),
            'boost_price' => floatval($input['boost_price'] ?? 19),
            'verify_price' => floatval($input['verify_price'] ?? 49),
            'paypal_enabled' => isset($input['paypal_enabled']) ? 1 : 0,
            'paypal_client_id' => sanitize_text_field($input['paypal_client_id'] ?? ''),
            'paypal_secret' => sanitize_text_field($input['paypal_secret'] ?? ''),
            'paypal_mode' => sanitize_text_field($input['paypal_mode'] ?? 'sandbox'),
            'stripe_enabled' => isset($input['stripe_enabled']) ? 1 : 0,
            'stripe_public_key' => sanitize_text_field($input['stripe_public_key'] ?? ''),
            'stripe_secret_key' => sanitize_text_field($input['stripe_secret_key'] ?? '')
        );
    }
    
    private static function sanitize_moderation_settings($input) {
        return array(
            'auto_moderation' => isset($input['auto_moderation']) ? 1 : 0,
            'banned_words' => sanitize_textarea_field($input['banned_words'] ?? ''),
            'max_reports_before_ban' => intval($input['max_reports_before_ban'] ?? 3)
        );
    }
    
    private static function sanitize_notification_settings($input) {
        return array(
            'email_new_message' => isset($input['email_new_message']) ? 1 : 0,
            'email_new_review' => isset($input['email_new_review']) ? 1 : 0,
            'email_listing_approved' => isset($input['email_listing_approved']) ? 1 : 0,
            'email_payment_received' => isset($input['email_payment_received']) ? 1 : 0
        );
    }
    
    private static function sanitize_seo_settings($input) {
        return array(
            'meta_title' => sanitize_text_field($input['meta_title'] ?? ''),
            'meta_description' => sanitize_textarea_field($input['meta_description'] ?? '')
        );
    }
    
    /**
     * Get default settings
     */
    private static function get_default_settings() {
        return array(
            'general' => array(
                'auto_approve_listings' => 0,
                'require_verification' => 0,
                'max_listings_per_user' => 5,
                'max_images_per_listing' => 5,
                'listing_expiry_days' => 30
            ),
            'payments' => array(
                'currency' => 'PLN',
                'featured_price' => 29,
                'boost_price' => 19,
                'verify_price' => 49,
                'paypal_enabled' => 0,
                'paypal_client_id' => '',
                'paypal_secret' => '',
                'paypal_mode' => 'sandbox',
                'stripe_enabled' => 0,
                'stripe_public_key' => '',
                'stripe_secret_key' => ''
            ),
            'moderation' => array(
                'auto_moderation' => 1,
                'banned_words' => '',
                'max_reports_before_ban' => 3
            ),
            'notifications' => array(
                'email_new_message' => 1,
                'email_new_review' => 1,
                'email_listing_approved' => 1,
                'email_payment_received' => 1
            ),
            'seo' => array(
                'meta_title' => '',
                'meta_description' => ''
            )
        );
    }
}