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
        error_log('Custom languages option created.');
    }
}

if ( ! defined( 'CHAT_NODE_SERVER_URL' ) ) {
    define( 'CHAT_NODE_SERVER_URL', 'http://chat-server:3000' );
}
if ( ! defined( 'CHAT_WS_URL' ) ) {
    define( 'CHAT_WS_URL', 'ws://localhost:8080' );
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
require_once ASTRA_CHILD_INC_DIR . 'news-scraper.php';

require_once ASTRA_CHILD_INC_DIR . 'login-redirect.php';
require_once ASTRA_CHILD_INC_DIR . 'custom-login-url.php';
require_once ASTRA_CHILD_INC_DIR . 'google-oauth.php';
require_once ASTRA_CHILD_INC_DIR . 'password-strength.php';
require_once ASTRA_CHILD_INC_DIR . 'register-enqueue.php';
require_once ASTRA_CHILD_INC_DIR . 'register-ajax.php';

require_once ASTRA_CHILD_INC_DIR . 'chat-db.php';
require_once ASTRA_CHILD_INC_DIR . 'chat-rest.php';
require_once ASTRA_CHILD_INC_DIR . 'chat-enqueue.php';

require_once ASTRA_CHILD_INC_DIR . 'email-verification.php';
require_once ASTRA_CHILD_INC_DIR . 'game-enqueue.php';

require_once ASTRA_CHILD_INC_DIR . 'nav-menu-custom.php';

add_action( 'init', 'astra_child_init_language' );
function astra_child_init_language() {
    if ( session_status() === PHP_SESSION_NONE ) {
        session_start();
    }
    Language::get_instance();
}

add_filter('rest_authentication_errors', function($result) {
    if (!empty($result)) {
        return $result;
    }
    $current_route = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
    if (strpos($current_route, '/mytheme/v1/chat/message/') !== false) {
        return $result;
    }
    if (!is_user_logged_in()) {
        return new WP_Error('rest_not_logged_in', 'You are not logged in.', array('status' => 401));
    }
    return $result;
});

add_action('template_include', function($template) {
    if (is_home()) {
        error_log('Blog archive template: ' . $template);
    }
    return $template;
});