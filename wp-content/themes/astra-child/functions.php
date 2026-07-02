<?php
/**
 * Astra Child Theme Functions
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


add_action( 'wp_enqueue_scripts', 'astra_child_enqueue_styles' );
function astra_child_enqueue_styles() {
    wp_enqueue_style( 'astra-theme-css', get_template_directory_uri() . '/style.css', array(), ASTRA_THEME_VERSION );
    wp_enqueue_style( 'astra-child-css', get_stylesheet_directory_uri() . '/style.css', array( 'astra-theme-css' ), '1.0.0' );
}

add_action('init', 'initialize_custom_languages');
function initialize_custom_languages() {
    $languages = get_option('custom_languages');
    if (empty($languages) || !is_array($languages)) {
        update_option('custom_languages', ['en' => 'English', 'vi' => 'Tiếng Việt']);
        error_log('✅ Custom languages option created.');
    }
}

define( 'ASTRA_CHILD_INC_DIR', __DIR__ . '/inc/' );

require_once ASTRA_CHILD_INC_DIR . 'custom-post-types.php';
require_once ASTRA_CHILD_INC_DIR . 'custom-taxonomies.php';
require_once ASTRA_CHILD_INC_DIR . 'meta-boxes.php';
require_once ASTRA_CHILD_INC_DIR . 'frontend-enqueue.php';
require_once ASTRA_CHILD_INC_DIR . 'query-filters.php';
require_once ASTRA_CHILD_INC_DIR . 'banner-admin-utils.php';

require_once ASTRA_CHILD_INC_DIR . 'class-language.php';
require_once ASTRA_CHILD_INC_DIR . 'language-helpers.php';
require_once ASTRA_CHILD_INC_DIR . 'translation-filters.php';

add_action( 'init', 'astra_child_init_language' );
function astra_child_init_language() {
    if ( session_status() === PHP_SESSION_NONE ) {
        session_start();
    }
    Language::get_instance();
}