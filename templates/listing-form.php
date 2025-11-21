<?php
/**
 * Lokalizacja: /templates/listing-form.php
 */

if (!defined('ABSPATH')) exit;

$user_id = get_current_user_id();
$listing_id = isset($_GET['edit']) ? intval($_GET['edit']) : 0;
$listing = null;

if ($listing_id) {
    $post = get_post($listing_id);
    if ($post && $post->post_author == $user_id) {
        $listing = PNG_Listings::get($listing_id);
    } else {
        echo '<div class="png-alert png-alert-error">' . __('Brak uprawnień do edycji tego ogłoszenia.', 'png') . '</div>';
        return;
    }
}

$categories = get_terms(array('taxonomy' => 'listing_category', 'hide_empty' => false));
?>

<div class="png-listing-form-wrapper">
    <div class="png-form-header">
        <h2><?php echo $listing_id ? __('Edytuj ogłoszenie', 'png') : __('Dodaj nowe ogłoszenie', 'png'); ?></h2>
        <p><?php _e('Wypełnij formularz, aby utworzyć swoje ogłoszenie', 'png'); ?></p>
    </div>

    <form id="png-listing-form" class="png-form" enctype="multipart/form-data">
        <?php wp_nonce_field('png_save_listing', 'png_nonce'); ?>
        <input type="hidden" name="listing_id" value="<?php echo $listing_id; ?>">
        
        <!-- Tytuł -->
        <div class="png-form-section">
            <h3><?php _e('Podstawowe informacje', 'png'); ?></h3>
            
            <div class="png-form-group">
                <label class="png-form-label" for="title">
                    <?php _e('Tytuł ogłoszenia', 'png'); ?> <span class="required">*</span>
                </label>
                <input type="text" 
                       id="title" 
                       name="title" 
                       class="png-form-input" 
                       value="<?php echo $listing ? esc_attr($listing['title']) : ''; ?>"
                       placeholder="<?php _e('np. Towarzysz do biegania w weekendy', 'png'); ?>"
                       required>
                <span class="png-form-help"><?php _e('Minimum 10 znaków', 'png'); ?></span>
            </div>
            
            <div class="png-form-group">
                <label class="png-form-label" for="description">
                    <?php _e('Opis', 'png'); ?> <span class="required">*</span>
                </label>
                <textarea id="description" 
                          name="description" 
                          class="png-form-textarea" 
                          rows="6"
                          placeholder="<?php _e('Opisz czego szukasz, swoje zainteresowania i oczekiwania...', 'png'); ?>"
                          required><?php echo $listing ? esc_textarea($listing['description']) : ''; ?></textarea>
                <span class="png-form-help"><?php _e('Minimum 50 znaków', 'png'); ?></span>
            </div>
        </div>

        <!-- Kategoria i szczegóły -->
        <div class="png-form-section">
            <h3><?php _e('Szczegóły', 'png'); ?></h3>
            
            <div class="png-form-row">
                <div class="png-form-group">
                    <label class="png-form-label" for="category">
                        <?php _e('Kategoria', 'png'); ?> <span class="required">*</span>
                    </label>
                    <select id="category" name="category" class="png-form-select" required>
                        <option value=""><?php _e('Wybierz kategorię', 'png'); ?></option>
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo esc_attr($cat->slug); ?>" 
                                <?php echo ($listing && $listing['category'] === $cat->slug) ? 'selected' : ''; ?>>
                            <?php echo esc_html($cat->name); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="png-form-group">
                    <label class="png-form-label" for="meeting_type">
                        <?php _e('Typ spotkania', 'png'); ?> <span class="required">*</span>
                    </label>
                    <select id="meeting_type" name="meeting_type" class="png-form-select" required>
                        <option value=""><?php _e('Wybierz typ', 'png'); ?></option>
                        <option value="online" <?php echo ($listing && $listing['meeting_type'] === 'online') ? 'selected' : ''; ?>>
                            <?php _e('Online', 'png'); ?>
                        </option>
                        <option value="offline" <?php echo ($listing && $listing['meeting_type'] === 'offline') ? 'selected' : ''; ?>>
                            <?php _e('Osobiście', 'png'); ?>
                        </option>
                        <option value="both" <?php echo ($listing && $listing['meeting_type'] === 'both') ? 'selected' : ''; ?>>
                            <?php _e('Online lub osobiście', 'png'); ?>
                        </option>
                    </select>
                </div>
            </div>
            
            <div class="png-form-row">
                <div class="png-form-group">
                    <label class="png-form-label" for="location">
                        <?php _e('Lokalizacja', 'png'); ?>
                    </label>
                    <input type="text" 
                           id="location" 
                           name="location" 
                           class="png-form-input" 
                           value="<?php echo $listing ? esc_attr($listing['location']) : ''; ?>"
                           placeholder="<?php _e('np. Warszawa, Mokotów', 'png'); ?>">
                </div>
                
                <div class="png-form-group">
                    <label class="png-form-label" for="price">
                        <?php _e('Cena za godzinę (PLN)', 'png'); ?>
                    </label>
                    <input type="number" 
                           id="price" 
                           name="price" 
                           class="png-form-input" 
                           value="<?php echo $listing ? esc_attr($listing['price']) : '0'; ?>"
                           min="0"
                           step="1"
                           placeholder="0">
                    <span class="png-form-help"><?php _e('Pozostaw 0 jeśli usługa darmowa', 'png'); ?></span>
                </div>
            </div>
        </div>

        <!-- Zdjęcia -->
        <div class="png-form-section">
            <h3><?php _e('Zdjęcia', 'png'); ?></h3>
            <p class="png-form-help"><?php _e('Dodaj zdjęcia do swojego ogłoszenia (max 5)', 'png'); ?></p>
            
            <div class="png-image-upload-area">
                <div class="png-upload-box" id="png-upload-trigger">
                    <i class="fas fa-cloud-upload-alt fa-3x"></i>
                    <p><?php _e('Kliknij lub przeciągnij zdjęcia tutaj', 'png'); ?></p>
                    <span class="png-form-help"><?php _e('JPG, PNG, GIF do 5MB', 'png'); ?></span>
                </div>
                <input type="file" 
                       id="png-image-input" 
                       name="images[]" 
                       multiple 
                       accept="image/*" 
                       style="display: none;">
            </div>
            
            <div id="png-image-preview" class="png-image-preview">
                <?php if ($listing && !empty($listing['images'])): ?>
                    <?php foreach ($listing['images'] as $index => $image): ?>
                    <div class="png-preview-item" data-image-url="<?php echo esc_url($image['url']); ?>">
                        <img src="<?php echo esc_url($image['thumbnail'] ?: $image['url']); ?>" alt="">
                        <button type="button" class="png-remove-image" data-index="<?php echo $index; ?>">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Dostępność -->
        <div class="png-form-section">
            <h3><?php _e('Dostępność', 'png'); ?></h3>
            
            <div class="png-form-group">
                <label class="png-form-label">
                    <?php _e('Kiedy jesteś dostępny?', 'png'); ?>
                </label>
                <textarea name="availability" 
                          class="png-form-textarea" 
                          rows="3"
                          placeholder="<?php _e('np. Weekendy, wieczory w tygodniu...', 'png'); ?>"><?php echo $listing ? esc_textarea($listing['availability']) : ''; ?></textarea>
            </div>
        </div>

        <!-- Dodatkowe opcje PRO -->
        <?php if (PNG_Subscriptions::user_has_feature($user_id, 'featured_listings')): ?>
        <div class="png-form-section png-pro-section">
            <h3><i class="fas fa-crown"></i> <?php _e('Opcje Premium', 'png'); ?></h3>
            
            <div class="png-form-group">
                <label class="png-checkbox-label">
                    <input type="checkbox" name="make_featured" value="1">
                    <span><?php _e('Wyróżnij to ogłoszenie (wyświetlane na górze listy)', 'png'); ?></span>
                </label>
            </div>
        </div>
        <?php endif; ?>

        <!-- Regulamin -->
        <div class="png-form-section">
            <div class="png-form-group">
                <label class="png-checkbox-label">
                    <input type="checkbox" name="accept_terms" value="1" required>
                    <span>
                        <?php _e('Akceptuję', 'png'); ?> 
                        <a href="/regulamin" target="_blank"><?php _e('regulamin', 'png'); ?></a>
                        <?php _e('i zobowiązuję się do przestrzegania zasad platformy', 'png'); ?>
                    </span>
                </label>
            </div>
        </div>

        <!-- Przyciski -->
        <div class="png-form-actions">
            <button type="submit" class="png-button png-button-primary png-button-lg">
                <i class="fas fa-save"></i>
                <?php echo $listing_id ? __('Zapisz zmiany', 'png') : __('Opublikuj ogłoszenie', 'png'); ?>
            </button>
            
            <a href="<?php echo home_url('/moje-ogloszenia'); ?>" class="png-button png-button-secondary png-button-lg">
                <?php _e('Anuluj', 'png'); ?>
            </a>
        </div>
    </form>
