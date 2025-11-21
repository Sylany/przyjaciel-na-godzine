<?php
/**
 * Lokalizacja: /includes/class-png-images.php
 */

if (!defined('ABSPATH')) {
    exit;
}

class PNG_Images {
    
    private static $allowed_types = array('image/jpeg', 'image/png', 'image/gif', 'image/webp');
    private static $max_size = 5242880; // 5MB
    
    /**
     * Upload image
     */
    public static function upload($file, $subdir = 'listings') {
        // Validate file
        $validation = self::validate($file);
        if (is_wp_error($validation)) {
            return $validation;
        }
        
        // Set upload directory
        add_filter('upload_dir', function($dirs) use ($subdir) {
            $dirs['path'] = $dirs['basedir'] . '/png-' . $subdir;
            $dirs['url'] = $dirs['baseurl'] . '/png-' . $subdir;
            $dirs['subdir'] = '/png-' . $subdir;
            return $dirs;
        });
        
        // Handle upload
        $upload = wp_handle_upload($file, array('test_form' => false));
        
        remove_all_filters('upload_dir');
        
        if (isset($upload['error'])) {
            return new WP_Error('upload_error', $upload['error']);
        }
        
        // Create thumbnail
        $thumbnail = self::create_thumbnail($upload['file']);
        
        // Optimize image
        self::optimize_image($upload['file']);
        
        return array(
            'url' => $upload['url'],
            'file' => $upload['file'],
            'thumbnail' => $thumbnail,
            'type' => $upload['type']
        );
    }
    
    /**
     * Validate image file
     */
    private static function validate($file) {
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            return new WP_Error('invalid_file', __('Nieprawidłowy plik.', 'png'));
        }
        
        if (!in_array($file['type'], self::$allowed_types)) {
            return new WP_Error('invalid_type', __('Dozwolone formaty: JPG, PNG, GIF, WEBP', 'png'));
        }
        
        if ($file['size'] > self::$max_size) {
            return new WP_Error('file_too_large', 
                sprintf(__('Plik za duży. Maksymalny rozmiar: %s', 'png'), size_format(self::$max_size))
            );
        }
        
        // Verify it's actually an image
        $image_info = getimagesize($file['tmp_name']);
        if ($image_info === false) {
            return new WP_Error('not_image', __('Plik nie jest obrazem.', 'png'));
        }
        
