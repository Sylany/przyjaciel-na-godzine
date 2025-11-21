<?php
if (!defined('ABSPATH')) {
    exit;
}

class PNG_Shortcodes {
    
    public static function init() {
        $shortcodes = array(
            'png_listings_archive' => 'listings_archive',
            'png_listing_form' => 'listing_form',
            'png_my_listings' => 'my_listings',
            'png_user_profile' => 'user_profile',
            'png_profile_edit' => 'profile_edit',
            'png_messages' => 'messages',
            'png_payment_checkout' => 'payment_checkout',
            'png_payment_success' => 'payment_success',
            'png_payment_cancelled' => 'payment_cancelled',
            'png_favorites' => 'favorites',
            'png_user_statistics' => 'user_statistics',
            'png_subscriptions' => 'subscriptions',
            'png_verification' => 'verification',
            'png_search_form' => 'search_form'
        );
        
        foreach ($shortcodes as $tag => $method) {
            add_shortcode($tag, array(__CLASS__, $method));
        }
    }
    
    public static function listings_archive($atts) {
        $atts = shortcode_atts(array(
            'per_page' => 12,
            'category' => '',
            'featured' => '',
            'orderby' => 'date',
            'order' => 'DESC'
        ), $atts);
        
        ob_start();
        include PNG_PLUGIN_PATH . 'templates/listings-archive.php';
        return ob_get_clean();
    }
    
    public static function listing_form($atts) {
        if (!is_user_logged_in()) {
            return '<div class="png-alert png-alert-warning">' . 
                   __('Musisz być zalogowany, aby dodać ogłoszenie.', 'png') . 
                   ' <a href="' . wp_login_url(get_permalink()) . '">' . __('Zaloguj się', 'png') . '</a></div>';
        }
        
        ob_start();
        include PNG_PLUGIN_PATH . 'templates/listing-form.php';
        return ob_get_clean();
    }
    
    public static function my_listings($atts) {
        if (!is_user_logged_in()) {
            return '<div class="png-alert png-alert-warning">' . 
                   __('Musisz być zalogowany.', 'png') . 
                   ' <a href="' . wp_login_url(get_permalink()) . '">' . __('Zaloguj się', 'png') . '</a></div>';
        }
        
        ob_start();
        include PNG_PLUGIN_PATH . 'templates/my-listings.php';
        return ob_get_clean();
    }
    
    public static function user_profile($atts) {
        $atts = shortcode_atts(array(
            'user_id' => get_current_user_id()
        ), $atts);
        
        if (!$atts['user_id']) {
            return '<div class="png-alert png-alert-error">' . __('Nie znaleziono użytkownika.', 'png') . '</div>';
        }
        
        ob_start();
        include PNG_PLUGIN_PATH . 'templates/user-profile.php';
        return ob_get_clean();
    }
    
    public static function profile_edit($atts) {
        if (!is_user_logged_in()) {
            return '<div class="png-alert png-alert-warning">' . 
                   __('Musisz być zalogowany.', 'png') . 
                   ' <a href="' . wp_login_url(get_permalink()) . '">' . __('Zaloguj się', 'png') . '</a></div>';
        }
        
        ob_start();
        include PNG_PLUGIN_PATH . 'templates/profile-edit.php';
        return ob_get_clean();
    }
    
    public static function messages($atts) {
        if (!is_user_logged_in()) {
            return '<div class="png-alert png-alert-warning">' . 
                   __('Musisz być zalogowany.', 'png') . 
                   ' <a href="' . wp_login_url(get_permalink()) . '">' . __('Zaloguj się', 'png') . '</a></div>';
        }
        
        ob_start();
        include PNG_PLUGIN_PATH . 'templates/messages.php';
        return ob_get_clean();
    }
    
    public static function payment_checkout($atts) {
        if (!is_user_logged_in()) {
            return '<div class="png-alert png-alert-warning">' . 
                   __('Musisz być zalogowany.', 'png') . 
                   ' <a href="' . wp_login_url(get_permalink()) . '">' . __('Zaloguj się', 'png') . '</a></div>';
        }
        
        ob_start();
        include PNG_PLUGIN_PATH . 'templates/payment-checkout.php';
        return ob_get_clean();
    }
    
    public static function payment_success($atts) {
        ob_start();
        include PNG_PLUGIN_PATH . 'templates/payment-success.php';
        return ob_get_clean();
    }
    
    public static function payment_cancelled($atts) {
        ob_start();
        include PNG_PLUGIN_PATH . 'templates/payment-cancelled.php';
        return ob_get_clean();
    }
    
