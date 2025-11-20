<?php
if (!defined('ABSPATH')) {
    exit;
}

class PNG_Post_Types {
    
    public static function init() {
        add_action('init', array(__CLASS__, 'register_post_types'));
        add_action('init', array(__CLASS__, 'register_taxonomies'));
    }
    
    public static function register_post_types() {
        // Custom Post Type dla ogłoszeń
        $labels = array(
            'name' => __('Ogłoszenia', 'png'),
            'singular_name' => __('Ogłoszenie', 'png'),
            'menu_name' => __('Ogłoszenia', 'png'),
            'name_admin_bar' => __('Ogłoszenie', 'png'),
            'add_new' => __('Dodaj nowe', 'png'),
            'add_new_item' => __('Dodaj nowe ogłoszenie', 'png'),
            'new_item' => __('Nowe ogłoszenie', 'png'),
            'edit_item' => __('Edytuj ogłoszenie', 'png'),
            'view_item' => __('Zobacz ogłoszenie', 'png'),
            'all_items' => __('Wszystkie ogłoszenia', 'png'),
            'search_items' => __('Szukaj ogłoszeń', 'png'),
            'not_found' => __('Nie znaleziono ogłoszeń', 'png'),
            'not_found_in_trash' => __('Nie znaleziono ogłoszeń w koszu', 'png')
        );

        $args = array(
            'labels' => $labels,
            'public' => true,
            'publicly_queryable' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'query_var' => true,
            'rewrite' => array('slug' => 'ogloszenia'),
            'capability_type' => 'post',
            'has_archive' => true,
            'hierarchical' => false,
            'menu_position' => 20,
            'menu_icon' => 'dashicons-groups',
            'supports' => array('title', 'editor', 'author', 'thumbnail', 'excerpt', 'comments'),
            'show_in_rest' => true,
            'rest_base' => 'listings'
        );

        register_post_type('listing', $args);
    }
    
    public static function register_taxonomies() {
        // Kategorie aktywności
        $labels = array(
            'name' => __('Kategorie', 'png'),
            'singular_name' => __('Kategoria', 'png'),
            'search_items' => __('Szukaj kategorii', 'png'),
            'all_items' => __('Wszystkie kategorie', 'png'),
            'parent_item' => __('Kategoria nadrzędna', 'png'),
            'parent_item_colon' => __('Kategoria nadrzędna:', 'png'),
            'edit_item' => __('Edytuj kategorię', 'png'),
            'update_item' => __('Zaktualizuj kategorię', 'png'),
            'add_new_item' => __('Dodaj nową kategorię', 'png'),
            'new_item_name' => __('Nazwa nowej kategorii', 'png'),
            'menu_name' => __('Kategorie', 'png')
        );

        $args = array(
            'hierarchical' => true,
            'labels' => $labels,
            'show_ui' => true,
            'show_admin_column' => true,
            'query_var' => true,
            'rewrite' => array('slug' => 'kategoria'),
            'show_in_rest' => true
        );

        register_taxonomy('listing_category', array('listing'), $args);
        
        // Tagi
        $labels = array(
            'name' => __('Tagi', 'png'),
            'singular_name' => __('Tag', 'png'),
            'search_items' => __('Szukaj tagów', 'png'),
            'all_items' => __('Wszystkie tagi', 'png'),
            'parent_item' => __('Tag nadrzędny', 'png'),
            'parent_item_colon' => __('Tag nadrzędny:', 'png'),
            'edit_item' => __('Edytuj tag', 'png'),
            'update_item' => __('Zaktualizuj tag', 'png'),
            'add_new_item' => __('Dodaj nowy tag', 'png'),
            'new_item_name' => __('Nazwa nowego tagu', 'png'),
            'menu_name' => __('Tagi', 'png')
        );

        $args = array(
            'hierarchical' => false,
            'labels' => $labels,
            'show_ui' => true,
            'show_admin_column' => true,
            'query_var' => true,
            'rewrite' => array('slug' => 'tag'),
            'show_in_rest' => true
        );

        register_taxonomy('listing_tag', array('listing'), $args);
    }
}