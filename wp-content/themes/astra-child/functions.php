<?php
/**
 * Astra Child Theme Functions
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// ==================================================
// 1. ENQUEUE PARENT ASTRA STYLES (CRITICAL!)
// ==================================================
add_action( 'wp_enqueue_scripts', 'astra_child_enqueue_styles' );
function astra_child_enqueue_styles() {
    // Load the parent theme's main stylesheet
    wp_enqueue_style( 'astra-theme-css', get_template_directory_uri() . '/style.css', array(), ASTRA_THEME_VERSION );
    
    // Load the child theme's custom styles (if you have any)
    wp_enqueue_style( 'astra-child-css', get_stylesheet_directory_uri() . '/style.css', array( 'astra-theme-css' ), '1.0.0' );
}

// ==================================================
// 2. LOAD FUNCTIONS
// ==================================================
define( 'ASTRA_CHILD_INC_DIR', __DIR__ . '/inc/' );

require_once ASTRA_CHILD_INC_DIR . 'custom-post-types.php';
require_once ASTRA_CHILD_INC_DIR . 'custom-taxonomies.php';
require_once ASTRA_CHILD_INC_DIR . 'meta-boxes.php';
require_once ASTRA_CHILD_INC_DIR . 'frontend-enqueue.php'; 
require_once ASTRA_CHILD_INC_DIR . 'query-filters.php';
require_once ASTRA_CHILD_INC_DIR . 'banner-admin-utils.php';