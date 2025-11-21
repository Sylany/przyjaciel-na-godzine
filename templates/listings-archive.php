<?php
/**
 * Lokalizacja: /templates/listings-archive.php
 */

if (!defined('ABSPATH')) exit;

$paged = get_query_var('paged') ? get_query_var('paged') : 1;
$category = get_query_var('category');
$search = get_query_var('s');
$location = get_query_var('location');

$args = array(
    'post_type' => 'listing',
    'posts_per_page' => $atts['per_page'],
    'paged' => $paged,
    'post_status' => 'publish'
);

if ($category) {
    $args['tax_query'] = array(
        array(
            'taxonomy' => 'listing_category',
            'field' => 'slug',
            'terms' => $category
        )
    );
}

if ($search) {
    $args['s'] = $search;
}

$query = new WP_Query($args);
?>

<div class="png-listings-wrapper">
    <!-- Search & Filters -->
    <div class="png-search-section">
        <?php echo do_shortcode('[png_search_form]'); ?>
    </div>

    <!-- Results Count -->
    <div class="png-results-header">
        <h2><?php printf(__('Znaleziono %d ogłoszeń', 'png'), $query->found_posts); ?></h2>
        
        <div class="png-sort-options">
            <select id="png-sort" onchange="window.location.href=this.value">
                <option value="?orderby=date&order=DESC"><?php _e('Najnowsze', 'png'); ?></option>
                <option value="?orderby=views&order=DESC"><?php _e('Najpopularniejsze', 'png'); ?></option>
                <option value="?orderby=rating&order=DESC"><?php _e('Najlepiej oceniane', 'png'); ?></option>
                <option value="?orderby=price&order=ASC"><?php _e('Cena: rosnąco', 'png'); ?></option>
            </select>
        </div>
    </div>

    <!-- Listings Grid -->
    <div class="png-listings-grid">
        <?php
        if ($query->have_posts()) :
            while ($query->have_posts()) : $query->the_post();
                $listing_id = get_the_ID();
                $price = get_post_meta($listing_id, '_price', true);
                $location = get_post_meta($listing_id, '_location', true);
                $featured = get_post_meta($listing_id, '_featured', true);
                $verified = get_post_meta($listing_id, '_verified', true);
                $author_id = get_the_author_meta('ID');
                
                $profile = PNG_Users::get_profile($author_id);
        ?>
        
        <article class="png-listing-card <?php echo $featured ? 'png-featured' : ''; ?>" data-listing-id="<?php echo $listing_id; ?>">
            <?php if ($featured): ?>
            <div class="png-featured-badge">
                <i class="fas fa-star"></i> <?php _e('Wyróżnione', 'png'); ?>
            </div>
            <?php endif; ?>
            
            <!-- Image -->
            <div class="png-listing-image">
                <a href="<?php the_permalink(); ?>">
                    <?php if (has_post_thumbnail()): ?>
                        <?php the_post_thumbnail('medium'); ?>
                    <?php else: ?>
                        <img src="<?php echo PNG_PLUGIN_URL; ?>assets/images/placeholder.jpg" alt="<?php the_title(); ?>">
                    <?php endif; ?>
                </a>
                
                <!-- Favorite Button -->
                <?php if (is_user_logged_in()): ?>
                <button class="png-favorite-btn" data-listing-id="<?php echo $listing_id; ?>">
                    <i class="far fa-heart"></i>
                </button>
                <?php endif; ?>
            </div>
            
            <!-- Content -->
            <div class="png-listing-content">
                <!-- Author -->
                <div class="png-listing-author">
                    <img src="<?php echo $profile['avatar_url']; ?>" alt="<?php echo esc_attr($profile['display_name']); ?>" class="png-author-avatar">
                    <div class="png-author-info">
                        <h4>
                            <?php echo esc_html($profile['display_name']); ?>
                            <?php if ($profile['is_verified']): ?>
                            <span class="png-verified-badge" title="<?php _e('Zweryfikowany', 'png'); ?>">
                                <i class="fas fa-check-circle"></i>
                            </span>
                            <?php endif; ?>
                        </h4>
                        <?php if ($profile['avg_rating'] > 0): ?>
                        <div class="png-rating">
                            <i class="fas fa-star"></i>
                            <?php echo number_format($profile['avg_rating'], 1); ?>
                            <span>(<?php echo $profile['total_reviews']; ?>)</span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Title -->
                <h3 class="png-listing-title">
                    <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                </h3>
                
                <!-- Excerpt -->
                <div class="png-listing-excerpt">
                    <?php echo wp_trim_words(get_the_excerpt(), 20); ?>
                </div>
                
                <!-- Meta -->
                <div class="png-listing-meta">
                    <?php if ($location): ?>
                    <span class="png-meta-item">
                        <i class="fas fa-map-marker-alt"></i>
                        <?php echo esc_html($location); ?>
                    </span>
                    <?php endif; ?>
                    
                    <?php if ($price > 0): ?>
                    <span class="png-meta-item png-price">
                        <i class="fas fa-tag"></i>
                        <?php echo number_format($price, 0, ',', ' '); ?> PLN/h
                    </span>
                    <?php endif; ?>
                </div>
                
                <!-- Actions -->
                <div class="png-listing-actions">
                    <a href="<?php the_permalink(); ?>" class="png-button png-button-primary">
                        <?php _e('Zobacz szczegóły', 'png'); ?>
                    </a>
                </div>
            </div>
        </article>
        
        <?php
            endwhile;
        else:
        ?>
        
        <div class="png-no-results">
            <i class="fas fa-search fa-3x"></i>
            <h3><?php _e('Nie znaleziono ogłoszeń', 'png'); ?></h3>
            <p><?php _e('Spróbuj zmienić kryteria wyszukiwania', 'png'); ?></p>
        </div>
        
        <?php
        endif;
        wp_reset_postdata();
        ?>
    </div>
    
    <!-- Pagination -->
    <?php if ($query->max_num_pages > 1): ?>
    <div class="png-pagination">
        <?php
        echo paginate_links(array(
            'total' => $query->max_num_pages,
            'current' => $paged,
            'prev_text' => '<i class="fas fa-chevron-left"></i>',
            'next_text' => '<i class="fas fa-chevron-right"></i>'
        ));
        ?>
    </div>
    <?php endif; ?>