</div>

<style>
.png-listing-form-wrapper {
    max-width: 800px;
    margin: 0 auto;
    padding: 20px;
}

.png-form-header {
    text-align: center;
    margin-bottom: 40px;
}

.png-form-header h2 {
    margin: 0 0 10px 0;
    color: #333;
}

.png-form-header p {
    color: #666;
    margin: 0;
}

.png-form-section {
    background: white;
    padding: 30px;
    margin-bottom: 24px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.png-form-section h3 {
    margin: 0 0 20px 0;
    padding-bottom: 12px;
    border-bottom: 2px solid #f0f0f0;
    color: #333;
}

.png-pro-section h3 {
    color: #ffd700;
}

.png-form-group {
    margin-bottom: 20px;
}

.png-form-label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: #333;
}

.required {
    color: #e74c3c;
}

.png-form-input,
.png-form-select,
.png-form-textarea {
    width: 100%;
    padding: 12px 16px;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    font-size: 15px;
    transition: border-color 0.3s;
}

.png-form-input:focus,
.png-form-select:focus,
.png-form-textarea:focus {
    outline: none;
    border-color: #007cba;
}

.png-form-textarea {
    resize: vertical;
    font-family: inherit;
}

.png-form-help {
    display: block;
    margin-top: 6px;
    font-size: 13px;
    color: #666;
}

