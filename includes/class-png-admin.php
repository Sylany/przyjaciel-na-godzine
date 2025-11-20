<?php
if (!defined('ABSPATH')) {
    exit;
}

class PNG_Admin {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'admin_init'));
    }
    
    public function admin_init() {
        register_setting('png_settings', 'png_options');
    }
    
    public function add_admin_menu() {
        add_menu_page(
            'Przyjaciel na Godzinę',
            'Przyjaciel na Godzinę',
            'manage_options',
            'przyjaciel-na-godzine',
            array($this, 'admin_dashboard'),
            'dashicons-groups',
            6
        );
        
        add_submenu_page(
            'przyjaciel-na-godzine',
            'Ustawienia',
            'Ustawienia',
            'manage_options',
            'png-settings',
            array($this, 'settings_page')
        );
    }
    
    public function admin_dashboard() {
        ?>
        <div class="wrap">
            <h1>Przyjaciel na Godzinę - Panel Administracyjny</h1>
            
            <div class="png-dashboard-stats">
                <div class="png-stat-card">
                    <h3>Aktywne ogłoszenia</h3>
                    <p class="stat-number"><?php echo $this->count_active_listings(); ?></p>
                </div>
                
                <div class="png-stat-card">
                    <h3>Zarejestrowani użytkownicy</h3>
                    <p class="stat-number"><?php echo $this->count_registered_users(); ?></p>
                </div>
            </div>
            
            <div class="png-admin-info">
                <h2>Informacje o wtyczce</h2>
                <p>Wersja: 1.0.0</p>
                <p>Shortcodes dostępne do użycia:</p>
                <ul>
                    <li><code>[png_user_profile]</code> - Profil użytkownika</li>
                    <li><code>[png_profile_edit]</code> - Edycja profilu</li>
                    <li><code>[png_listing_form]</code> - Formularz ogłoszenia</li>
                    <li><code>[png_my_listings]</code> - Lista ogłoszeń użytkownika</li>
                </ul>
            </div>
        </div>
        
        <style>
        .png-dashboard-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        
        .png-stat-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .png-stat-card h3 {
            margin: 0 0 10px 0;
            color: #666;
            font-size: 14px;
            text-transform: uppercase;
        }
        
        .stat-number {
            font-size: 2em;
            font-weight: bold;
            margin: 0;
            color: #007cba;
        }
        
        .png-admin-info {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-top: 20px;
        }
        </style>
        <?php
    }
    
    public function settings_page() {
        $options = get_option('png_options', array(
            'email_verification' => 0,
            'max_images' => 5,
            'auto_moderation' => 0
        ));
        ?>
        <div class="wrap">
            <h1>Ustawienia Przyjaciel na Godzinę</h1>
            
            <form method="post" action="options.php">
                <?php settings_fields('png_settings'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">Wymagana weryfikacja email</th>
                        <td>
                            <input type="checkbox" name="png_options[email_verification]" value="1" <?php checked(1, $options['email_verification']); ?>>
                            <p class="description">Wymagaj weryfikacji email przed publikacją ogłoszeń</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">Automatyczna moderacja</th>
                        <td>
                            <input type="checkbox" name="png_options[auto_moderation]" value="1" <?php checked(1, $options['auto_moderation']); ?>>
                            <p class="description">Automatyczne blokowanie ogłoszeń z zakazanymi słowami</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">Maksymalna liczba zdjęć</th>
                        <td>
                            <input type="number" name="png_options[max_images]" value="<?php echo esc_attr($options['max_images']); ?>" min="1" max="10">
                            <p class="description">Maksymalna liczba zdjęć na ogłoszenie</p>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
    
    private function count_active_listings() {
        $count = wp_count_posts('listing');
        return $count->publish;
    }
    
    private function count_registered_users() {
        return count(get_users());
    }
}