<?php
add_action('wp_enqueue_scripts', 'chat_enqueue_scripts');
function chat_enqueue_scripts() {
    if (!is_page('chat')) {
        return;
    }

    $js_dir = get_stylesheet_directory_uri() . '/assets/js/chat/';

    wp_enqueue_script(
        'chat-config',
        $js_dir . 'config.js',
        array(),
        '4.7',
        true
    );

    wp_localize_script('chat-config', 'chatSettings', array(
        'restUrl'      => rest_url('mytheme/v1/chat'),
        'nonce'        => wp_create_nonce('wp_rest'),
        'websocketUrl' => defined('CHAT_WS_URL') ? CHAT_WS_URL : 'ws://localhost:8080',
        'userId'       => get_current_user_id(),
        'userName'     => wp_get_current_user()->display_name,
        'isAdmin'      => current_user_can('manage_options') ? '1' : '0',
    ));

    wp_enqueue_script(
        'chat-api',
        $js_dir . 'api.js',
        array('chat-config'),
        '4.7',
        true
    );

    wp_enqueue_script(
        'chat-ui',
        $js_dir . 'ui.js',
        array('chat-config'),
        '4.7',
        true
    );

    wp_enqueue_script(
        'chat-app',
        $js_dir . 'app.js',
        array('chat-config', 'chat-api', 'chat-ui'),
        '4.7',
        true
    );
}