<?php
if (!defined('ABSPATH')) exit;

global $wpdb;

// Filtry
$category = sanitize_text_field($_GET['category'] ?? '');
$city = sanitize_text_field($_GET['city'] ?? '');
$price_min = floatval($_GET['price_min'] ?? 0);
$price_max = floatval($_GET['price_max'] ?? 1000);

// Build query
$where = "WHERE status = 'active'";
$params = array();

if ($category) {
    $where .= " AND category = %s";
    $params[] = $category;
}

if ($city) {
    $where .= " AND city LIKE %s";
    $params[] = '%' . $wpdb->esc_like($city) . '%';
}

if ($price_min > 0) {
    $where .= " AND (is_free = 1 OR price >= %f)";
    $params[] = $price_min;
}

if ($price_max > 0 && $price_max < 1000) {
    $where .= " AND (is_free = 1 OR price <= %f)";
    $params[] = $price_max;
}

// Get listings
$listings = $wpdb->get_results($wpdb->prepare("
    SELECT l.*, u.display_name, u.avg_rating, u.is_verified 
    FROM {$wpdb->prefix}png_listings l
    LEFT JOIN {$wpdb->prefix}png_user_profiles u ON l.user_id = u.user_id
    {$where}
    ORDER BY l.featured_until DESC, l.created_at DESC
    LIMIT 50
" . (!empty($params) ? $params : '')));

$categories = array(
    '' => 'Wszystkie kategorie',
    'rozmowa' => 'Rozmowa',
    'spacer' => 'Spacer',
    'kawa' => 'Wspólna kawa',
    'kino' => 'Kino',
    'sport' => 'Sport',
    'hobby' => 'Wspólne hobby',
    'nauka' => 'Korepetycje',
    'inne' => 'Inne'
);
?>

<div class="png-container">
    <h1><?php _e('Znajdź towarzysza', 'przyjaciel-na-godzine'); ?></h1>
    
    <!-- Filtry -->
    <div class="png-search-form">
        <form method="get" action="">
            <input type="text" name="city" class="png-form-control png-search-input" 
                   placeholder="<?php _e('Miasto...', 'przyjaciel-na-godzine'); ?>" value="<?php echo esc_attr($city); ?>">
            
            <select name="category" class="png-form-control">
                <?php foreach ($categories as $key => $name): ?>
                    <option value="<?php echo $key; ?>" <?php selected($category, $key); ?>>
                        <?php echo $name; ?>
                    </option>
                <?php endforeach; ?>
            </select>
            
            <input type="number" name="price_min" class="png-form-control" placeholder="Cena od" 
                   value="<?php echo $price_min; ?>" min="0" step="1">
            
            <input type="number" name="price_max" class="png-form-control" placeholder="Cena do" 
                   value="<?php echo $price_max; ?>" min="0" step="1">
            
            <button type="submit" class="png-btn"><?php _e('Szukaj', 'przyjaciel-na-godzine'); ?></button>
            <a href="?" class="png-btn" style="background: #666;"><?php _e('Wyczyść', 'przyjaciel-na-godzine'); ?></a>
        </form>
    </div>
    
    <!-- Kategorie -->
    <div class="png-categories-filter">
        <?php foreach ($categories as $key => $name): if ($key !== ''): ?>
            <a href="?category=<?php echo $key; ?>" 
               class="png-category-btn <?php echo $category === $key ? 'active' : ''; ?>">
                <?php echo $name; ?>
            </a>
        <?php endif; endforeach; ?>
    </div>
    
    <!-- Lista ogłoszeń -->
    <div class="png-listings-grid">
        <?php if (empty($listings)): ?>
            <div class="png-no-results">
                <p><?php _e('Nie znaleziono ogłoszeń spełniających kryteria.', 'przyjaciel-na-godzine'); ?></p>
            </div>
        <?php else: ?>
            <?php foreach ($listings as $listing): ?>
                <div class="png-listing-card">
                    <?php if ($listing->featured_until && strtotime($listing->featured_until) > time()): ?>
                        <span class="png-featured-badge">★ Wyróżnione</span>
                    <?php endif; ?>
                    
                    <h3><?php echo esc_html($listing->title); ?></h3>
                    
                    <div class="png-listing-meta">
                        <span class="png-listing-city">📍 <?php echo esc_html($listing->city); ?></span>
                        <span class="png-listing-age">👤 <?php echo $listing->age ? $listing->age . ' lat' : 'Wiek nie podany'; ?></span>
                    </div>
                    
                    <p class="png-listing-description">
                        <?php echo wp_trim_words(esc_html($listing->description), 20); ?>
                    </p>
                    
                    <div class="png-listing-price">
                        <?php if ($listing->is_free): ?>
                            <span class="png-listing-free">🆓 Bezpłatnie</span>
                        <?php else: ?>
                            💰 <?php echo number_format($listing->price, 2); ?> zł
                        <?php endif; ?>
                    </div>
                    
                    <div class="png-listing-author">
                        <strong><?php echo esc_html($listing->display_name ?: 'Anonim'); ?></strong>
                        <?php if ($listing->is_verified): ?>
                            <span class="png-verified-badge">✓ Zweryfikowany</span>
                        <?php endif; ?>
                        
                        <?php if ($listing->avg_rating > 0): ?>
                            <div class="png-rating">
                                ⭐ <?php echo number_format($listing->avg_rating, 1); ?> (<?php echo $listing->total_reviews ?? 0; ?>)
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="png-listing-actions">
                        <a href="<?php echo home_url('/ogloszenie/' . $listing->id); ?>" class="png-btn" style="font-size: 14px; padding: 8px 15px;">
                            <?php _e('Zobacz szczegóły', 'przyjaciel-na-godzine'); ?>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>