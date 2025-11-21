<?php
if (!defined('ABSPATH')) {
    exit;
}

class PNG_Users {
    
    public function __construct() {
        add_shortcode('png_user_profile', array($this, 'display_user_profile'));
        add_shortcode('png_profile_edit', array($this, 'display_profile_edit_form'));
    }
    
    public function display_user_profile($atts) {
        if (!is_user_logged_in()) {
            return '<p>Musisz być zalogowany, aby zobaczyć swój profil.</p>';
        }
        
        $user_id = get_current_user_id();
        $user_data = get_userdata($user_id);
        
        ob_start();
        ?>
        <div class="png-user-profile">
            <h2>Twój profil</h2>
            <div class="png-profile-info">
                <p><strong>Nazwa użytkownika:</strong> <?php echo esc_html($user_data->display_name); ?></p>
                <p><strong>Email:</strong> <?php echo esc_html($user_data->user_email); ?></p>
                <p><strong>Data rejestracji:</strong> <?php echo date('d.m.Y', strtotime($user_data->user_registered)); ?></p>
            </div>
            <div class="png-profile-actions">
                <a href="<?php echo esc_url(home_url('/edit-profile')); ?>" class="png-button">Edytuj profil</a>
                <a href="<?php echo esc_url(home_url('/my-listings')); ?>" class="png-button png-button-secondary">Moje ogłoszenia</a>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    public function display_profile_edit_form() {
        if (!is_user_logged_in()) {
            return '<p>Musisz być zalogowany, aby edytować profil.</p>';
        }
        
        $user_id = get_current_user_id();
        $user_data = get_userdata($user_id);
        
        ob_start();
        ?>
        <div class="png-profile-edit">
            <h2>Edytuj profil</h2>
            
            <?php if (isset($_GET['updated']) && $_GET['updated'] == '1'): ?>
                <div class="png-alert png-alert-success">
                    Profil został zaktualizowany pomyślnie.
                </div>
            <?php endif; ?>
            
            <form method="post" action="<?php echo admin_url('admin-ajax.php'); ?>">
                <input type="hidden" name="action" value="png_update_profile">
                <?php wp_nonce_field('png_update_profile', 'png_nonce'); ?>
                
                <div class="png-form-group">
                    <label class="png-form-label" for="display_name">Imię / Nick *</label>
                    <input type="text" id="display_name" name="display_name" class="png-form-input" value="<?php echo esc_attr($user_data->display_name); ?>" required>
                </div>
                
                <div class="png-form-group">
                    <label class="png-form-label" for="email">Email *</label>
                    <input type="email" id="email" name="email" class="png-form-input" value="<?php echo esc_attr($user_data->user_email); ?>" required>
                </div>
                
                <div class="png-form-group">
                    <label class="png-form-label" for="description">O sobie</label>
                    <textarea id="description" name="description" class="png-form-textarea" rows="5"><?php echo esc_textarea(get_user_meta($user_id, 'description', true)); ?></textarea>
                    <span class="png-form-help">Opisz siebie, swoje zainteresowania, co oferujesz.</span>
                </div>
                
                <div class="png-form-group">
                    <input type="checkbox" id="terms_accept" name="terms_accept" required>
                    <label for="terms_accept">Akceptuję <a href="/regulamin" target="_blank">regulamin</a> *</label>
                </div>
                
                <button type="submit" class="png-button">Zapisz zmiany</button>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }
}