</div>

<style>
.png-listings-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 24px;
    margin: 30px 0;
}

.png-listing-card {
    background: #fff;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    transition: transform 0.3s, box-shadow 0.3s;
    position: relative;
}

.png-listing-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 4px 16px rgba(0,0,0,0.15);
}

.png-listing-card.png-featured {
    border: 2px solid #ffd700;
}

.png-featured-badge {
    position: absolute;
    top: 12px;
    left: 12px;
    background: #ffd700;
    color: #000;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    z-index: 10;
}

.png-listing-image {
    position: relative;
    height: 200px;
    overflow: hidden;
}

.png-listing-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.png-favorite-btn {
    position: absolute;
    top: 12px;
    right: 12px;
    background: white;
    border: none;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    cursor: pointer;
    box-shadow: 0 2px 8px rgba(0,0,0,0.15);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 10;
}

.png-favorite-btn:hover {
    background: #ff4444;
    color: white;
}

.png-listing-content {
    padding: 16px;
}

.png-listing-author {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 12px;
}

.png-author-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    object-fit: cover;
}

.png-author-info h4 {
    margin: 0;
    font-size: 14px;
    display: flex;
    align-items: center;
    gap: 6px;
}

.png-verified-badge {
    color: #1da1f2;
}

.png-rating {
    font-size: 12px;
    color: #666;
}

.png-rating .fa-star {
    color: #ffd700;
}

.png-listing-title {
    margin: 12px 0;
    font-size: 18px;
    line-height: 1.3;
}

.png-listing-title a {
    color: #333;
    text-decoration: none;
}

.png-listing-title a:hover {
    color: #007cba;
}

.png-listing-excerpt {
    color: #666;
    font-size: 14px;
    line-height: 1.5;
    margin-bottom: 12px;
}

.png-listing-meta {
    display: flex;
    gap: 16px;
    margin-bottom: 16px;
    font-size: 13px;
    color: #666;
}

.png-meta-item i {
    margin-right: 4px;
}

.png-price {
    color: #007cba;
    font-weight: 600;
}

.png-listing-actions .png-button {
    width: 100%;
}

.png-no-results {
    text-align: center;
    padding: 60px 20px;
    color: #666;
}

.png-no-results i {
    margin-bottom: 20px;
    opacity: 0.3;
}

.png-pagination {
    display: flex;
    justify-content: center;
    gap: 8px;
    margin: 40px 0;
}

.png-pagination a,
.png-pagination span {
    padding: 10px 16px;
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 6px;
    text-decoration: none;
    color: #333;
}

.png-pagination .current {
    background: #007cba;
    color: white;
    border-color: #007cba;
}

@media (max-width: 768px) {
    .png-listings-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // Favorite toggle
    $('.png-favorite-btn').on('click', function(e) {
        e.preventDefault();
        var $btn = $(this);
        var listingId = $btn.data('listing-id');
        
        $.ajax({
            url: pngData.ajaxUrl,
            type: 'POST',
            data: {
                action: 'png_toggle_favorite',
                nonce: pngData.nonce,
                listing_id: listingId
            },
            success: function(response) {
                if (response.success) {
                    $btn.find('i').toggleClass('far fas');
                }
            }
        });
    });
    
    // Track view on click
    $('.png-listing-card').on('click', 'a', function() {
        var listingId = $(this).closest('.png-listing-card').data('listing-id');
        
        $.ajax({
            url: pngData.ajaxUrl,
            type: 'POST',
            data: {
                action: 'png_view_listing',
                listing_id: listingId
            }
        });
    });
});
</script>