.png-form-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
}

.png-upload-box {
    border: 3px dashed #ddd;
    border-radius: 12px;
    padding: 40px;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s;
    background: #fafafa;
}

.png-upload-box:hover {
    border-color: #007cba;
    background: #f0f8ff;
}

.png-upload-box i {
    color: #007cba;
    margin-bottom: 12px;
}

.png-image-preview {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    gap: 16px;
    margin-top: 20px;
}

.png-preview-item {
    position: relative;
    aspect-ratio: 1;
    border-radius: 8px;
    overflow: hidden;
}

.png-preview-item img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.png-remove-image {
    position: absolute;
    top: 8px;
    right: 8px;
    background: rgba(255,0,0,0.8);
    color: white;
    border: none;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
}

.png-checkbox-label {
    display: flex;
    align-items: center;
    gap: 10px;
    cursor: pointer;
}

.png-checkbox-label input[type="checkbox"] {
    width: 20px;
    height: 20px;
    cursor: pointer;
}

.png-form-actions {
    display: flex;
    gap: 16px;
    justify-content: center;
    margin-top: 30px;
}

.png-button-lg {
    padding: 14px 32px;
    font-size: 16px;
}

@media (max-width: 768px) {
    .png-form-row {
        grid-template-columns: 1fr;
    }
    
    .png-form-actions {
        flex-direction: column;
    }
    
    .png-form-actions .png-button {
        width: 100%;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // Image upload
    $('#png-upload-trigger').on('click', function() {
        $('#png-image-input').click();
    });
    
    $('#png-image-input').on('change', function(e) {
        var files = e.target.files;
        
        for (var i = 0; i < files.length; i++) {
            uploadImage(files[i]);
        }
    });
    
    function uploadImage(file) {
        var formData = new FormData();
        formData.append('action', 'png_upload_image');
        formData.append('nonce', pngData.nonce);
        formData.append('image', file);
        
        $.ajax({
            url: pngData.ajaxUrl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    var html = '<div class="png-preview-item" data-image-url="' + response.data.url + '">' +
                               '<img src="' + (response.data.thumbnail || response.data.url) + '" alt="">' +
                               '<button type="button" class="png-remove-image"><i class="fas fa-times"></i></button>' +
                               '</div>';
                    $('#png-image-preview').append(html);
                }
            }
        });
    }
    
    // Remove image
    $(document).on('click', '.png-remove-image', function() {
        $(this).closest('.png-preview-item').remove();
    });
    
    // Form submission
    $('#png-listing-form').on('submit', function(e) {
        e.preventDefault();
        
        var $form = $(this);
        var $btn = $form.find('button[type="submit"]');
        var btnText = $btn.html();
        
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> ' + pngData.strings.loading);
        
        var formData = new FormData(this);
        formData.append('action', 'png_save_listing');
        
        // Collect image URLs
        var images = [];
        $('#png-image-preview .png-preview-item').each(function() {
            images.push($(this).data('image-url'));
        });
        formData.append('images', JSON.stringify(images));
        
        $.ajax({
            url: pngData.ajaxUrl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    alert(response.data.message);
                    if (response.data.redirect) {
                        window.location.href = response.data.redirect;
                    }
                } else {
                    alert(response.data.message || pngData.strings.error);
                }
                $btn.prop('disabled', false).html(btnText);
            },
            error: function() {
                alert(pngData.strings.error);
                $btn.prop('disabled', false).html(btnText);
            }
        });
    });
});
</script>