    public static function favorites($atts) {
        if (!is_user_logged_in()) {
            return '<div class="png-alert png-alert-warning">' . 
                   __('Musisz być zalogowany.', 'png') . 
                   ' <a href="' . wp_login_url(get_permalink()) . '">' . __('Zaloguj się', 'png') . '</a></div>';
        }
        
        ob_start();
        include PNG_PLUGIN_PATH . 'templates/favorites.php';
        return ob_get_clean();
    }
    
    public static function user_statistics($atts) {
        if (!is_user_logged_in()) {
            return '<div class="png-alert png-alert-warning">' . 
                   __('Musisz być zalogowany.', 'png') . 
                   ' <a href="' . wp_login_url(get_permalink()) . '">' . __('Zaloguj się', 'png') . '</a></div>';
        }
        
        ob_start();
        include PNG_PLUGIN_PATH . 'templates/user-statistics.php';
        return ob_get_clean();
    }
    
    public static function subscriptions($atts) {
        if (!is_user_logged_in()) {
            return '<div class="png-alert png-alert-warning">' . 
                   __('Musisz być zalogowany.', 'png') . 
                   ' <a href="' . wp_login_url(get_permalink()) . '">' . __('Zaloguj się', 'png') . '</a></div>';
        }
        
        ob_start();
        include PNG_PLUGIN_PATH . 'templates/subscriptions.php';
        return ob_get_clean();
    }
    
    public static function verification($atts) {
        if (!is_user_logged_in()) {
            return '<div class="png-alert png-alert-warning">' . 
                   __('Musisz być zalogowany.', 'png') . 
                   ' <a href="' . wp_login_url(get_permalink()) . '">' . __('Zaloguj się', 'png') . '</a></div>';
        }
        
        ob_start();
        include PNG_PLUGIN_PATH . 'templates/verification-form.php';
        return ob_get_clean();
    }
    
    public static function search_form($atts) {
        $atts = shortcode_atts(array(
            'style' => 'default',
            'show_filters' => 'yes'
        ), $atts);
        
        ob_start();
        ?>
        <div class="png-search-form">
            <form method="get" action="<?php echo esc_url(home_url('/znajdz-towarzysza')); ?>">
                <div class="png-search-row">
                    <div class="png-search-field">
                        <input type="text" name="s" placeholder="<?php esc_attr_e('Czego szukasz?', 'png'); ?>" value="<?php echo esc_attr(get_query_var('s')); ?>">
                    </div>
                    
                    <div class="png-search-field">
                        <input type="text" name="location" placeholder="<?php esc_attr_e('Lokalizacja', 'png'); ?>" value="<?php echo esc_attr(get_query_var('location')); ?>">
                    </div>
                    
                    <div class="png-search-field">
                        <select name="category">
                            <option value=""><?php _e('Wszystkie kategorie', 'png'); ?></option>
                            <?php
                            $categories = get_terms(array('taxonomy' => 'listing_category', 'hide_empty' => false));
                            foreach ($categories as $cat) {
                                echo '<option value="' . esc_attr($cat->slug) . '" ' . selected(get_query_var('category'), $cat->slug, false) . '>' . esc_html($cat->name) . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                    
                    <button type="submit" class="png-button png-button-primary">
                        <i class="fas fa-search"></i> <?php _e('Szukaj', 'png'); ?>
                    </button>
                </div>
                
                <?php if ($atts['show_filters'] === 'yes'): ?>
                <div class="png-search-filters">
                    <div class="png-filter-group">
                        <label><?php _e('Cena', 'png'); ?></label>
                        <input type="number" name="price_min" placeholder="Od" value="<?php echo esc_attr(get_query_var('price_min')); ?>">
                        <input type="number" name="price_max" placeholder="Do" value="<?php echo esc_attr(get_query_var('price_max')); ?>">
                    </div>
                    
                    <div class="png-filter-group">
                        <label><?php _e('Typ spotkania', 'png'); ?></label>
                        <select name="meeting_type">
                            <option value=""><?php _e('Wszystkie', 'png'); ?></option>
                            <option value="online" <?php selected(get_query_var('meeting_type'), 'online'); ?>><?php _e('Online', 'png'); ?></option>
                            <option value="offline" <?php selected(get_query_var('meeting_type'), 'offline'); ?>><?php _e('Osobiście', 'png'); ?></option>
                        </select>
                    </div>
                    
                    <div class="png-filter-group">
                        <label>
                            <input type="checkbox" name="verified_only" value="1" <?php checked(get_query_var('verified_only'), '1'); ?>>
                            <?php _e('Tylko zweryfikowani', 'png'); ?>
                        </label>
                    </div>
                </div>
                <?php endif; ?>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }
}