        return true;
    }
    
    /**
     * Create thumbnail
     */
    private static function create_thumbnail($file, $width = 300, $height = 300) {
        $image_editor = wp_get_image_editor($file);
        
        if (is_wp_error($image_editor)) {
            return false;
        }
        
        $image_editor->resize($width, $height, true);
        
        $thumbnail_path = preg_replace('/(\.[^.]+)$/', '-thumb$1', $file);
        $saved = $image_editor->save($thumbnail_path);
        
        if (is_wp_error($saved)) {
            return false;
        }
        
        return str_replace(wp_upload_dir()['basedir'], wp_upload_dir()['baseurl'], $thumbnail_path);
    }
    
    /**
     * Optimize image
     */
    private static function optimize_image($file) {
        $image_editor = wp_get_image_editor($file);
        
        if (is_wp_error($image_editor)) {
            return false;
        }
        
        // Set quality
        $image_editor->set_quality(85);
        
        // Save optimized version
        $image_editor->save($file);
        
        return true;
    }
    
    /**
     * Add image to listing
     */
    public static function add_to_listing($listing_id, $image_data) {
        $images = get_post_meta($listing_id, '_listing_images', true);
        
        if (!is_array($images)) {
            $images = array();
        }
        
        // Check limit
        $settings = get_option('png_settings');
        $max_images = $settings['general']['max_images_per_listing'] ?? 5;
        
        if (count($images) >= $max_images) {
            return new WP_Error('limit_reached', 
                sprintf(__('Maksymalna liczba zdjęć: %d', 'png'), $max_images)
            );
        }
        
        $images[] = array(
            'url' => $image_data['url'],
            'thumbnail' => $image_data['thumbnail'],
            'uploaded_at' => current_time('mysql')
        );
        
        update_post_meta($listing_id, '_listing_images', $images);
        
        // Set first image as featured if not set
        if (!has_post_thumbnail($listing_id) && count($images) === 1) {
            self::set_as_featured($listing_id, $image_data['file']);
        }
        
        return true;
    }
    
    /**
     * Remove image from listing
     */
    public static function remove_from_listing($listing_id, $image_url) {
        $images = get_post_meta($listing_id, '_listing_images', true);
        
        if (!is_array($images)) {
            return false;
        }
        
        $images = array_filter($images, function($img) use ($image_url) {
            return $img['url'] !== $image_url;
        });
        
        update_post_meta($listing_id, '_listing_images', array_values($images));
        
        // Delete physical file
        $file_path = str_replace(wp_upload_dir()['baseurl'], wp_upload_dir()['basedir'], $image_url);
        if (file_exists($file_path)) {
            @unlink($file_path);
        }
        
        // Delete thumbnail
        $thumbnail_path = preg_replace('/(\.[^.]+)$/', '-thumb$1', $file_path);
        if (file_exists($thumbnail_path)) {
            @unlink($thumbnail_path);
        }
        
        return true;
    }
    
    /**
     * Set image as featured
     */
    public static function set_as_featured($listing_id, $file_path) {
        $attachment = array(
            'post_mime_type' => mime_content_type($file_path),
            'post_title' => sanitize_file_name(basename($file_path)),
            'post_content' => '',
            'post_status' => 'inherit'
        );
        
        $attach_id = wp_insert_attachment($attachment, $file_path, $listing_id);
        
        if (!is_wp_error($attach_id)) {
            require_once ABSPATH . 'wp-admin/includes/image.php';
            $attach_data = wp_generate_attachment_metadata($attach_id, $file_path);
            wp_update_attachment_metadata($attach_id, $attach_data);
            set_post_thumbnail($listing_id, $attach_id);
        }
        
        return $attach_id;
    }
    
    /**
     * Get listing images
     */
    public static function get_listing_images($listing_id) {
        $images = get_post_meta($listing_id, '_listing_images', true);
        
        if (!is_array($images)) {
            return array();
        }
        
        return $images;
    }
    
    /**
     * Resize image
     */
    public static function resize($file, $width, $height, $crop = false) {
        $image_editor = wp_get_image_editor($file);
        
        if (is_wp_error($image_editor)) {
            return $image_editor;
        }
        
        $image_editor->resize($width, $height, $crop);
        
        $resized_path = preg_replace('/(\.[^.]+)$/', "-{$width}x{$height}$1", $file);
        $saved = $image_editor->save($resized_path);
        
        if (is_wp_error($saved)) {
            return $saved;
        }
        
        return $saved['path'];
    }
    
    /**
     * Add watermark
     */
    public static function add_watermark($file, $watermark_text = '') {
        $image_editor = wp_get_image_editor($file);
        
        if (is_wp_error($image_editor)) {
            return $image_editor;
        }
        
        // Get image dimensions
        $size = $image_editor->get_size();
        
        // Create watermark (simplified - would need GD or Imagick for text)
        // This is a placeholder for watermark functionality
        
        return $image_editor->save($file);
    }
    
    /**
     * Convert to WebP
     */
    public static function convert_to_webp($file) {
        $image_editor = wp_get_image_editor($file);
        
        if (is_wp_error($image_editor)) {
            return $image_editor;
        }
        
        $webp_path = preg_replace('/\.[^.]+$/', '.webp', $file);
        
        $saved = $image_editor->save($webp_path, 'image/webp');
        
        if (is_wp_error($saved)) {
            return $saved;
        }
        
        return $saved['path'];
    }
    
    /**
     * Crop image
     */
    public static function crop($file, $x, $y, $width, $height) {
        $image_editor = wp_get_image_editor($file);
        
        if (is_wp_error($image_editor)) {
            return $image_editor;
        }
        
        $image_editor->crop($x, $y, $width, $height);
        
        return $image_editor->save($file);
    }
    
    /**
     * Rotate image
     */
    public static function rotate($file, $angle) {
        $image_editor = wp_get_image_editor($file);
        
        if (is_wp_error($image_editor)) {
            return $image_editor;
        }
        
        $image_editor->rotate($angle);
        
        return $image_editor->save($file);
    }
    
    /**
     * Get image dimensions
     */
    public static function get_dimensions($file) {
        $image_info = getimagesize($file);
        
        if ($image_info === false) {
            return false;
        }
        
        return array(
            'width' => $image_info[0],
            'height' => $image_info[1],
            'type' => $image_info['mime']
        );
    }
    
    /**
     * Generate placeholder image
     */
    public static function generate_placeholder($width = 800, $height = 600, $text = '') {
        $image = imagecreatetruecolor($width, $height);
        
        // Background color
        $bg_color = imagecolorallocate($image, 240, 240, 240);
        imagefill($image, 0, 0, $bg_color);
        
        // Text color
        $text_color = imagecolorallocate($image, 150, 150, 150);
        
        // Add text
        $font_size = 5;
        $text = $text ?: "{$width}x{$height}";
        $text_width = imagefontwidth($font_size) * strlen($text);
        $text_height = imagefontheight($font_size);
        
        $x = ($width - $text_width) / 2;
        $y = ($height - $text_height) / 2;
        
        imagestring($image, $font_size, $x, $y, $text, $text_color);
        
        // Save to temp file
        $upload_dir = wp_upload_dir();
        $filename = 'placeholder-' . $width . 'x' . $height . '.png';
        $filepath = $upload_dir['path'] . '/' . $filename;
        
        imagepng($image, $filepath);
        imagedestroy($image);
        
        return $upload_dir['url'] . '/' . $filename;
    }
    
    /**
     * Clean up old temp files
     */
    public static function cleanup_temp_files($days = 1) {
        $upload_dir = wp_upload_dir();
        $temp_dir = $upload_dir['basedir'] . '/png-temp/';
        
        if (!is_dir($temp_dir)) {
            return 0;
        }
        
        $files = glob($temp_dir . '*');
        $now = time();
        $deleted = 0;
        
        foreach ($files as $file) {
            if (is_file($file) && ($now - filemtime($file) >= $days * 86400)) {
                if (@unlink($file)) {
                    $deleted++;
                }
            }
        }
        
        return $deleted;
    }
}